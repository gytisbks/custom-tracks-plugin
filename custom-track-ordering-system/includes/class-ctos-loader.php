<?php
/**
 * The core loader class.
 */
class CTOS_Loader {
    
    /**
     * The array of actions registered with WordPress.
     */
    protected $actions;
    
    /**
     * The array of filters registered with WordPress.
     */
    protected $filters;
    
    /**
     * Initialize the collections used to maintain the actions and filters.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        
        // Enable error handling
        try {
            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('CTOS_Loader initialized successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error initializing CTOS_Loader: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        try {
            // Initialize core classes safely with individual try-catch blocks
            $this->safe_initialize('CTOS_Post_Types');
            $this->safe_initialize('CTOS_Producer_Settings');
            $this->safe_initialize('CTOS_Order_Form');
            $this->safe_initialize('CTOS_Order_Workflow');
            $this->safe_initialize('CTOS_File_Handler');
            $this->safe_initialize('CTOS_Notifications');
            $this->safe_initialize('CTOS_MarketKing_Integration');
            $this->safe_initialize('CTOS_WooCommerce_Integration');
            $this->safe_initialize('CTOS_Shortcodes');
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('CTOS_Loader: Dependencies loaded successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error loading dependencies: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Helper method to safely initialize a class
     */
    private function safe_initialize($class_name) {
        try {
            if (class_exists($class_name)) {
                new $class_name();
                if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                    error_log('Class initialized: ' . $class_name);
                }
            } else {
                if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                    error_log('Class not found: ' . $class_name);
                }
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error initializing ' . $class_name . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register the admin hooks.
     */
    private function define_admin_hooks() {
        try {
            if (is_admin()) {
                if (class_exists('CTOS_Admin')) {
                    $admin = new CTOS_Admin();
                    $this->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
                    $this->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
                    $this->add_action('admin_menu', $admin, 'add_admin_menu');
                    
                    if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                        error_log('Admin hooks registered successfully');
                    }
                } else {
                    if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                        error_log('CTOS_Admin class not found');
                    }
                }
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error defining admin hooks: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register the public hooks.
     */
    private function define_public_hooks() {
        try {
            $this->add_action('wp_enqueue_scripts', $this, 'enqueue_styles');
            $this->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts');
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Public hooks registered successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error defining public hooks: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Enqueue public styles.
     */
    public function enqueue_styles() {
        try {
            wp_enqueue_style('ctos-public-styles', CTOS_PLUGIN_URL . 'assets/css/public.css', array(), CTOS_VERSION, 'all');
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Public styles enqueued successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error enqueuing styles: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Enqueue public scripts.
     */
    public function enqueue_scripts() {
        try {
            wp_enqueue_script('ctos-public-scripts', CTOS_PLUGIN_URL . 'assets/js/public.js', array('jquery'), CTOS_VERSION, false);
            
            // Add localization data for JavaScript
            wp_localize_script('ctos-public-scripts', 'ctos_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ctos-nonce'),
            ));
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Public scripts enqueued successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error enqueuing scripts: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Add a new action to the collection to be registered with WordPress.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a new filter to the collection to be registered with WordPress.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    /**
     * Register the filters and actions with WordPress.
     */
    public function run() {
        try {
            // Register filters
            foreach ($this->filters as $hook) {
                try {
                    add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
                } catch (Exception $e) {
                    if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                        error_log('Error adding filter ' . $hook['hook'] . ': ' . $e->getMessage());
                    }
                }
            }
            
            // Register actions
            foreach ($this->actions as $hook) {
                try {
                    add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
                } catch (Exception $e) {
                    if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                        error_log('Error adding action ' . $hook['hook'] . ': ' . $e->getMessage());
                    }
                }
            }
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('CTOS_Loader: All hooks registered successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error running loader: ' . $e->getMessage());
            }
        }
    }
}
