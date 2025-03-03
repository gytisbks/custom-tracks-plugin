<?php
/**
 * Template for track order details on order page.
 */
defined('ABSPATH') || exit;

$producer = get_user_by('id', $order_meta->producer_id);
$producer_name = $producer ? $producer->display_name : __('Unknown Producer', 'custom-track-ordering-system');
$status_label = CTOS_MarketKing_Integration::get_status_label($order_meta->status);
$status_class = CTOS_MarketKing_Integration::get_status_class($order_meta->status);
?>

<div class="ctos-order-details-container">
    <h3><?php _e('Custom Track Order Details', 'custom-track-ordering-system'); ?></h3>
    
    <div class="ctos-order-details">
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Producer:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html($producer_name); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Service Type:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html(ucfirst(str_replace('_', ' ', $order_meta->service_type))); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Genres:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html($order_meta->genres); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('BPM:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html($order_meta->bpm); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Mood:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html($order_meta->mood); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Track Length:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo esc_html(ucfirst(str_replace('_', ' ', $order_meta->track_length))); ?></div>
        </div>
        
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Status:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value">
                <span class="ctos-order-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
            </div>
        </div>
        
        <?php if (!empty($order_meta->addons)) : 
            $addons = json_decode($order_meta->addons, true);
        ?>
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Add-ons:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value">
                <ul>
                    <?php foreach ($addons as $addon) : ?>
                        <li><?php echo esc_html($addon['name']); ?> (<?php echo wc_price($addon['price']); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($order_meta->instructions)) : ?>
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Instructions:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value"><?php echo nl2br(esc_html($order_meta->instructions)); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($order_meta->demo_url) && in_array($order_meta->status, array('awaiting_customer_approval', 'awaiting_final_payment', 'awaiting_final_delivery', 'completed'))) : ?>
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Demo Track:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value">
                <audio class="ctos-audio-player" controls>
                    <source src="<?php echo esc_url($order_meta->demo_url); ?>" type="audio/mpeg">
                    <?php _e('Your browser does not support the audio element.', 'custom-track-ordering-system'); ?>
                </audio>
                <a href="<?php echo esc_url(CTOS_File_Handler::get_download_url('demo', $order_meta->order_id, 'demo')); ?>" class="ctos-button ctos-button-secondary"><?php _e('Download Demo', 'custom-track-ordering-system'); ?></a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($order_meta->final_files) && $order_meta->status === 'completed') : 
            $final_files = json_decode($order_meta->final_files, true);
        ?>
        <div class="ctos-order-detail-row">
            <div class="ctos-order-detail-label"><?php _e('Final Files:', 'custom-track-ordering-system'); ?></div>
            <div class="ctos-order-detail-value">
                <ul class="ctos-file-list">
                    <?php foreach ($final_files as $file) : ?>
                        <li class="ctos-file-item">
                            <span class="dashicons dashicons-media-audio"></span>
                            <span class="ctos-file-name"><?php echo esc_html($file['name']); ?></span>
                            <a href="<?php echo esc_url(CTOS_File_Handler::get_download_url($file['id'], $order_meta->order_id, 'final')); ?>" class="ctos-button ctos-button-secondary"><?php _e('Download', 'custom-track-ordering-system'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php
    // Show action buttons based on status and user role
    $current_user_id = get_current_user_id();
    $is_producer = $current_user_id === $order_meta->producer_id;
    $is_customer = $current_user_id === $order_meta->customer_id;
    
    if ($is_producer && $order_meta->status === 'pending_demo_submission' && $order_meta->deposit_paid) : ?>
        <div class="ctos-action-buttons">
            <button type="button" class="ctos-button" onclick="document.getElementById('ctos-demo-upload').click();"><?php _e('Upload Demo', 'custom-track-ordering-system'); ?></button>
            <input type="file" id="ctos-demo-upload" class="ctos-demo-upload" data-order-id="<?php echo $order_meta->order_id; ?>" accept=".mp3" style="display: none;">
        </div>
    <?php endif; ?>
    
    <?php if ($is_customer && $order_meta->status === 'awaiting_customer_approval') : ?>
        <div class="ctos-action-buttons">
            <a href="#" class="ctos-button ctos-approve-demo" data-order-id="<?php echo $order_meta->order_id; ?>"><?php _e('Approve Demo', 'custom-track-ordering-system'); ?></a>
            <a href="#" class="ctos-button ctos-button-secondary ctos-request-revision" data-order-id="<?php echo $order_meta->order_id; ?>"><?php _e('Request Revision', 'custom-track-ordering-system'); ?></a>
        </div>
        
        <div id="ctos-revision-form-container" style="display: none; margin-top: 20px;">
            <h4><?php _e('Request Revision', 'custom-track-ordering-system'); ?></h4>
            <form id="ctos-revision-form" class="ctos-form">
                <div class="ctos-form-row">
                    <label for="ctos-revision-notes" class="ctos-form-label"><?php _e('Revision Notes', 'custom-track-ordering-system'); ?></label>
                    <textarea id="ctos-revision-notes" class="ctos-textarea" required></textarea>
                    <p class="ctos-form-help"><?php _e('Describe in detail what changes you would like the producer to make.', 'custom-track-ordering-system'); ?></p>
                </div>
                <div class="ctos-form-row">
                    <button type="submit" class="ctos-button"><?php _e('Submit Revision Request', 'custom-track-ordering-system'); ?></button>
                    <button type="button" class="ctos-button ctos-button-secondary" onclick="document.getElementById('ctos-revision-form-container').style.display='none';"><?php _e('Cancel', 'custom-track-ordering-system'); ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($is_producer && $order_meta->status === 'awaiting_final_delivery' && $order_meta->final_paid) : ?>
        <div class="ctos-action-buttons">
            <button type="button" class="ctos-button" onclick="document.getElementById('ctos-final-files-upload').click();"><?php _e('Upload Final Files', 'custom-track-ordering-system'); ?></button>
            <input type="file" id="ctos-final-files-upload" class="ctos-final-files-upload" data-order-id="<?php echo $order_meta->order_id; ?>" multiple accept=".mp3,.wav,.zip" style="display: none;">
        </div>
    <?php endif; ?>
    
    <?php if ($is_customer && $order_meta->status === 'completed' && !empty($order_meta->final_files)) : ?>
        <div class="ctos-action-buttons">
            <a href="#" class="ctos-button ctos-complete-order" data-order-id="<?php echo $order_meta->order_id; ?>"><?php _e('Confirm Receipt', 'custom-track-ordering-system'); ?></a>
        </div>
    <?php endif; ?>
    
    <?php
    // Show link to conversation if MarketKing Messages is active
    $thread_id = get_post_meta($order_meta->order_id, '_ctos_message_thread_id', true);
    if ($thread_id && function_exists('marketking_get_message_url')) {
        $message_url = marketking_get_message_url($thread_id);
        ?>
        <div class="ctos-message-link" style="margin-top: 20px;">
            <a href="<?php echo esc_url($message_url); ?>" class="ctos-button"><?php _e('View Messages', 'custom-track-ordering-system'); ?></a>
        </div>
        <?php
    }
    ?>
</div>
