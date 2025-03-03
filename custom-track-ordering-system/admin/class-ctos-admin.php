<?php
/**
 * The admin-specific functionality of the plugin.
 */
class CTOS_Admin {
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_ctos_track_order_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_ctos_track_order_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style('ctos-admin', CTOS_PLUGIN_URL . 'admin/css/admin.css', array(), CTOS_VERSION, 'all');
    }
    
    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('ctos-admin', CTOS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), CTOS_VERSION, false);
        
        wp_localize_script('ctos-admin', 'ctos_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ctos-admin-nonce'),
        ));
    }
    
    /**
     * Add menu items to the admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Track Orders', 'custom-track-ordering-system'),
            __('Track Orders', 'custom-track-ordering-system'),
            'manage_options',
            'ctos-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-playlist-audio',
            56
        );
        
        add_submenu_page(
            'ctos-dashboard',
            __('Dashboard', 'custom-track-ordering-system'),
            __('Dashboard', 'custom-track-ordering-system'),
            'manage_options',
            'ctos-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'ctos-dashboard',
            __('Settings', 'custom-track-ordering-system'),
            __('Settings', 'custom-track-ordering-system'),
            'manage_options',
            'ctos-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'ctos-dashboard',
            __('Service Types', 'custom-track-ordering-system'),
            __('Service Types', 'custom-track-ordering-system'),
            'manage_options',
            'edit-tags.php?taxonomy=ctos_service_type&post_type=ctos_producer_service'
        );
        
        add_submenu_page(
            'ctos-dashboard',
            __('Genres', 'custom-track-ordering-system'),
            __('Genres', 'custom-track-ordering-system'),
            'manage_options',
            'edit-tags.php?taxonomy=ctos_genre&post_type=ctos_producer_service'
        );
    }
    
    /**
     * Add meta boxes to the track order custom post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ctos_order_details',
            __('Track Order Details', 'custom-track-ordering-system'),
            array($this, 'render_order_details_meta_box'),
            'ctos_track_order',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ctos_order_workflow',
            __('Order Workflow', 'custom-track-ordering-system'),
            array($this, 'render_order_workflow_meta_box'),
            'ctos_track_order',
            'side',
            'high'
        );
    }
    
    /**
     * Render the order details meta box.
     */
    public function render_order_details_meta_box($post) {
        $order_id = $post->ID;
        
        // Get order data
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta) {
            echo '<p>' . __('No custom track order data found.', 'custom-track-ordering-system') . '</p>';
            return;
        }
        
        // Get producer and customer names
        $producer = get_user_by('id', $order_meta->producer_id);
        $customer = get_user_by('id', $order_meta->customer_id);
        
        // Include the template
        include CTOS_PLUGIN_DIR . 'admin/partials/order-details-meta-box.php';
    }
    
    /**
     * Render the order workflow meta box.
     */
    public function render_order_workflow_meta_box($post) {
        $order_id = $post->ID;
        
        // Get order data
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$order_meta) {
            echo '<p>' . __('No custom track order data found.', 'custom-track-ordering-system') . '</p>';
            return;
        }
        
        // Include the template
        include CTOS_PLUGIN_DIR . 'admin/partials/order-workflow-meta-box.php';
    }
    
    /**
     * Set custom columns for track order list.
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['producer'] = __('Producer', 'custom-track-ordering-system');
        $new_columns['customer'] = __('Customer', 'custom-track-ordering-system');
        $new_columns['service_type'] = __('Service Type', 'custom-track-ordering-system');
        $new_columns['status'] = __('Status', 'custom-track-ordering-system');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Display custom column content.
     */
    public function custom_column_content($column, $post_id) {
        // Get order data
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $post_id
        ));
        
        if (!$order_meta) {
            echo '<span class="na">&ndash;</span>';
            return;
        }
        
        switch ($column) {
            case 'producer':
                $producer = get_user_by('id', $order_meta->producer_id);
                if ($producer) {
                    echo '<a href="' . esc_url(get_edit_user_link($producer->ID)) . '">' . esc_html($producer->display_name) . '</a>';
                } else {
                    echo '<span class="na">' . __('Unknown', 'custom-track-ordering-system') . '</span>';
                }
                break;
                
            case 'customer':
                $customer = get_user_by('id', $order_meta->customer_id);
                if ($customer) {
                    echo '<a href="' . esc_url(get_edit_user_link($customer->ID)) . '">' . esc_html($customer->display_name) . '</a>';
                } else {
                    echo '<span class="na">' . __('Unknown', 'custom-track-ordering-system') . '</span>';
                }
                break;
                
            case 'service_type':
                echo esc_html(ucfirst(str_replace('_', ' ', $order_meta->service_type)));
                break;
                
            case 'status':
                $status_label = CTOS_MarketKing_Integration::get_status_label($order_meta->status);
                $status_class = CTOS_MarketKing_Integration::get_status_class($order_meta->status);
                echo '<mark class="order-status ' . esc_attr($status_class) . '"><span>' . esc_html($status_label) . '</span></mark>';
                break;
        }
    }
    
    /**
     * Render the dashboard admin page.
     */
    public function render_dashboard_page() {
        include CTOS_PLUGIN_DIR . 'admin/partials/dashboard-page.php';
    }
    
    /**
     * Render the settings admin page.
     */
    public function render_settings_page() {
        include CTOS_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
    
    /**
     * Get order statistics
     */
    public function get_order_statistics() {
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        // Total orders
        $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table");
        
        // Orders by status
        $pending_demo = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table WHERE status = 'pending_demo_submission'");
        $awaiting_approval = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table WHERE status = 'awaiting_customer_approval'");
        $awaiting_payment = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table WHERE status = 'awaiting_final_payment'");
        $awaiting_delivery = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table WHERE status = 'awaiting_final_delivery'");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM $meta_table WHERE status = 'completed'");
        
        // Total revenue
        $total_revenue = $wpdb->get_var("
            SELECT SUM(pm.meta_value)
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_order_total'
            AND p.ID IN (
                SELECT DISTINCT order_id FROM $meta_table
            )
        ");
        
        // Producers count
        $producers_count = $wpdb->get_var("SELECT COUNT(DISTINCT producer_id) FROM $meta_table");
        
        // Customers count
        $customers_count = $wpdb->get_var("SELECT COUNT(DISTINCT customer_id) FROM $meta_table");
        
        return array(
            'total_orders' => $total_orders,
            'pending_demo' => $pending_demo,
            'awaiting_approval' => $awaiting_approval,
            'awaiting_payment' => $awaiting_payment,
            'awaiting_delivery' => $awaiting_delivery,
            'completed' => $completed,
            'total_revenue' => $total_revenue,
            'producers_count' => $producers_count,
            'customers_count' => $customers_count
        );
    }
    
    /**
     * Get recent orders
     */
    public function get_recent_orders($limit = 5) {
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        $orders = $wpdb->get_results("
            SELECT * FROM $meta_table
            ORDER BY created_at DESC
            LIMIT $limit
        ");
        
        return $orders;
    }
}
