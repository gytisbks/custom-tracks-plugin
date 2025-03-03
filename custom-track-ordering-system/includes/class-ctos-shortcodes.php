<?php
/**
 * Handles shortcodes for the Custom Track Ordering System.
 */
class CTOS_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('ctos_producer_settings', array($this, 'producer_settings_shortcode'));
        add_shortcode('custom_track_order_settings', array($this, 'producer_settings_shortcode')); // Alias for backward compatibility
        add_shortcode('ctos_order_form', array($this, 'order_form_shortcode'));
        add_shortcode('ctos_producer_orders', array($this, 'producer_orders_shortcode'));
        add_shortcode('ctos_customer_orders', array($this, 'customer_orders_shortcode'));
        add_shortcode('ctos_track_delivery', array($this, 'track_delivery_shortcode'));
        add_shortcode('ctos_order_button', array($this, 'order_button_shortcode'));
    }
    
    /**
     * Shortcode for displaying producer settings form
     */
    public function producer_settings_shortcode($atts) {
        // Check if user is logged in and is a producer
        if (!is_user_logged_in() || !CTOS_MarketKing_Integration::is_producer()) {
            return '<p>' . __('You must be logged in as a producer to view this content.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Get producer settings
        $producer_id = get_current_user_id();
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(CTOS_PLUGIN_DIR . 'templates/shortcodes/producer-settings.php');
        
        // Return buffer content
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying the order form
     */
    public function order_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'producer_id' => 0,
        ), $atts);
        
        $producer_id = intval($atts['producer_id']);
        
        // If no producer ID specified, try to get it from the context
        if (!$producer_id) {
            // Check if we're on a vendor profile page
            if (function_exists('marketking_get_page_vendor_id')) {
                $producer_id = marketking_get_page_vendor_id();
            }
            
            // If still no producer ID and user is a producer, use the current user
            if (!$producer_id && is_user_logged_in() && CTOS_MarketKing_Integration::is_producer()) {
                $producer_id = get_current_user_id();
            }
        }
        
        // If still no producer ID, return an error
        if (!$producer_id) {
            return '<p>' . __('No producer specified.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Check if this producer has enabled custom orders
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        if (!$settings->enable_custom_orders) {
            return '<p>' . __('This producer is not currently accepting custom track orders.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(CTOS_PLUGIN_DIR . 'templates/shortcodes/order-form.php');
        
        // Return buffer content
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying producer's track orders
     */
    public function producer_orders_shortcode($atts) {
        // Check if user is logged in and is a producer
        if (!is_user_logged_in() || !CTOS_MarketKing_Integration::is_producer()) {
            return '<p>' . __('You must be logged in as a producer to view this content.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Get producer orders
        $producer_id = get_current_user_id();
        $orders = CTOS_MarketKing_Integration::get_producer_orders($producer_id);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(CTOS_PLUGIN_DIR . 'templates/shortcodes/producer-orders.php');
        
        // Return buffer content
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying customer's track orders
     */
    public function customer_orders_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to view your orders.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Get customer orders
        $customer_id = get_current_user_id();
        $orders = CTOS_MarketKing_Integration::get_customer_orders($customer_id);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(CTOS_PLUGIN_DIR . 'templates/shortcodes/customer-orders.php');
        
        // Return buffer content
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying track delivery tools
     */
    public function track_delivery_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'order_id' => 0,
        ), $atts);
        
        $order_id = intval($atts['order_id']);
        
        // If no order ID specified, try to get it from the URL
        if (!$order_id && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
        }
        
        // If still no order ID, return an error
        if (!$order_id) {
            return '<p>' . __('No order specified.', 'custom-track-ordering-system') . '</p>';
        }
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(CTOS_PLUGIN_DIR . 'templates/shortcodes/track-delivery.php');
        
        // Return buffer content
        return ob_get_clean();
    }
    
    /**
     * Shortcode for displaying an "Order Custom Track" button
     */
    public function order_button_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'producer_id' => 0,
            'text' => __('Order Custom Track', 'custom-track-ordering-system'),
            'class' => '',
        ), $atts);
        
        $producer_id = intval($atts['producer_id']);
        $button_text = sanitize_text_field($atts['text']);
        $extra_class = sanitize_html_class($atts['class']);
        
        // If no producer ID specified, try to get it from the context
        if (!$producer_id) {
            // Check if we're on a vendor profile page
            if (function_exists('marketking_get_page_vendor_id')) {
                $producer_id = marketking_get_page_vendor_id();
            }
            
            // If still no producer ID, return an error
            if (!$producer_id) {
                return '<p>' . __('No producer specified.', 'custom-track-ordering-system') . '</p>';
            }
        }
        
        // Check if this producer has enabled custom orders
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        if (!$settings->enable_custom_orders) {
            return ''; // Return nothing if custom orders are disabled
        }
        
        // Include the order modal in the footer if not already included
        if (!did_action('wp_footer')) {
            add_action('wp_footer', function() {
                include_once(CTOS_PLUGIN_DIR . 'templates/order-form-modal.php');
            });
        }
        
        // Return the button HTML
        return '<div class="ctos-request-button-container">
            <button class="ctos-request-button ' . esc_attr($extra_class) . '" data-producer-id="' . esc_attr($producer_id) . '">
                ' . esc_html($button_text) . '
            </button>
        </div>';
    }
}
