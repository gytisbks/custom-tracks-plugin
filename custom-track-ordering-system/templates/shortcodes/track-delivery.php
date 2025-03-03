<?php
/**
 * Template for the track delivery page shortcode.
 */
defined('ABSPATH') || exit;

// Get order data from query param if set
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Get order data
global $wpdb;
$meta_table = $wpdb->prefix . 'ctos_order_meta';
$order_meta = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $meta_table WHERE order_id = %d",
    $order_id
));

// Check if order exists
if (!$order_meta) {
    echo '<p>' . __('Order not found.', 'custom-track-ordering-system') . '</p>';
    return;
}

// Check permissions
$current_user_id = get_current_user_id();
$is_producer = ($current_user_id == $order_meta->producer_id);
$is_customer = ($current_user_id == $order_meta->customer_id);

if (!$is_producer && !$is_customer) {
    echo '<p>' . __('You do not have permission to view this order.', 'custom-track-ordering-system') . '</p>';
    return;
}

// Get WC order if it exists
$wc_order = wc_get_order($order_id);
$wc_order_status = $wc_order ? $wc_order->get_status() : 'unknown';

// Get status label
$status_label = CTOS_MarketKing_Integration::get_status_label($order_meta->status);
$status_class = CTOS_MarketKing_Integration::get_status_class($order_meta->status);
?>

