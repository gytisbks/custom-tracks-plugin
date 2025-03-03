<?php
/**
 * Handles the order form and processing.
 */
class CTOS_Order_Form {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Ajax handlers
        add_action('wp_ajax_ctos_create_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_nopriv_ctos_create_order', array($this, 'ajax_create_order'));
        
        // Ajax handler for fetching producer settings
        add_action('wp_ajax_ctos_get_producer_settings', array($this, 'ajax_get_producer_settings'));
        add_action('wp_ajax_nopriv_ctos_get_producer_settings', array($this, 'ajax_get_producer_settings'));
        
        // Display order button in producer profile
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_order_button_to_producer_profile'), 15);
        
        // Add modal to footer - ALWAYS include on all pages where button might appear
        add_action('wp_footer', array($this, 'add_order_modal_to_footer'));
    }
    
    /**
     * Add order button to producer profile
     */
    public function add_order_button_to_producer_profile() {
        global $post;
        
        // Only on single product pages where the producer's profile is being viewed
        if (!is_singular('seller') && !is_author()) {
            return;
        }
        
        // Get producer ID
        $producer_id = 0;
        
        // Author page
        if (is_author()) {
            $producer_id = get_the_author_meta('ID');
        }
        
        // Market King store page
        else if (function_exists('marketking_get_page_vendor_id')) {
            $producer_id = marketking_get_page_vendor_id();
        }
        
        if (!$producer_id) {
            return;
        }
        
        // Check if producer has enabled custom orders
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        if (!$settings->enable_custom_orders) {
            return;
        }
        
        // Output the button
        ?>
        <div class="ctos-request-button-container">
            <button class="ctos-request-button" data-producer-id="<?php echo esc_attr($producer_id); ?>">
                <?php _e('Order Custom Track', 'custom-track-ordering-system'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Add order modal to footer
     */
    public function add_order_modal_to_footer() {
        // Include the modal on all pages - we'll control visibility with JS/CSS
        include_once(CTOS_PLUGIN_DIR . 'templates/order-form-modal.php');
    }
    
    /**
     * Get producer settings via AJAX
     */
    public function ajax_get_producer_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get producer ID
        $producer_id = isset($_POST['producer_id']) ? intval($_POST['producer_id']) : 0;
        if (!$producer_id) {
            wp_send_json_error('Invalid producer ID');
            return;
        }
        
        // Get producer settings
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        // Ensure base price is valid
        $base_price = !empty($settings->base_price) ? floatval($settings->base_price) : 99.99;
        
        // Format addons as array
        $addons_array = array();
        if (!empty($settings->addons)) {
            $addons_array = json_decode($settings->addons, true);
            if (!is_array($addons_array)) {
                $addons_array = array();
            }
        }
        
        // Debug the data we're about to send
        error_log('CTOS: Producer settings response - Base price: ' . $base_price);
        error_log('CTOS: Producer settings addons: ' . print_r($addons_array, true));
        
        // Send response
        wp_send_json_success(array(
            'base_price' => $base_price,
            'addons' => $addons_array
        ));
    }
    
    /**
     * Create a new order via AJAX
     */
    public function ajax_create_order() {
        // Verify nonce
        if (!isset($_POST['ctos_nonce']) || !wp_verify_nonce($_POST['ctos_nonce'], 'ctos-nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get producer ID
        $producer_id = isset($_POST['producer_id']) ? intval($_POST['producer_id']) : 0;
        if (!$producer_id) {
            wp_send_json_error('Invalid producer ID');
            return;
        }
        
        // Get customer ID
        $customer_id = get_current_user_id();
        if (!$customer_id && !isset($_POST['email'])) {
            wp_send_json_error('You must be logged in or provide an email address');
            return;
        }
        
        // Validate required fields
        $required_fields = array('track_title', 'genre', 'description');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error('Please fill in all required fields');
                return;
            }
        }
        
        // Get form data
        $track_title = sanitize_text_field($_POST['track_title']);
        $genre = sanitize_text_field($_POST['genre']);
        $description = sanitize_textarea_field($_POST['description']);
        $reference_tracks = isset($_POST['reference_tracks']) ? sanitize_textarea_field($_POST['reference_tracks']) : '';
        
        // Get producer settings for pricing
        $producer_settings = new CTOS_Producer_Settings();
        $settings = $producer_settings->get_producer_settings($producer_id);
        
        // Calculate price - ensure we have a valid base price
        $base_price = !empty($settings->base_price) ? floatval($settings->base_price) : 99.99;
        $total_price = $base_price;
        
        // Process addons
        $selected_addons = array();
        if (isset($_POST['addons']) && is_array($_POST['addons'])) {
            $addons = json_decode($settings->addons, true);
            if (is_array($addons)) {
                foreach ($_POST['addons'] as $addon_name) {
                    foreach ($addons as $addon) {
                        if ($addon['name'] === $addon_name) {
                            $selected_addons[] = $addon;
                            $total_price += floatval($addon['price']);
                            break;
                        }
                    }
                }
            }
        }
        
        // Calculate deposit amount (30% of total)
        $deposit_amount = round($total_price * 0.3, 2);
        
        // Log the price calculations
        error_log('CTOS: Order Creation - Base price: ' . $base_price);
        error_log('CTOS: Order Creation - Total price: ' . $total_price);
        error_log('CTOS: Order Creation - Deposit amount: ' . $deposit_amount);
        
        // Get our custom track order product
        $product_id = $this->get_track_order_product();
        if (!$product_id) {
            wp_send_json_error('Track order product not found');
            return;
        }
        
        // Add to cart with custom data
        $cart_item_data = array(
            '_ctos_track_order' => true,
            '_ctos_producer_id' => $producer_id,
            '_ctos_customer_id' => $customer_id,
            '_ctos_track_title' => $track_title,
            '_ctos_genre' => $genre,
            '_ctos_description' => $description,
            '_ctos_reference_tracks' => $reference_tracks,
            '_ctos_selected_addons' => $selected_addons,
            '_ctos_base_price' => $base_price,
            '_ctos_total_price' => $total_price,
            '_ctos_deposit_amount' => $deposit_amount,
            '_ctos_order_type' => 'deposit'
        );
        
        // Clear cart first to avoid issues
        WC()->cart->empty_cart();
        
        // Add product to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        if ($cart_item_key) {
            // Process file uploads if present
            if (!empty($_FILES['reference_files'])) {
                // We would save the file info in the session to retrieve later
                // This is just a placeholder - actual file handling would depend on your setup
                WC()->session->set('ctos_reference_files_' . $cart_item_key, true);
            }
            
            // Set cart product price to deposit amount
            foreach (WC()->cart->get_cart() as $key => $cart_item) {
                if ($key === $cart_item_key) {
                    // Force update the price
                    $cart_item['data']->set_price($deposit_amount);
                    $cart_item['data']->set_regular_price($deposit_amount);
                    $cart_item['data']->set_sale_price($deposit_amount);
                    
                    // Directly update WC cart contents
                    WC()->cart->cart_contents[$key]['data']->set_price($deposit_amount);
                    WC()->cart->cart_contents[$key]['data']->set_regular_price($deposit_amount);
                    WC()->cart->cart_contents[$key]['data']->set_sale_price($deposit_amount);
                    break;
                }
            }
            
            // Force cart recalculation
            WC()->cart->calculate_totals();
            
            // Return success with redirect to checkout
            wp_send_json_success(array(
                'redirect' => wc_get_checkout_url()
            ));
        } else {
            wp_send_json_error('Failed to add product to cart');
        }
    }
    
    /**
     * Get or create the track order product
     */
    private function get_track_order_product() {
        // First, check if we already have a product
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_ctos_track_order_product',
                    'value' => 1,
                    'compare' => '='
                )
            )
        );
        
        $products = get_posts($args);
        
        if (!empty($products)) {
            return $products[0]->ID;
        }
        
        // No product found, create one
        $product = new WC_Product_Simple();
        $product->set_name('Custom Track Order');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_description('This is a custom track order product.');
        $product->set_virtual(true);
        $product->set_price(99.99); // Set a default price
        $product->set_regular_price(99.99);
        $product->set_sold_individually(true);
        
        // Save the product
        $product_id = $product->save();
        
        // Set the meta to identify this product
        update_post_meta($product_id, '_ctos_track_order_product', 1);
        
        return $product_id;
    }
}
