<?php
/**
 * Handles producer settings for custom track orders.
 */
class CTOS_Producer_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Ajax handler for saving producer settings
        add_action('wp_ajax_ctos_save_producer_settings', array($this, 'ajax_save_producer_settings'));
    }
    
    /**
     * Get producer settings
     * 
     * @param int $producer_id
     * @return object
     */
    public function get_producer_settings($producer_id) {
        // Get settings from user meta
        $enable_custom_orders = get_user_meta($producer_id, '_ctos_enable_custom_orders', true);
        $genres = get_user_meta($producer_id, '_ctos_genres', true);
        $daw_compatibility = get_user_meta($producer_id, '_ctos_daw_compatibility', true);
        $similar_artists = get_user_meta($producer_id, '_ctos_similar_artists', true);
        $base_price = get_user_meta($producer_id, '_ctos_base_price', true);
        $delivery_time = get_user_meta($producer_id, '_ctos_delivery_time', true);
        $revisions = get_user_meta($producer_id, '_ctos_revisions', true);
        $addons = get_user_meta($producer_id, '_ctos_addons', true);
        
        // Set default values
        if (empty($base_price)) {
            $base_price = 99.99;
        }
        
        if (empty($delivery_time)) {
            $delivery_time = 7;
        }
        
        if (empty($revisions)) {
            $revisions = 3;
        }
        
        // Create settings object
        $settings = new stdClass();
        $settings->enable_custom_orders = ($enable_custom_orders === 'yes') ? 1 : 0;
        $settings->genres = $genres;
        $settings->daw_compatibility = $daw_compatibility;
        $settings->similar_artists = $similar_artists;
        $settings->base_price = floatval($base_price);
        $settings->delivery_time = intval($delivery_time);
        $settings->revisions = intval($revisions);
        $settings->addons = $addons;
        
        // Parse addons into array for template use
        $settings->addons_array = array();
        if (!empty($addons)) {
            $decoded = json_decode($addons, true);
            if (is_array($decoded)) {
                $settings->addons_array = $decoded;
            }
        }
        
        return $settings;
    }
    
    /**
     * Save producer settings via AJAX
     */
    public function ajax_save_producer_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check if user is producer
        $user_id = get_current_user_id();
        if (!$user_id || !CTOS_MarketKing_Integration::is_producer($user_id)) {
            wp_send_json_error('You do not have permission to save these settings');
            return;
        }
        
        // Debug to help diagnose form data
        error_log('CTOS: Saving producer settings - Form data: ' . print_r($_POST, true));
        
        // Get and sanitize form data
        $enable_custom_orders = isset($_POST['enable_custom_orders']) ? 'yes' : 'no';
        $genres = isset($_POST['genres']) ? sanitize_text_field($_POST['genres']) : '';
        $daw_compatibility = isset($_POST['daw_compatibility']) ? sanitize_text_field($_POST['daw_compatibility']) : '';
        $similar_artists = isset($_POST['similar_artists']) ? sanitize_text_field($_POST['similar_artists']) : '';
        $base_price = isset($_POST['base_price']) ? floatval($_POST['base_price']) : 99.99;
        $delivery_time = isset($_POST['delivery_time']) ? intval($_POST['delivery_time']) : 7;
        $revisions = isset($_POST['revisions']) ? intval($_POST['revisions']) : 3;
        
        // Process addons
        $addons = array();
        if (isset($_POST['addons']) && is_array($_POST['addons'])) {
            foreach ($_POST['addons'] as $addon) {
                if (!empty($addon['name']) && isset($addon['price'])) {
                    $addons[] = array(
                        'name' => sanitize_text_field($addon['name']),
                        'price' => floatval($addon['price'])
                    );
                }
            }
        }
        
        // Save all settings
        update_user_meta($user_id, '_ctos_enable_custom_orders', $enable_custom_orders);
        update_user_meta($user_id, '_ctos_genres', $genres);
        update_user_meta($user_id, '_ctos_daw_compatibility', $daw_compatibility);
        update_user_meta($user_id, '_ctos_similar_artists', $similar_artists);
        update_user_meta($user_id, '_ctos_base_price', $base_price);
        update_user_meta($user_id, '_ctos_delivery_time', $delivery_time);
        update_user_meta($user_id, '_ctos_revisions', $revisions);
        update_user_meta($user_id, '_ctos_addons', json_encode($addons));
        
        // Return success
        wp_send_json_success('Settings saved successfully');
    }
}
