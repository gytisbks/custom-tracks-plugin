<?php
/**
 * Integration with WooCommerce for handling orders and payments.
 */
class CTOS_WooCommerce_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add custom data to cart item
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        
        // Display custom data in cart and checkout
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_custom_data'), 10, 2);
        
        // Save custom data to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_data_to_order_items'), 10, 4);
        
        // Process order when completed
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'));
        
        // Add custom tab to My Account
        add_filter('woocommerce_account_menu_items', array($this, 'add_custom_tracks_account_menu_item'));
        add_action('woocommerce_account_custom-tracks_endpoint', array($this, 'custom_tracks_endpoint_content'));
        
        // Register the endpoint
        add_action('init', array($this, 'add_custom_tracks_endpoint'));
        add_filter('query_vars', array($this, 'add_custom_tracks_query_vars'));
        
        // Adjust cart item price
        add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 10, 1);
    }
    
    /**
     * Add custom data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Check if this is our custom track order
        if (isset($_POST['_ctos_track_order']) || (isset($cart_item_data['_ctos_track_order']) && $cart_item_data['_ctos_track_order'])) {
            // If we're already processing a cart item update, don't do anything
            if (isset($cart_item_data['_ctos_track_order']) && $cart_item_data['_ctos_track_order']) {
                return $cart_item_data;
            }
            
            // This is coming from our form - get the data
            $cart_item_data['_ctos_track_order'] = true;
            $cart_item_data['_ctos_producer_id'] = isset($_POST['producer_id']) ? intval($_POST['producer_id']) : 0;
            $cart_item_data['_ctos_track_title'] = isset($_POST['track_title']) ? sanitize_text_field($_POST['track_title']) : '';
            $cart_item_data['_ctos_genre'] = isset($_POST['genre']) ? sanitize_text_field($_POST['genre']) : '';
            $cart_item_data['_ctos_description'] = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
            $cart_item_data['_ctos_reference_tracks'] = isset($_POST['reference_tracks']) ? sanitize_textarea_field($_POST['reference_tracks']) : '';
            
            // Generate a unique ID for this cart item to prevent merging
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }
        
        return $cart_item_data;
    }
    
    /**
     * Adjust cart item price before totals are calculated
     */
    public function before_calculate_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['_ctos_track_order']) && $cart_item['_ctos_track_order']) {
                // Use the deposit amount for the price
                if (isset($cart_item['_ctos_deposit_amount']) && $cart_item['_ctos_deposit_amount'] > 0) {
                    $cart_item['data']->set_price($cart_item['_ctos_deposit_amount']);
                    
                    // Log the price set
                    error_log('CTOS: Setting cart item price to: ' . $cart_item['_ctos_deposit_amount']);
                } else if (isset($cart_item['_ctos_total_price']) && $cart_item['_ctos_total_price'] > 0) {
                    // If no deposit amount, use total price
                    $cart_item['data']->set_price($cart_item['_ctos_total_price']);
                    
                    // Log the price set
                    error_log('CTOS: Setting cart item price to total price: ' . $cart_item['_ctos_total_price']);
                } else if (isset($cart_item['_ctos_base_price']) && $cart_item['_ctos_base_price'] > 0) {
                    // Fallback to base price
                    $cart_item['data']->set_price($cart_item['_ctos_base_price']);
                    
                    // Log the price set
                    error_log('CTOS: Setting cart item price to base price: ' . $cart_item['_ctos_base_price']);
                }
            }
        }
    }
    
    /**
     * Display custom data in cart and checkout
     */
    public function display_cart_item_custom_data($item_data, $cart_item) {
        if (isset($cart_item['_ctos_track_order']) && $cart_item['_ctos_track_order']) {
            $item_data[] = array(
                'key'   => __('Item', 'custom-track-ordering-system'),
                'value' => __('Custom Track Order', 'custom-track-ordering-system')
            );
            
            $item_data[] = array(
                'key'   => __('Track Title', 'custom-track-ordering-system'),
                'value' => $cart_item['_ctos_track_title']
            );
            
            $item_data[] = array(
                'key'   => __('Genre', 'custom-track-ordering-system'),
                'value' => $cart_item['_ctos_genre']
            );
            
            if (isset($cart_item['_ctos_order_type']) && $cart_item['_ctos_order_type'] === 'deposit') {
                $item_data[] = array(
                    'key'   => __('Payment Type', 'custom-track-ordering-system'),
                    'value' => __('Initial Deposit (30%)', 'custom-track-ordering-system')
                );
                
                // Show the full price info
                if (isset($cart_item['_ctos_total_price'])) {
                    $item_data[] = array(
                        'key'   => __('Full Price', 'custom-track-ordering-system'),
                        'value' => wc_price($cart_item['_ctos_total_price'])
                    );
                }
            }
        }
        
        return $item_data;
    }
    
    /**
     * Save custom data to order items
     */
    public function add_custom_data_to_order_items($item, $cart_item_key, $cart_item, $order) {
        if (isset($cart_item['_ctos_track_order']) && $cart_item['_ctos_track_order']) {
            // Save all custom data as order item meta
            $item->add_meta_data('_ctos_track_order', true);
            $item->add_meta_data('_ctos_producer_id', $cart_item['_ctos_producer_id']);
            $item->add_meta_data('_ctos_customer_id', get_current_user_id());
            $item->add_meta_data('_ctos_track_title', $cart_item['_ctos_track_title']);
            $item->add_meta_data('_ctos_genre', $cart_item['_ctos_genre']);
            $item->add_meta_data('_ctos_description', $cart_item['_ctos_description']);
            
            if (isset($cart_item['_ctos_reference_tracks'])) {
                $item->add_meta_data('_ctos_reference_tracks', $cart_item['_ctos_reference_tracks']);
            }
            
            if (isset($cart_item['_ctos_selected_addons'])) {
                $item->add_meta_data('_ctos_selected_addons', $cart_item['_ctos_selected_addons']);
            }
            
            if (isset($cart_item['_ctos_base_price'])) {
                $item->add_meta_data('_ctos_base_price', $cart_item['_ctos_base_price']);
            }
            
            if (isset($cart_item['_ctos_total_price'])) {
                $item->add_meta_data('_ctos_total_price', $cart_item['_ctos_total_price']);
            }
            
            if (isset($cart_item['_ctos_deposit_amount'])) {
                $item->add_meta_data('_ctos_deposit_amount', $cart_item['_ctos_deposit_amount']);
            }
            
            if (isset($cart_item['_ctos_order_type'])) {
                $item->add_meta_data('_ctos_order_type', $cart_item['_ctos_order_type']);
            }
        }
    }
    
    /**
     * Process order when completed
     */
    public function process_completed_order($order_id) {
        $order = wc_get_order($order_id);
        
        // Check if this order contains a custom track order
        $has_track_order = false;
        $track_order_data = array();
        
        foreach ($order->get_items() as $item_id => $item) {
            $is_track_order = $item->get_meta('_ctos_track_order');
            
            if ($is_track_order) {
                $has_track_order = true;
                
                // Get all track order data
                $track_order_data = array(
                    'item_id' => $item_id,
                    'producer_id' => $item->get_meta('_ctos_producer_id'),
                    'customer_id' => $item->get_meta('_ctos_customer_id'),
                    'track_title' => $item->get_meta('_ctos_track_title'),
                    'genre' => $item->get_meta('_ctos_genre'),
                    'description' => $item->get_meta('_ctos_description'),
                    'reference_tracks' => $item->get_meta('_ctos_reference_tracks'),
                    'selected_addons' => $item->get_meta('_ctos_selected_addons'),
                    'base_price' => $item->get_meta('_ctos_base_price'),
                    'total_price' => $item->get_meta('_ctos_total_price'),
                    'deposit_amount' => $item->get_meta('_ctos_deposit_amount'),
                    'order_type' => $item->get_meta('_ctos_order_type'),
                );
                
                break;
            }
        }
        
        if ($has_track_order) {
            // Create record in our custom table
            global $wpdb;
            $table_name = $wpdb->prefix . 'ctos_order_meta';
            
            // Ensure table exists
            $this->create_order_meta_table();
            
            // Check if this order is already recorded
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE order_id = %d",
                $order_id
            ));
            
            if (!$existing) {
                // Insert new record
                $wpdb->insert(
                    $table_name,
                    array(
                        'order_id' => $order_id,
                        'producer_id' => $track_order_data['producer_id'],
                        'customer_id' => $track_order_data['customer_id'],
                        'track_title' => $track_order_data['track_title'],
                        'genre' => $track_order_data['genre'],
                        'description' => $track_order_data['description'],
                        'reference_tracks' => $track_order_data['reference_tracks'],
                        'selected_addons' => is_array($track_order_data['selected_addons']) ? json_encode($track_order_data['selected_addons']) : $track_order_data['selected_addons'],
                        'base_price' => $track_order_data['base_price'],
                        'total_price' => $track_order_data['total_price'],
                        'deposit_amount' => $track_order_data['deposit_amount'],
                        'payment_status' => $track_order_data['order_type'] === 'deposit' ? 'deposit_paid' : 'paid',
                        'order_status' => 'new',
                        'created_at' => current_time('mysql')
                    )
                );
                
                // Get the inserted ID
                $track_order_id = $wpdb->insert_id;
                
                // Send notification emails
                if (class_exists('CTOS_Notifications')) {
                    $notifications = new CTOS_Notifications();
                    $notifications->send_new_order_notifications($track_order_id, $order);
                }
            }
        }
    }
    
    /**
     * Create order meta table if it doesn't exist
     */
    private function create_order_meta_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctos_order_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                order_id bigint(20) NOT NULL,
                producer_id bigint(20) NOT NULL,
                customer_id bigint(20) NOT NULL,
                track_title varchar(255) NOT NULL,
                genre varchar(100) NOT NULL,
                description text NOT NULL,
                reference_tracks text,
                selected_addons text,
                base_price decimal(10,2) NOT NULL DEFAULT 0.00,
                total_price decimal(10,2) NOT NULL DEFAULT 0.00,
                deposit_amount decimal(10,2) NOT NULL DEFAULT 0.00,
                payment_status varchar(50) NOT NULL DEFAULT 'pending',
                order_status varchar(50) NOT NULL DEFAULT 'new',
                demo_file varchar(255),
                final_file varchar(255),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY order_id (order_id),
                KEY producer_id (producer_id),
                KEY customer_id (customer_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Add custom tracks menu item to My Account
     */
    public function add_custom_tracks_account_menu_item($items) {
        // Add the custom tracks item after orders
        $new_items = array();
        
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            
            if ($key === 'orders') {
                $new_items['custom-tracks'] = __('Custom Tracks', 'custom-track-ordering-system');
            }
        }
        
        return $new_items;
    }
    
    /**
     * Add custom endpoint
     */
    public function add_custom_tracks_endpoint() {
        add_rewrite_endpoint('custom-tracks', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Add query vars
     */
    public function add_custom_tracks_query_vars($vars) {
        $vars[] = 'custom-tracks';
        return $vars;
    }
    
    /**
     * Custom tracks endpoint content
     */
    public function custom_tracks_endpoint_content() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }
        
        // Get customer ID
        $customer_id = get_current_user_id();
        
        // Check if user is a producer
        $is_producer = CTOS_MarketKing_Integration::is_producer($customer_id);
        
        if ($is_producer) {
            // Display producer orders
            echo do_shortcode('[ctos_producer_orders]');
        } else {
            // Display customer orders
            echo do_shortcode('[ctos_customer_orders]');
        }
    }
}
