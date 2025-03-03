<?php
/**
 * Handles file uploads and downloads for the custom track ordering system.
 */
class CTOS_File_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_file_upload_handlers'));
        add_action('init', array($this, 'handle_file_downloads'));
        add_action('wp_ajax_ctos_upload_reference_tracks', array($this, 'ajax_upload_reference_tracks'));
    }
    
    /**
     * Register file upload handlers
     */
    public function register_file_upload_handlers() {
        // Set up directories
        $upload_dir = wp_upload_dir();
        $ctos_dir = $upload_dir['basedir'] . '/ctos_files/';
        
        // Create base directory if it doesn't exist
        if (!file_exists($ctos_dir)) {
            wp_mkdir_p($ctos_dir);
            
            // Create an index.php file to prevent directory listing
            file_put_contents($ctos_dir . 'index.php', '<?php // Silence is golden');
            
            // Create .htaccess to protect the directory
            $htaccess_content = "
            <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{HTTP_REFERER} !^" . get_site_url() . " [NC]
            RewriteRule .* - [F]
            </IfModule>
            ";
            file_put_contents($ctos_dir . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Handle secure file downloads
     */
    public function handle_file_downloads() {
        if (isset($_GET['ctos_download']) && isset($_GET['file_id']) && isset($_GET['order_id']) && isset($_GET['nonce'])) {
            $file_id = sanitize_text_field($_GET['file_id']);
            $order_id = intval($_GET['order_id']);
            $nonce = sanitize_text_field($_GET['nonce']);
            
            // Verify nonce
            if (!wp_verify_nonce($nonce, 'ctos_download_' . $file_id . '_' . $order_id)) {
                wp_die(__('Security check failed', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 403));
            }
            
            // Check if user has permission to download this file
            $current_user_id = get_current_user_id();
            if (!$current_user_id) {
                wp_die(__('You must be logged in to download files', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 403));
            }
            
            global $wpdb;
            $meta_table = $wpdb->prefix . 'ctos_order_meta';
            $order_meta = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $meta_table WHERE order_id = %d",
                $order_id
            ));
            
            if (!$order_meta || ($current_user_id != $order_meta->customer_id && $current_user_id != $order_meta->producer_id)) {
                wp_die(__('You do not have permission to download this file', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 403));
            }
            
            // Determine file type (demo, final, reference)
            $file_type = isset($_GET['file_type']) ? sanitize_text_field($_GET['file_type']) : 'final';
            $files_json = '';
            
            switch ($file_type) {
                case 'demo':
                    $file_url = $order_meta->demo_url;
                    break;
                    
                case 'final':
                    $files_json = $order_meta->final_files;
                    break;
                    
                case 'reference':
                    $files_json = $order_meta->reference_tracks;
                    break;
                    
                default:
                    wp_die(__('Invalid file type', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 400));
            }
            
            // For multiple files, find the right one
            if (!empty($files_json) && $file_type !== 'demo') {
                $files = json_decode($files_json, true);
                $file_url = '';
                
                foreach ($files as $file) {
                    if (isset($file['id']) && $file['id'] == $file_id) {
                        $file_url = $file['url'];
                        break;
                    } elseif (isset($file['name']) && sanitize_title($file['name']) == $file_id) {
                        $file_url = $file['url'];
                        break;
                    }
                }
                
                if (empty($file_url)) {
                    wp_die(__('File not found', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 404));
                }
            }
            
            // Get the file path from URL
            $file_path = $this->get_file_path_from_url($file_url);
            
            if (!file_exists($file_path)) {
                wp_die(__('File not found', 'custom-track-ordering-system'), __('Error', 'custom-track-ordering-system'), array('response' => 404));
            }
            
            // Get file info
            $file_name = basename($file_path);
            $file_size = filesize($file_path);
            $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
            
            // Set appropriate content type
            $content_types = array(
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'aif' => 'audio/aiff',
                'aiff' => 'audio/aiff',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'pdf' => 'application/pdf',
                'mid' => 'audio/midi',
                'midi' => 'audio/midi',
                'flac' => 'audio/flac',
            );
            
            $content_type = isset($content_types[$file_ext]) ? $content_types[$file_ext] : 'application/octet-stream';
            
            // Set headers and output file
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Length: ' . $file_size);
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            
            readfile($file_path);
            exit;
        }
    }
    
    /**
     * Get file path from URL
     */
    private function get_file_path_from_url($url) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];
        
        // Replace the base URL with the base directory path
        return str_replace($base_url, $base_dir, $url);
    }
    
    /**
     * Upload reference tracks via AJAX
     */
    public function ajax_upload_reference_tracks() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ctos-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Validate data
        if (empty($_FILES)) {
            wp_send_json_error('No files uploaded');
        }
        
        // Create a temporary order ID
        $temp_order_id = 'temp_' . get_current_user_id() . '_' . time();
        
        // Set up directories
        $upload_dir = wp_upload_dir();
        $reference_dir = $upload_dir['basedir'] . '/ctos_files/' . $temp_order_id . '/references/';
        
        // Create directory if it doesn't exist
        wp_mkdir_p($reference_dir);
        
        $uploaded_files = array();
        
        // Process each uploaded file
        foreach ($_FILES as $key => $file) {
            // Validate file type
            $file_type = wp_check_filetype($file['name'], array(
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'aif' => 'audio/aiff',
                'aiff' => 'audio/aiff',
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'pdf' => 'application/pdf',
            ));
            
            if (!$file_type['type']) {
                continue; // Skip invalid file types
            }
            
            // Generate a unique filename
            $filename = sanitize_file_name($file['name']);
            $file_path = $reference_dir . $filename;
            
            // Move the uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_files[] = array(
                    'id' => md5($filename . time()),
                    'name' => $filename,
                    'url' => $upload_dir['baseurl'] . '/ctos_files/' . $temp_order_id . '/references/' . $filename,
                    'type' => $file_type['type']
                );
            }
        }
        
        if (empty($uploaded_files)) {
            wp_send_json_error('Failed to upload files');
        }
        
        // Store the uploaded files in a transient for later use
        set_transient('ctos_reference_tracks_' . get_current_user_id(), json_encode($uploaded_files), HOUR_IN_SECONDS);
        
        wp_send_json_success(array(
            'files' => $uploaded_files,
            'temp_order_id' => $temp_order_id
        ));
    }
    
    /**
     * Generate download URL with security nonce
     */
    public static function get_download_url($file_id, $order_id, $file_type = 'final') {
        $nonce = wp_create_nonce('ctos_download_' . $file_id . '_' . $order_id);
        return add_query_arg(
            array(
                'ctos_download' => 1,
                'file_id' => $file_id,
                'order_id' => $order_id,
                'file_type' => $file_type,
                'nonce' => $nonce
            ),
            home_url()
        );
    }
}
