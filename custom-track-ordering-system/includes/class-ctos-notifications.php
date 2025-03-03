<?php
/**
 * Handles notifications for custom track orders.
 */
class CTOS_Notifications {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_changes'), 10, 4);
        add_action('ctos_order_status_changed', array($this, 'handle_custom_order_status_changes'), 10, 3);
        
        // Hook into WooCommerce emails
        add_filter('woocommerce_email_classes', array($this, 'register_custom_emails'));
    }
    
    /**
     * Handle WooCommerce order status changes
     */
    public function handle_order_status_changes($order_id, $from_status, $to_status, $order) {
        // Check if this is a custom track order
        $is_track_order = false;
        
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_ctos_producer_id')) {
                $is_track_order = true;
                break;
            }
        }
        
        if (!$is_track_order) {
            return;
        }
        
        // Handle specific status changes
        if ($to_status === 'processing' || $to_status === 'completed') {
            // Check if this is a final payment order
            $is_final_payment = false;
            $original_order_id = 0;
            
            foreach ($order->get_items() as $item) {
                if ($item->get_meta('_ctos_is_final_payment') === 'yes') {
                    $is_final_payment = true;
                    $original_order_id = $item->get_meta('_ctos_original_order_id');
                    break;
                }
            }
            
            if ($is_final_payment && $original_order_id) {
                // Update the original order's status
                global $wpdb;
                $meta_table = $wpdb->prefix . 'ctos_order_meta';
                
                $wpdb->update(
                    $meta_table,
                    array(
                        'status' => 'awaiting_final_delivery',
                        'final_paid' => 1
                    ),
                    array('order_id' => $original_order_id)
                );
                
                // Get producer ID
                $order_meta = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $meta_table WHERE order_id = %d",
                    $original_order_id
                ));
                
                if ($order_meta) {
                    // Send notification to producer
                    $workflow = new CTOS_Order_Workflow();
                    $workflow->send_producer_notification($order_meta->producer_id, $original_order_id, 'final_payment_received');
                    
                    // Add note to the MarketKing thread
                    $thread_id = get_post_meta($original_order_id, '_ctos_message_thread_id', true);
                    if ($thread_id && function_exists('marketking_add_message_to_thread')) {
                        marketking_add_message_to_thread(
                            $thread_id,
                            0, // System message
                            __('Final payment has been received. The producer can now deliver the final files.', 'custom-track-ordering-system')
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Handle custom order status changes
     */
    public function handle_custom_order_status_changes($order_id, $from_status, $to_status) {
        // Get order data
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta) {
            return;
        }
        
        // Handle specific status transitions
        switch ($to_status) {
            case 'pending_demo_submission':
                // Producer needs to create and upload a demo
                break;
                
            case 'awaiting_customer_approval':
                // Customer needs to review the demo
                break;
                
            case 'awaiting_final_payment':
                // Customer needs to make the final payment
                break;
                
            case 'awaiting_final_delivery':
                // Producer needs to deliver final files
                break;
                
            case 'completed':
                // Order is complete
                break;
        }
        
        // Add custom action hooks for each status change
        do_action('ctos_order_status_' . $to_status, $order_id, $order_meta);
    }
    
    /**
     * Register custom WooCommerce email classes
     */
    public function register_custom_emails($email_classes) {
        // Include custom email classes
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-deposit-payment-email.php';
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-demo-submitted-email.php';
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-demo-approved-email.php';
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-revision-requested-email.php';
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-final-payment-email.php';
        include_once CTOS_PLUGIN_DIR . 'includes/emails/class-ctos-final-files-email.php';
        
        // Add email classes
        $email_classes['CTOS_Deposit_Payment_Email'] = new CTOS_Deposit_Payment_Email();
        $email_classes['CTOS_Demo_Submitted_Email'] = new CTOS_Demo_Submitted_Email();
        $email_classes['CTOS_Demo_Approved_Email'] = new CTOS_Demo_Approved_Email();
        $email_classes['CTOS_Revision_Requested_Email'] = new CTOS_Revision_Requested_Email();
        $email_classes['CTOS_Final_Payment_Email'] = new CTOS_Final_Payment_Email();
        $email_classes['CTOS_Final_Files_Email'] = new CTOS_Final_Files_Email();
        
        return $email_classes;
    }
}
