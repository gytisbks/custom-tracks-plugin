<?php
/**
 * Class for the deposit payment email.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('CTOS_Deposit_Payment_Email')) :

/**
 * Deposit Payment Email
 *
 * Email sent to producers when a customer makes the initial deposit payment.
 */
class CTOS_Deposit_Payment_Email extends WC_Email {
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ctos_deposit_payment';
        $this->title = __('Custom Track Deposit Payment', 'custom-track-ordering-system');
        $this->description = __('This email is sent to producers when a customer makes a deposit payment for a custom track.', 'custom-track-ordering-system');
        
        $this->template_html = 'emails/deposit-payment.php';
        $this->template_plain = 'emails/plain/deposit-payment.php';
        $this->template_base = CTOS_PLUGIN_DIR . 'templates/';
        
        $this->subject = __('New custom track order {order_number} - deposit payment received', 'custom-track-ordering-system');
        $this->heading = __('New custom track order', 'custom-track-ordering-system');
        
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
        
        // Call parent constructor
        parent::__construct();
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
     * @param int $order_id The order ID.
     * @param WC_Order $order Order object.
     */
    public function trigger($order_id, $order = false) {
        $this->setup_locale();
        
        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        
        if (is_a($order, 'WC_Order')) {
            $this->object = $order;
            $this->placeholders['{order_number}'] = $order->get_order_number();
            
            // Look for producer's email
            $producer_id = false;
            foreach ($order->get_items() as $item) {
                $producer_id = $item->get_meta('_ctos_producer_id');
                if ($producer_id) {
                    break;
                }
            }
            
            if ($producer_id) {
                $producer = get_user_by('id', $producer_id);
                if ($producer) {
                    $this->recipient = $producer->user_email;
                }
            }
        }
        
        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
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
            'order'         => $this->object,
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
            'order'         => $this->object,
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