<div class="ctos-delivery-container">
    <h2><?php printf(__('Track Order #%s', 'custom-track-ordering-system'), $order_id); ?></h2>
    
    <div class="ctos-order-meta">
        <div class="ctos-order-status-wrapper">
            <span class="ctos-order-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
        </div>
        
        <p><strong><?php _e('Created:', 'custom-track-ordering-system'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order_meta->created_at)); ?></p>
        
        <?php if ($is_producer) : ?>
            <p><strong><?php _e('Customer:', 'custom-track-ordering-system'); ?></strong> 
                <?php 
                $customer = get_user_by('id', $order_meta->customer_id);
                echo $customer ? esc_html($customer->display_name) : __('Unknown', 'custom-track-ordering-system');
                ?>
            </p>
        <?php endif; ?>
        
        <?php if ($is_customer) : ?>
            <p><strong><?php _e('Producer:', 'custom-track-ordering-system'); ?></strong> 
                <?php 
                $producer = get_user_by('id', $order_meta->producer_id);
                echo $producer ? esc_html($producer->display_name) : __('Unknown', 'custom-track-ordering-system');
                ?>
            </p>
        <?php endif; ?>
        
        <p><strong><?php _e('Service Type:', 'custom-track-ordering-system'); ?></strong> <?php echo esc_html(ucfirst(str_replace('_', ' ', $order_meta->service_type))); ?></p>
    </div>
    
    <div class="ctos-order-details">
        <h3><?php _e('Order Details', 'custom-track-ordering-system'); ?></h3>
        
        <p><strong><?php _e('Genres:', 'custom-track-ordering-system'); ?></strong> <?php echo esc_html($order_meta->genres); ?></p>
        <p><strong><?php _e('BPM:', 'custom-track-ordering-system'); ?></strong> <?php echo esc_html($order_meta->bpm); ?></p>
        <p><strong><?php _e('Mood:', 'custom-track-ordering-system'); ?></strong> <?php echo esc_html($order_meta->mood); ?></p>
        <p><strong><?php _e('Track Length:', 'custom-track-ordering-system'); ?></strong> <?php echo esc_html($order_meta->track_length); ?></p>
        
        <?php if (!empty($order_meta->instructions)) : ?>
            <p><strong><?php _e('Special Instructions:', 'custom-track-ordering-system'); ?></strong></p>
            <div class="ctos-instructions">
                <?php echo wpautop(esc_html($order_meta->instructions)); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Show addons if any
        $addons = json_decode($order_meta->addons, true);
        if (!empty($addons)) : 
        ?>
            <p><strong><?php _e('Add-ons:', 'custom-track-ordering-system'); ?></strong></p>
            <ul class="ctos-addons-list">
                <?php foreach ($addons as $addon) : ?>
                    <li><?php echo esc_html($addon['name']); ?> (+<?php echo wc_price($addon['price']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="ctos-files-container">
        <h3><?php _e('Track Files', 'custom-track-ordering-system'); ?></h3>
        
        <?php if ($order_meta->status === 'pending_demo_submission' && $is_producer && $order_meta->deposit_paid) : ?>
            <div class="ctos-upload-section">
                <h4><?php _e('Upload Demo Track', 'custom-track-ordering-system'); ?></h4>
                <form class="ctos-upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="action" value="ctos_upload_demo">
                    <?php wp_nonce_field('ctos-upload-nonce', 'upload_nonce'); ?>
                    
                    <div class="ctos-form-group">
                        <label for="demo_file"><?php _e('Demo File (MP3 only):', 'custom-track-ordering-system'); ?></label>
                        <input type="file" name="demo_file" id="demo_file" accept=".mp3" required>
                    </div>
                    
                    <div class="ctos-form-group">
                        <label for="demo_notes"><?php _e('Notes for Customer:', 'custom-track-ordering-system'); ?></label>
                        <textarea name="demo_notes" id="demo_notes" rows="4"></textarea>
                    </div>
                    
                    <button type="submit" class="ctos-button"><?php _e('Upload Demo', 'custom-track-ordering-system'); ?></button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($order_meta->status === 'awaiting_customer_approval' && $is_customer) : ?>
            <div class="ctos-demo-approval-section">
                <h4><?php _e('Demo Track Approval', 'custom-track-ordering-system'); ?></h4>
                
                <?php if (!empty($order_meta->demo_file)) : ?>
                    <div class="ctos-demo-file">
                        <audio controls>
                            <source src="<?php echo esc_url($order_meta->demo_file); ?>" type="audio/mpeg">
                            <?php _e('Your browser does not support the audio element.', 'custom-track-ordering-system'); ?>
                        </audio>
                        <a href="<?php echo esc_url($order_meta->demo_file); ?>" class="ctos-button ctos-button-secondary" download><?php _e('Download Demo', 'custom-track-ordering-system'); ?></a>
                    </div>
                    
                    <?php if (!empty($order_meta->demo_notes)) : ?>
                        <div class="ctos-demo-notes">
                            <h5><?php _e('Producer Notes:', 'custom-track-ordering-system'); ?></h5>
                            <?php echo wpautop(esc_html($order_meta->demo_notes)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ctos-approval-buttons">
                        <form class="ctos-approval-form" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <input type="hidden" name="action" value="ctos_approve_demo">
                            <?php wp_nonce_field('ctos-approval-nonce', 'approval_nonce'); ?>
                            
                            <button type="submit" class="ctos-button"><?php _e('Approve Track', 'custom-track-ordering-system'); ?></button>
                        </form>
                        
                        <button type="button" class="ctos-button ctos-button-secondary" id="ctos-request-revision-btn"><?php _e('Request Revision', 'custom-track-ordering-system'); ?></button>
                        
                        <div class="ctos-revision-form" style="display: none;">
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <input type="hidden" name="action" value="ctos_request_revision">
                                <?php wp_nonce_field('ctos-revision-nonce', 'revision_nonce'); ?>
                                
                                <div class="ctos-form-group">
                                    <label for="revision_notes"><?php _e('Revision Notes:', 'custom-track-ordering-system'); ?></label>
                                    <textarea name="revision_notes" id="revision_notes" rows="4" required></textarea>
                                </div>
                                
                                <button type="submit" class="ctos-button"><?php _e('Submit Revision Request', 'custom-track-ordering-system'); ?></button>
                            </form>
                        </div>
                    </div>
                <?php else : ?>
                    <p><?php _e('Demo file not found. Please contact the producer.', 'custom-track-ordering-system'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($order_meta->status === 'awaiting_final_delivery' && $is_producer && $order_meta->final_paid) : ?>
            <div class="ctos-upload-section">
                <h4><?php _e('Upload Final Files', 'custom-track-ordering-system'); ?></h4>
                <form class="ctos-upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="action" value="ctos_upload_final_files">
                    <?php wp_nonce_field('ctos-upload-nonce', 'upload_nonce'); ?>
                    
                    <div class="ctos-form-group">
                        <label for="final_files"><?php _e('Final Files (MP3, WAV, ZIP):', 'custom-track-ordering-system'); ?></label>
                        <input type="file" name="final_files[]" id="final_files" multiple accept=".mp3,.wav,.zip" required>
                    </div>
                    
                    <div class="ctos-form-group">
                        <label for="final_notes"><?php _e('Delivery Notes:', 'custom-track-ordering-system'); ?></label>
                        <textarea name="final_notes" id="final_notes" rows="4"></textarea>
                    </div>
                    
                    <button type="submit" class="ctos-button"><?php _e('Upload Final Files', 'custom-track-ordering-system'); ?></button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($order_meta->status === 'completed' && ($is_customer || $is_producer)) : ?>
            <div class="ctos-final-files-section">
                <h4><?php _e('Final Files', 'custom-track-ordering-system'); ?></h4>
                
                <?php
                $final_files = json_decode($order_meta->final_files, true);
                if (!empty($final_files)) : 
                ?>
                    <div class="ctos-files-list">
                        <ul>
                            <?php foreach ($final_files as $file) : ?>
                                <li>
                                    <a href="<?php echo esc_url($file['url']); ?>" download class="ctos-file-link">
                                        <?php echo esc_html($file['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($order_meta->final_notes)) : ?>
                        <div class="ctos-final-notes">
                            <h5><?php _e('Delivery Notes:', 'custom-track-ordering-system'); ?></h5>
                            <?php echo wpautop(esc_html($order_meta->final_notes)); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p><?php _e('No final files available. Please contact support.', 'custom-track-ordering-system'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    // Show messages from MarketKing if available
    $thread_id = get_post_meta($order_id, '_ctos_message_thread_id', true);
    if ($thread_id && function_exists('marketking_get_message_url')) :
        $message_url = marketking_get_message_url($thread_id);
    ?>
        <div class="ctos-message-section">
            <h3><?php _e('Messages', 'custom-track-ordering-system'); ?></h3>
            <a href="<?php echo esc_url($message_url); ?>" class="ctos-button"><?php _e('View Conversation', 'custom-track-ordering-system'); ?></a>
        </div>
    <?php endif; ?>
</div>

<script>
    // Simple JavaScript to toggle the revision form
    document.addEventListener('DOMContentLoaded', function() {
        var revisionBtn = document.getElementById('ctos-request-revision-btn');
        if (revisionBtn) {
            revisionBtn.addEventListener('click', function() {
                var revisionForm = document.querySelector('.ctos-revision-form');
                if (revisionForm) {
                    revisionForm.style.display = revisionForm.style.display === 'none' ? 'block' : 'none';
                }
            });
        }
    });
</script>
