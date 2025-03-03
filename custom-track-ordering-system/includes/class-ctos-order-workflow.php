<?php
/**
 * Handles the custom track order workflow.
 */
class CTOS_Order_Workflow {
    
    /**
     * Constructor
     */
    public function __construct() {
        // WooCommerce hooks
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_data_to_order_items'), 10, 4);
        add_action('woocommerce_checkout_order_processed', array($this, 'process_track_order'), 10, 3);
        
        // Order status workflow hooks
        add_action('woocommerce_order_status_completed', array($this, 'handle_deposit_payment_complete'));
        add_action('woocommerce_order_status_processing', array($this, 'handle_deposit_payment_processing'));
        
        // AJAX actions for workflow
        add_action('wp_ajax_ctos_upload_demo', array($this, 'ajax_upload_demo'));
        add_action('wp_ajax_ctos_approve_demo', array($this, 'ajax_approve_demo'));
        add_action('wp_ajax_ctos_request_revision', array($this, 'ajax_request_revision'));
        add_action('wp_ajax_ctos_upload_final_files', array($this, 'ajax_upload_final_files'));
        add_action('wp_ajax_ctos_complete_order', array($this, 'ajax_complete_order'));
    }
    
    /**
     * Add custom track order data to order line items
     */
    public function add_custom_data_to_order_items($item, $cart_item_key, $values, $order) {
        if (isset($values['ctos_custom_track_order'])) {
            $custom_data = $values['ctos_custom_track_order'];
            
            foreach ($custom_data as $key => $value) {
                if (is_array($value)) {
                    $item->add_meta_data('_ctos_' . $key, json_encode($value));
                } else {
                    $item->add_meta_data('_ctos_' . $key, $value);
                }
            }
            
            // Set the line item to 30% of the total price (deposit)
            $deposit_amount = floatval($custom_data['deposit_amount']);
            $item->set_total($deposit_amount);
            $item->set_subtotal($deposit_amount);
            
            // Add a note to indicate this is a deposit payment
            $item->add_meta_data('_ctos_is_deposit', 'yes');
        }
    }
    
    /**
     * Process track order after checkout is complete
     */
    public function process_track_order($order_id, $posted_data, $order) {
        $order_contains_track = false;
        $producer_id = 0;
        $customer_id = $order->get_user_id();
        
        // Check if order contains a track order
        foreach ($order->get_items() as $item) {
            $producer_id = $item->get_meta('_ctos_producer_id');
            
            if ($producer_id) {
                $order_contains_track = true;
                break;
            }
        }
        
        if (!$order_contains_track) {
            return;
        }
        
        // Create a custom track order record
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        $order_data = array(
            'order_id' => $order_id,
            'producer_id' => $producer_id,
            'customer_id' => $customer_id,
            'service_type' => $item->get_meta('_ctos_service_type'),
            'genres' => $item->get_meta('_ctos_genres'),
            'bpm' => intval($item->get_meta('_ctos_bpm')),
            'mood' => $item->get_meta('_ctos_mood'),
            'track_length' => $item->get_meta('_ctos_track_length'),
            'addons' => $item->get_meta('_ctos_addons'),
            'instructions' => $item->get_meta('_ctos_instructions'),
            'status' => 'pending_demo_submission',
            'deposit_paid' => 0,
        );
        
        $wpdb->insert($meta_table, $order_data);
        
        // Create a MarketKing message thread
        if (function_exists('marketking_create_message_thread')) {
            $thread_id = marketking_create_message_thread(
                $customer_id,
                $producer_id,
                sprintf(__('Custom Track Order #%s', 'custom-track-ordering-system'), $order_id),
                sprintf(__('New custom track order #%s has been placed. The producer will begin working on your track after the deposit payment is confirmed.', 'custom-track-ordering-system'), $order_id)
            );
            
            // Save thread ID to order meta
            update_post_meta($order_id, '_ctos_message_thread_id', $thread_id);
        }
    }
    
