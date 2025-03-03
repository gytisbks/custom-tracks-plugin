<?php
/**
 * Class to register custom post types and taxonomies.
 */
class CTOS_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            // Register post types and taxonomies on init
            add_action('init', array($this, 'register_post_types'), 10);
            add_action('init', array($this, 'register_taxonomies'), 9);
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('CTOS_Post_Types initialized');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error initializing CTOS_Post_Types: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        try {
            // Register track order post type
            $labels = array(
                'name'                  => _x('Track Orders', 'Post type general name', 'custom-track-ordering-system'),
                'singular_name'         => _x('Track Order', 'Post type singular name', 'custom-track-ordering-system'),
                'menu_name'             => _x('Track Orders', 'Admin Menu text', 'custom-track-ordering-system'),
                'name_admin_bar'        => _x('Track Order', 'Add New on Toolbar', 'custom-track-ordering-system'),
                'add_new'               => __('Add New', 'custom-track-ordering-system'),
                'add_new_item'          => __('Add New Track Order', 'custom-track-ordering-system'),
                'new_item'              => __('New Track Order', 'custom-track-ordering-system'),
                'edit_item'             => __('Edit Track Order', 'custom-track-ordering-system'),
                'view_item'             => __('View Track Order', 'custom-track-ordering-system'),
                'all_items'             => __('All Track Orders', 'custom-track-ordering-system'),
                'search_items'          => __('Search Track Orders', 'custom-track-ordering-system'),
                'not_found'             => __('No track orders found.', 'custom-track-ordering-system'),
                'not_found_in_trash'    => __('No track orders found in Trash.', 'custom-track-ordering-system'),
                'featured_image'        => _x('Track Order Cover Image', 'Overrides the "Featured Image" phrase', 'custom-track-ordering-system'),
                'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'custom-track-ordering-system'),
                'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'custom-track-ordering-system'),
                'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'custom-track-ordering-system'),
                'archives'              => _x('Track Order archives', 'The post type archive label used in nav menus', 'custom-track-ordering-system'),
                'insert_into_item'      => _x('Insert into track order', 'Overrides the "Insert into post" phrase', 'custom-track-ordering-system'),
                'uploaded_to_this_item' => _x('Uploaded to this track order', 'Overrides the "Uploaded to this post" phrase', 'custom-track-ordering-system'),
                'filter_items_list'     => _x('Filter track orders list', 'Screen reader text for the filter links', 'custom-track-ordering-system'),
                'items_list_navigation' => _x('Track orders list navigation', 'Screen reader text for the pagination', 'custom-track-ordering-system'),
                'items_list'            => _x('Track orders list', 'Screen reader text for the items list', 'custom-track-ordering-system'),
            );
            
            $args = array(
                'labels'             => $labels,
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array('slug' => 'track-order'),
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
                'menu_icon'          => 'dashicons-playlist-audio',
            );
            
            register_post_type('ctos_track_order', $args);
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Post types registered successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error registering post types: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        try {
            // Register service type taxonomy
            $labels = array(
                'name'              => _x('Service Types', 'taxonomy general name', 'custom-track-ordering-system'),
                'singular_name'     => _x('Service Type', 'taxonomy singular name', 'custom-track-ordering-system'),
                'search_items'      => __('Search Service Types', 'custom-track-ordering-system'),
                'all_items'         => __('All Service Types', 'custom-track-ordering-system'),
                'parent_item'       => __('Parent Service Type', 'custom-track-ordering-system'),
                'parent_item_colon' => __('Parent Service Type:', 'custom-track-ordering-system'),
                'edit_item'         => __('Edit Service Type', 'custom-track-ordering-system'),
                'update_item'       => __('Update Service Type', 'custom-track-ordering-system'),
                'add_new_item'      => __('Add New Service Type', 'custom-track-ordering-system'),
                'new_item_name'     => __('New Service Type Name', 'custom-track-ordering-system'),
                'menu_name'         => __('Service Types', 'custom-track-ordering-system'),
            );
            
            $args = array(
                'hierarchical'      => true,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array('slug' => 'service-type'),
            );
            
            register_taxonomy('ctos_service_type', array('ctos_track_order'), $args);
            
            // Register genre taxonomy
            $labels = array(
                'name'              => _x('Genres', 'taxonomy general name', 'custom-track-ordering-system'),
                'singular_name'     => _x('Genre', 'taxonomy singular name', 'custom-track-ordering-system'),
                'search_items'      => __('Search Genres', 'custom-track-ordering-system'),
                'all_items'         => __('All Genres', 'custom-track-ordering-system'),
                'parent_item'       => __('Parent Genre', 'custom-track-ordering-system'),
                'parent_item_colon' => __('Parent Genre:', 'custom-track-ordering-system'),
                'edit_item'         => __('Edit Genre', 'custom-track-ordering-system'),
                'update_item'       => __('Update Genre', 'custom-track-ordering-system'),
                'add_new_item'      => __('Add New Genre', 'custom-track-ordering-system'),
                'new_item_name'     => __('New Genre Name', 'custom-track-ordering-system'),
                'menu_name'         => __('Genres', 'custom-track-ordering-system'),
            );
            
            $args = array(
                'hierarchical'      => false,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array('slug' => 'genre'),
            );
            
            register_taxonomy('ctos_genre', array('ctos_track_order'), $args);
            
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Taxonomies registered successfully');
            }
        } catch (Exception $e) {
            if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
                error_log('Error registering taxonomies: ' . $e->getMessage());
            }
        }
    }
}
