<?php
/**
 * Fired during plugin activation.
 */
class CTOS_Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        global $wpdb;
        
        if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
            error_log('Running CTOS activation...');
        }
        
        // Character set
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create producer settings table
        $producer_settings_table = $wpdb->prefix . 'ctos_producer_settings';
        $sql_producer_settings = "CREATE TABLE IF NOT EXISTS $producer_settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            producer_id bigint(20) NOT NULL,
            enable_custom_orders tinyint(1) NOT NULL DEFAULT 1,
            genres text DEFAULT '',
            daw_compatibility text DEFAULT '',
            similar_artists text DEFAULT '',
            base_price decimal(10,2) NOT NULL DEFAULT 0,
            delivery_time int(11) NOT NULL DEFAULT 7,
            revisions int(11) NOT NULL DEFAULT 3,
            addons text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY producer_id (producer_id)
        ) $charset_collate;";
        
        // Create order meta table
        $order_meta_table = $wpdb->prefix . 'ctos_order_meta';
        $sql_order_meta = "CREATE TABLE IF NOT EXISTS $order_meta_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            producer_id bigint(20) NOT NULL,
            track_name varchar(255) NOT NULL,
            track_description text DEFAULT NULL,
            reference_tracks text DEFAULT NULL,
            initial_deposit decimal(10,2) NOT NULL DEFAULT 0,
            final_payment decimal(10,2) NOT NULL DEFAULT 0,
            total_amount decimal(10,2) NOT NULL DEFAULT 0,
            addons text DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending_demo_submission',
            demo_file varchar(255) DEFAULT NULL,
            final_file varchar(255) DEFAULT NULL,
            feedback text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY producer_id (producer_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Create order files table
        $order_files_table = $wpdb->prefix . 'ctos_order_files';
        $sql_order_files = "CREATE TABLE IF NOT EXISTS $order_files_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size bigint(20) NOT NULL,
            file_path varchar(255) NOT NULL,
            uploaded_by bigint(20) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY file_type (file_type),
            KEY uploaded_by (uploaded_by)
        ) $charset_collate;";
        
        // DB Delta to create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_producer_settings);
        dbDelta($sql_order_meta);
        dbDelta($sql_order_files);
        
        if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
            error_log('CTOS tables created successfully');
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $ctos_dir = $upload_dir['basedir'] . '/ctos';
        
        if (!file_exists($ctos_dir)) {
            wp_mkdir_p($ctos_dir);
            wp_mkdir_p($ctos_dir . '/demos');
            wp_mkdir_p($ctos_dir . '/final');
            
            // Create .htaccess to protect the directory
            $htaccess_file = $ctos_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Protect Directory\n";
                $htaccess_content .= "<Files '*'>\n";
                $htaccess_content .= "    <IfModule mod_authz_core.c>\n";
                $htaccess_content .= "        Require all denied\n";
                $htaccess_content .= "    </IfModule>\n";
                $htaccess_content .= "    <IfModule !mod_authz_core.c>\n";
                $htaccess_content .= "        Order deny,allow\n";
                $htaccess_content .= "        Deny from all\n";
                $htaccess_content .= "    </IfModule>\n";
                $htaccess_content .= "</Files>\n";
                
                @file_put_contents($htaccess_file, $htaccess_content);
            }
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('CTOS upload directories created successfully');
            }
        }
    }
}
