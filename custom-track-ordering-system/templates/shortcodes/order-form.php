<?php
/**
 * Template for the order form shortcode
 */
defined('ABSPATH') || exit;

// Add nonce for security
$nonce = wp_create_nonce('ctos-nonce');

// Get producer settings for pricing
$producer_settings = new CTOS_Producer_Settings();
$settings = $producer_settings->get_producer_settings($producer_id);

// Enqueue scripts and styles
wp_enqueue_style('ctos-public', CTOS_PLUGIN_URL . 'assets/css/public.css');
wp_enqueue_script('ctos-public', CTOS_PLUGIN_URL . 'assets/js/public.js', array('jquery'), CTOS_VERSION, true);

// Localize script data
wp_localize_script('ctos-public', 'ctos_vars', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => $nonce
));
?>

<div class="ctos-order-form-container">
    <h2><?php _e('Request Custom Track', 'custom-track-ordering-system'); ?></h2>
    
    <form id="ctos-order-form" class="ctos-form">
        <input type="hidden" name="producer_id" value="<?php echo esc_attr($producer_id); ?>">
        <?php wp_nonce_field('ctos-nonce', 'ctos_nonce'); ?>
        
        <div class="ctos-form-row">
            <label for="ctos-track-title" class="ctos-form-label"><?php _e('Track Title', 'custom-track-ordering-system'); ?></label>
            <input type="text" name="track_title" id="ctos-track-title" class="ctos-input" required>
            <p class="ctos-form-help"><?php _e('Working title for your custom track', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-genre" class="ctos-form-label"><?php _e('Genre', 'custom-track-ordering-system'); ?></label>
            <input type="text" name="genre" id="ctos-genre" class="ctos-input" required>
            <p class="ctos-form-help"><?php _e('What genre would you like for this track?', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-description" class="ctos-form-label"><?php _e('Track Description', 'custom-track-ordering-system'); ?></label>
            <textarea name="description" id="ctos-description" class="ctos-textarea" rows="4" required></textarea>
            <p class="ctos-form-help"><?php _e('Describe the sound, mood, and style you want. Be as specific as possible.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-reference-tracks" class="ctos-form-label"><?php _e('Reference Tracks', 'custom-track-ordering-system'); ?></label>
            <textarea name="reference_tracks" id="ctos-reference-tracks" class="ctos-textarea" rows="3"></textarea>
            <p class="ctos-form-help"><?php _e('List URLs to tracks with a similar style (YouTube, SoundCloud, Spotify, etc.)', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label class="ctos-form-label"><?php _e('Reference Files', 'custom-track-ordering-system'); ?></label>
            <div class="ctos-file-upload">
                <p class="ctos-file-upload-text"><?php _e('Upload audio examples or inspiration', 'custom-track-ordering-system'); ?></p>
                <input type="file" name="reference_files[]" id="ctos-reference-upload" class="ctos-input-file" multiple accept="audio/*">
                <div class="ctos-file-list"></div>
            </div>
            <p class="ctos-form-help"><?php _e('Optional: Upload mp3 files that showcase the style you want', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <?php if (!empty($settings->addons_array)): ?>
        <div class="ctos-form-row">
            <label class="ctos-form-label"><?php _e('Additional Services', 'custom-track-ordering-system'); ?></label>
            <div class="ctos-addons-list">
                <?php foreach ($settings->addons_array as $index => $addon): ?>
                <div class="ctos-addon-item">
                    <label>
                        <input type="checkbox" class="ctos-addon-checkbox" 
                               name="addons[]" value="<?php echo esc_attr($addon['name']); ?>" 
                               data-price="<?php echo esc_attr($addon['price']); ?>">
                        <?php echo esc_html($addon['name']); ?> (+€<?php echo number_format((float)$addon['price'], 2); ?>)
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="ctos-form-row">
            <label class="ctos-form-label"><?php _e('Pricing Summary', 'custom-track-ordering-system'); ?></label>
            <div class="ctos-pricing-summary">
                <div class="ctos-price-row">
                    <span class="ctos-price-label"><?php _e('Base Price:', 'custom-track-ordering-system'); ?></span>
                    <span class="ctos-base-price">€<?php echo number_format((float)$settings->base_price, 2); ?></span>
                    <input type="hidden" id="ctos-base-price-value" value="<?php echo esc_attr($settings->base_price); ?>">
                </div>
                <div class="ctos-price-row">
                    <span class="ctos-price-label"><?php _e('Total Price:', 'custom-track-ordering-system'); ?></span>
                    <span id="ctos-total-price">€<?php echo number_format((float)$settings->base_price, 2); ?></span>
                </div>
                <div class="ctos-price-row">
                    <span class="ctos-price-label"><?php _e('Initial Deposit (30%):', 'custom-track-ordering-system'); ?></span>
                    <span class="ctos-deposit-amount">€<?php echo number_format((float)$settings->base_price * 0.3, 2); ?></span>
                </div>
                <p class="ctos-price-note"><?php _e('You will pay the initial deposit now. The remaining balance will be due after you approve the demo.', 'custom-track-ordering-system'); ?></p>
            </div>
        </div>
        
        <div class="ctos-form-row">
            <button type="submit" class="ctos-button" id="ctos-submit-order"><?php _e('Submit Order', 'custom-track-ordering-system'); ?></button>
            <span id="ctos-form-message" style="display: none; margin-left: 10px;"></span>
        </div>
    </form>
</div>
