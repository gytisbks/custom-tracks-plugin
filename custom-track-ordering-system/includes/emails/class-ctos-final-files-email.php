<?php
/**
 * Class for the final files email.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('CTOS_Final_Files_Email')) :

/**
 * Final Files Email
 *
 * Email sent to customers when a producer delivers the final track files.
 */
class CTOS_Final_Files_Email extends WC_Email {
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ctos_final_files';
        $this->title = __('Custom Track Final Files', 'custom-track-ordering-system');
        $this->description = __('This email is sent to customers when a producer delivers the final track files.', 'custom-track-ordering-system');
        
        $this->template_html = 'emails/final-files.php';
        $this->template_plain = 'emails/plain/final-files.php';
        $this->template_base = CTOS_PLUGIN_DIR . 'templates/';
        
        $this->subject = __('Your final track files are ready - Order {order_number}', 'custom-track-ordering-system');
        $this->heading = __('Your final track files are ready!', 'custom-track-ordering-system');
        
        // Call parent constructor
        parent::__construct();
        
        // Triggers for this email
        add_action('ctos_order_status_completed', array($this, 'trigger'), 10, 1);
    }
    
    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return $this->subject;
    }
    
    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return $this->heading;
    }
    
    /**
     * Trigger the sending of this email.
     *
     * @param int $order_id The custom track order ID.
     */
    public function trigger($order_id) {
        $this->setup_locale();
        
        global $wpdb;
        $meta_table = $wpdb->prefix . 'ctos_order_meta';
        
        // Get the custom track order details
        $order_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $meta_table WHERE order_id = %d",
            $order_id
        ));
        
        if ($order_meta) {
            $this->object = $order_meta;
            
            // Get the WooCommerce order
            $wc_order = wc_get_order($order_meta->wc_order_id);
            if (!$wc_order) {
                return;
            }
            
            $this->placeholders['{order_number}'] = $wc_order->get_order_number();
            
            // Get the customer's email
            $this->recipient = $wc_order->get_billing_email();
            
            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }
        }
        
        $this->restore_locale();
    }
    
    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html($this->template_html, array(
            'order_meta'    => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ), '', $this->template_base);
    }
    
    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html($this->template_plain, array(
            'order_meta'    => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ), '', $this->template_base);
    }
    
    /**
     * Initialize Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'custom-track-ordering-system'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'custom-track-ordering-system'),
                'default' => 'yes',
            ),
            'subject' => array(
                'title'       => __('Subject', 'custom-track-ordering-system'),
                'type'        => 'text',
                'description' => __('This controls the email subject line. Leave blank to use the default subject.', 'custom-track-ordering-system'),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading' => array(
                'title'       => __('Email Heading', 'custom-track-ordering-system'),
                'type'        => 'text',
                'description' => __('This controls the main heading in the email notification. Leave blank to use the default heading.', 'custom-track-ordering-system'),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'email_type' => array(
                'title'       => __('Email Type', 'custom-track-ordering-system'),
                'type'        => 'select',
                'description' => __('Choose which format of email to send.', 'custom-track-ordering-system'),
                'default'     => 'html',
                'class'       => 'email_type',
                'options'     => array(
                    'plain'     => __('Plain text', 'custom-track-ordering-system'),
                    'html'      => __('HTML', 'custom-track-ordering-system'),
                    'multipart' => __('Multipart', 'custom-track-ordering-system'),
                ),
            ),
        );
    }
}

endif;