    /**
     * Handle deposit payment complete
     */
    public function handle_deposit_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $contains_deposit = false;
        
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_ctos_is_deposit') === 'yes') {
                $contains_deposit = true;
                break;
            }
        }
        
        if (!$contains_deposit) {
            return;
        }
        
        // Update order meta to indicate deposit is paid
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        $wpdb->update(
            $meta_table,
            array('deposit_paid' => 1),
            array('order_id' => $order_id)
        );
        
        // Send notification to producer
        $producer_id = $item->get_meta('_ctos_producer_id');
        $this->send_producer_notification($producer_id, $order_id, 'deposit_paid');
        
        // Add note to the MarketKing thread
        $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
        if ($thread_id && function_exists('marketking_add_message_to_thread')) {
            marketking_add_message_to_thread(
                $thread_id,
                0, // System message
                sprintf(__('Deposit payment for order #%s has been received. The producer can now start working on your track.', 'custom-track-ordering-system'), $order_id)
            );
        }
    }
    
    /**
     * Handle deposit payment processing (for payment methods that process immediately)
     */
    public function handle_deposit_payment_processing($order_id) {
        $this->handle_deposit_payment_complete($order_id);
    }
    
    /**
     * Upload demo track via AJAX
     */
    public function ajax_upload_demo() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (!isset($_POST['order_id']) || !isset($_FILES['demo_file'])) {
            wp_send_json_error('Missing required data');
        }
        
        $order_id = intval($_POST['order_id']);
        
        // Check if user is the producer for this order
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta || $order_meta->producer_id != get_current_user_id()) {
            wp_send_json_error('You are not authorized to upload a demo for this order');
        }
        
        // Handle file upload
        $upload_dir = wp_upload_dir();
        $ctos_dir = $upload_dir['basedir'] . '/ctos_files/' . $order_id . '/demos/';
        
        // Create directory if it doesn't exist
        wp_mkdir_p($ctos_dir);
        
        // Generate a unique filename
        $filename = 'demo_' . time() . '_' . sanitize_file_name($_FILES['demo_file']['name']);
        $file_path = $ctos_dir . $filename;
        
        // Move the uploaded file
        if (move_uploaded_file($_FILES['demo_file']['tmp_name'], $file_path)) {
            // Update order status
            $wpdb->update(
                $meta_table,
                array(
                    'status' => 'awaiting_customer_approval',
                    'demo_url' => $upload_dir['baseurl'] . '/ctos_files/' . $order_id . '/demos/' . $filename
                ),
                array('order_id' => $order_id)
            );
            
            // Send notification to customer
            $this->send_customer_notification($order_meta->customer_id, $order_id, 'demo_submitted');
            
            // Add message to thread
            $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
            if ($thread_id && function_exists('marketking_add_message_to_thread')) {
                $demo_url = $upload_dir['baseurl'] . '/ctos_files/' . $order_id . '/demos/' . $filename;
                
                marketking_add_message_to_thread(
                    $thread_id,
                    $order_meta->producer_id,
                    sprintf(__('I have uploaded a demo for your review. [Listen to Demo](%s)', 'custom-track-ordering-system'), $demo_url)
                );
            }
            
            wp_send_json_success('Demo uploaded successfully');
        } else {
            wp_send_json_error('Failed to upload demo file');
        }
    }
    
    /**
     * Approve demo via AJAX
     */
    public function ajax_approve_demo() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (!isset($_POST['order_id'])) {
            wp_send_json_error('Missing required data');
        }
        
        $order_id = intval($_POST['order_id']);
        
        // Check if user is the customer for this order
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta || $order_meta->customer_id != get_current_user_id()) {
            wp_send_json_error('You are not authorized to approve this demo');
        }
        
        // Update order status
        $wpdb->update(
            $meta_table,
            array(
                'status' => 'awaiting_final_payment',
                'demo_approved' => 1
            ),
            array('order_id' => $order_id)
        );
        
        // Create a new order for the remaining balance (70%)
        $original_order = wc_get_order($order_id);
        $total_price = 0;
        
        foreach ($original_order->get_items() as $item) {
            if ($item->get_meta('_ctos_is_deposit') === 'yes') {
                $total_price = floatval($item->get_meta('_ctos_total_price'));
                break;
            }
        }
        
        // Calculate remaining amount (70%)
        $remaining_amount = $total_price * 0.7;
        
        // Create new order
        $final_order = wc_create_order(array(
            'customer_id' => $order_meta->customer_id,
            'status' => 'pending',
        ));
        
        // Add the same product but with different price
        $product_id = $this->get_track_order_product_id();
        $item_id = $final_order->add_product(wc_get_product($product_id), 1);
        
        if ($item_id) {
            $item = $final_order->get_item($item_id);
            $item->add_meta_data('_ctos_producer_id', $order_meta->producer_id);
            $item->add_meta_data('_ctos_service_type', $order_meta->service_type);
            $item->add_meta_data('_ctos_is_final_payment', 'yes');
            $item->add_meta_data('_ctos_original_order_id', $order_id);
            $item->set_total($remaining_amount);
            $item->set_subtotal($remaining_amount);
            $item->save();
        }
        
        // Calculate totals
        $final_order->calculate_totals();
        $final_order->save();
        
        // Add note to the MarketKing thread
        $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
        if ($thread_id && function_exists('marketking_add_message_to_thread')) {
            marketking_add_message_to_thread(
                $thread_id,
                $order_meta->customer_id,
                sprintf(__('I have approved the demo. To receive the final track, please complete the remaining payment here: [Pay Now](%s)', 'custom-track-ordering-system'), $final_order->get_checkout_payment_url())
            );
        }
        
        // Send notification to producer
        $this->send_producer_notification($order_meta->producer_id, $order_id, 'demo_approved');
        
        wp_send_json_success(array(
            'message' => 'Demo approved successfully',
            'payment_url' => $final_order->get_checkout_payment_url()
        ));
    }
    
    /**
     * Request revision via AJAX
     */
    public function ajax_request_revision() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (!isset($_POST['order_id']) || !isset($_POST['revision_notes'])) {
            wp_send_json_error('Missing required data');
        }
        
        $order_id = intval($_POST['order_id']);
        $revision_notes = sanitize_textarea_field($_POST['revision_notes']);
        
        // Check if user is the customer for this order
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta || $order_meta->customer_id != get_current_user_id()) {
            wp_send_json_error('You are not authorized to request revisions for this order');
        }
        
        // Check if max revisions has been reached
        $producer_settings = $this->get_producer_settings($order_meta->producer_id);
        if ($order_meta->revision_count >= $producer_settings->revisions) {
            wp_send_json_error('Maximum number of revisions reached');
        }
        
        // Update order status and increment revision count
        $wpdb->update(
            $meta_table,
            array(
                'status' => 'pending_demo_submission',
                'revision_count' => $order_meta->revision_count + 1
            ),
            array('order_id' => $order_id)
        );
        
        // Add message to thread
        $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
        if ($thread_id && function_exists('marketking_add_message_to_thread')) {
            marketking_add_message_to_thread(
                $thread_id,
                $order_meta->customer_id,
                sprintf(__('I am requesting a revision for the demo. Here are my notes: %s', 'custom-track-ordering-system'), $revision_notes)
            );
        }
        
        // Send notification to producer
        $this->send_producer_notification($order_meta->producer_id, $order_id, 'revision_requested');
        
        wp_send_json_success('Revision requested successfully');
    }
    
    /**
     * Upload final files via AJAX
     */
    public function ajax_upload_final_files() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (!isset($_POST['order_id']) || empty($_FILES)) {
            wp_send_json_error('Missing required data');
        }
        
        $order_id = intval($_POST['order_id']);
        
        // Check if user is the producer for this order
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta || $order_meta->producer_id != get_current_user_id()) {
            wp_send_json_error('You are not authorized to upload final files for this order');
        }
        
        // Check if final payment is made
        if (!$order_meta->final_paid) {
            wp_send_json_error('Final payment has not been made yet');
        }
        
        // Handle file uploads
        $upload_dir = wp_upload_dir();
        $ctos_dir = $upload_dir['basedir'] . '/ctos_files/' . $order_id . '/final/';
        
        // Create directory if it doesn't exist
        wp_mkdir_p($ctos_dir);
        
        $uploaded_files = array();
        
        foreach ($_FILES as $key => $file) {
            $filename = sanitize_file_name($file['name']);
            $file_path = $ctos_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_files[] = array(
                    'name' => $filename,
                    'url' => $upload_dir['baseurl'] . '/ctos_files/' . $order_id . '/final/' . $filename
                );
            }
        }
        
        // Update order status
        $wpdb->update(
            $meta_table,
            array(
                'status' => 'completed',
                'final_files' => json_encode($uploaded_files)
            ),
            array('order_id' => $order_id)
        );
        
        // Add message to thread
        $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
        if ($thread_id && function_exists('marketking_add_message_to_thread')) {
            $files_list = '';
            foreach ($uploaded_files as $file) {
                $files_list .= sprintf('[%s](%s) ', $file['name'], $file['url']);
            }
            
            marketking_add_message_to_thread(
                $thread_id,
                $order_meta->producer_id,
                sprintf(__('I have uploaded the final files for your order: %s', 'custom-track-ordering-system'), $files_list)
            );
        }
        
        // Send notification to customer
        $this->send_customer_notification($order_meta->customer_id, $order_id, 'final_files_ready');
        
        wp_send_json_success('Final files uploaded successfully');
    }
    
    /**
     * Complete order via AJAX
     */
    public function ajax_complete_order() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (!isset($_POST['order_id'])) {
            wp_send_json_error('Missing required data');
        }
        
        $order_id = intval($_POST['order_id']);
        
        // Check if user is the customer for this order
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta || $order_meta->customer_id != get_current_user_id()) {
            wp_send_json_error('You are not authorized to complete this order');
        }
        
        // Mark the original WooCommerce order as completed
        $original_order = wc_get_order($order_id);
        if ($original_order) {
            $original_order->update_status('completed', __('Customer confirmed track delivery.', 'custom-track-ordering-system'));
        }
        
        // Find the final payment order and mark it as completed if it exists
        $args = array(
            'meta_key' => '_ctos_original_order_id',
            'meta_value' => $order_id,
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => 1,
        );
        
        $orders = wc_get_orders($args);
        if (!empty($orders)) {
            $final_order = $orders[0];
            $final_order->update_status('completed', __('Customer confirmed track delivery.', 'custom-track-ordering-system'));
        }
        
        // Add message to thread
        $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
        if ($thread_id && function_exists('marketking_add_message_to_thread')) {
            marketking_add_message_to_thread(
                $thread_id,
                $order_meta->customer_id,
                __('I have received the final files and confirm the order is complete.', 'custom-track-ordering-system')
            );
        }
        
        wp_send_json_success('Order completed successfully');
    }
    
    /**
     * Get producer settings
     */
    private function get_producer_settings($producer_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctos_producer_settings';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE producer_id = %d",
            $producer_id
        ));
    }
    
    /**
     * Get the track order product ID
     */
    private function get_track_order_product_id() {
        $products = wc_get_products(array(
            'limit' => 1,
            'status' => 'publish',
            'type' => 'simple',
            'meta_key' => '_ctos_track_order_product',
            'meta_value' => 'yes',
        ));
        
        if (!empty($products)) {
            return $products[0]->get_id();
        }
        
        return 0;
    }
    
    /**
     * Send notification to producer
     */
    private function send_producer_notification($producer_id, $order_id, $type) {
        $producer = get_user_by('id', $producer_id);
        if (!$producer) {
            return;
        }
        
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'deposit_paid':
                $subject = sprintf(__('Deposit payment received for Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The deposit payment for order #%s has been received. You can now start working on the custom track. Log in to your dashboard to see the order details.', 'custom-track-ordering-system'), $order_id);
                break;
                
            case 'demo_approved':
                $subject = sprintf(__('Demo approved for Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The customer has approved your demo for order #%s. Once they complete the final payment, you can upload the final files.', 'custom-track-ordering-system'), $order_id);
                break;
                
            case 'revision_requested':
                $subject = sprintf(__('Revision requested for Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The customer has requested a revision for order #%s. Please check your dashboard for their feedback.', 'custom-track-ordering-system'), $order_id);
                break;
                
            case 'final_payment_received':
                $subject = sprintf(__('Final payment received for Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The final payment for order #%s has been received. Please deliver the final track files to the customer.', 'custom-track-ordering-system'), $order_id);
                break;
        }
        
        if (!empty($subject) && !empty($message)) {
            wp_mail($producer->user_email, $subject, $message);
        }
    }
    
    /**
     * Send notification to customer
     */
    private function send_customer_notification($customer_id, $order_id, $type) {
        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            return;
        }
        
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'demo_submitted':
                $subject = sprintf(__('Demo ready for your review - Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The producer has submitted a demo for your order #%s. Please login to your account to review it and provide feedback or approval.', 'custom-track-ordering-system'), $order_id);
                break;
                
            case 'final_files_ready':
                $subject = sprintf(__('Your custom track is ready - Order #%s', 'custom-track-ordering-system'), $order_id);
                $message = sprintf(__('The producer has delivered the final files for your order #%s. Please login to your account to download the files.', 'custom-track-ordering-system'), $order_id);
                break;
        }
        
        if (!empty($subject) && !empty($message)) {
            wp_mail($customer->user_email, $subject, $message);
        }
    }
}
