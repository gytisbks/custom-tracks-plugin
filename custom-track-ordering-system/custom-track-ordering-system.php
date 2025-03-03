<?php
/**
 * Plugin Name: Custom Track Ordering System
 * Plugin URI: https://example.com/custom-track-ordering-system
 * Description: A system for music producers to accept custom track orders from customers.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: custom-track-ordering-system
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CTOS_VERSION', '1.0.0');
define('CTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTOS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTOS_PLUGIN_FILE', __FILE__);

/**
 * The code that runs during plugin activation.
 */
function activate_ctos() {
    require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-activator.php';
    CTOS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ctos() {
    // Future deactivation code
}

register_activation_hook(__FILE__, 'activate_ctos');
register_deactivation_hook(__FILE__, 'deactivate_ctos');

/**
 * Load plugin classes
 */
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-loader.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-post-types.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-producer-settings.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-order-form.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-order-workflow.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-file-handler.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-notifications.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-marketking-integration.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-woocommerce-integration.php';
require_once CTOS_PLUGIN_DIR . 'includes/class-ctos-shortcodes.php';
require_once CTOS_PLUGIN_DIR . 'admin/class-ctos-admin.php';

/**
 * Initialize the core plugin classes.
 */
function run_ctos() {
    // Initialize classes
    $post_types = new CTOS_Post_Types();
    $producer_settings = new CTOS_Producer_Settings();
    $order_form = new CTOS_Order_Form();
    $order_workflow = new CTOS_Order_Workflow();
    $file_handler = new CTOS_File_Handler();
    $notifications = new CTOS_Notifications();
    $marketking_integration = new CTOS_MarketKing_Integration();
    $woocommerce_integration = new CTOS_WooCommerce_Integration();
    $shortcodes = new CTOS_Shortcodes();
    $admin = new CTOS_Admin();
    
    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'ctos_enqueue_scripts');
    add_action('admin_enqueue_scripts', 'ctos_enqueue_admin_scripts');
}

/**
 * Enqueue public scripts and styles
 */
function ctos_enqueue_scripts() {
    // CSS
    wp_enqueue_style('ctos-public', CTOS_PLUGIN_URL . 'assets/css/public.css', array(), CTOS_VERSION);
    
    // JavaScript
    wp_enqueue_script('ctos-public', CTOS_PLUGIN_URL . 'assets/js/public.js', array('jquery'), CTOS_VERSION, true);
    
    // Localize script
    wp_localize_script('ctos-public', 'ctos_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ctos-nonce')
    ));
}

/**
 * Enqueue admin scripts and styles
 */
function ctos_enqueue_admin_scripts() {
    // CSS
    wp_enqueue_style('ctos-admin', CTOS_PLUGIN_URL . 'admin/css/admin.css', array(), CTOS_VERSION);
    
    // JavaScript
    wp_enqueue_script('ctos-admin', CTOS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), CTOS_VERSION, true);
    
    // Localize script
    wp_localize_script('ctos-admin', 'ctos_admin_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ctos-admin-nonce')
    ));
}

// Run the plugin
run_ctos();
