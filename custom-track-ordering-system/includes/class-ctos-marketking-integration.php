<?php
/**
 * Integration with MarketKing plugin.
 */
class CTOS_MarketKing_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // No actions needed in constructor
    }
    
    /**
     * Check if a user is a producer (vendor)
     */
    public static function is_producer($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if MarketKing is active
        if (!class_exists('MarketKing')) {
            // Fallback to WooCommerce vendor role check
            $user = get_userdata($user_id);
            return ($user && in_array('seller', (array) $user->roles));
        }
        
        // Use MarketKing function if available
        if (function_exists('marketking_is_vendor')) {
            return marketking_is_vendor($user_id);
        }
        
        // Fallback to meta field check
        $is_vendor = get_user_meta($user_id, 'marketking_is_vendor', true);
        return !empty($is_vendor);
    }
    
    /**
     * Get all producers (vendors)
     */
    public static function get_all_producers() {
        // Check if MarketKing is active
        if (!class_exists('MarketKing')) {
            // Fallback to WooCommerce vendor role query
            $args = array(
                'role' => 'seller',
                'fields' => 'ID',
            );
            
            return get_users($args);
        }
        
        // Use MarketKing function if available
        if (function_exists('marketking_get_all_vendors')) {
            return marketking_get_all_vendors();
        }
        
        // Fallback to meta query
        $args = array(
            'meta_key' => 'marketking_is_vendor',
            'meta_value' => '1',
            'fields' => 'ID',
        );
        
        return get_users($args);
    }
    
    /**
     * Get producer orders
     */
    public static function get_producer_orders($producer_id) {
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") != $meta_table) {
            return array();
        }
        
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE producer_id = %d ORDER BY created_at DESC",
            $producer_id
        ));
        
        return $orders;
    }
    
    /**
     * Get customer orders
     */
    public static function get_customer_orders($customer_id) {
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") != $meta_table) {
            return array();
        }
        
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE customer_id = %d ORDER BY created_at DESC",
            $customer_id
        ));
        
        return $orders;
    }
    
    /**
     * Get producer profile URL
     */
    public static function get_producer_profile_url($producer_id) {
        // Check if MarketKing is active
        if (!class_exists('MarketKing')) {
            // Fallback to author page
            return get_author_posts_url($producer_id);
        }
        
        // Use MarketKing function if available
        if (function_exists('marketking_get_store_link')) {
            return marketking_get_store_link($producer_id);
        }
        
        // Fallback to author page
        return get_author_posts_url($producer_id);
    }
}
