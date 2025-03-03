<?php
/**
 * Template for the order form modal
 */
defined('ABSPATH') || exit;

// Add nonce for security
$nonce = wp_create_nonce('ctos-nonce');
?>

<div id="ctos-order-modal" class="ctos-modal">
    <div class="ctos-modal-content">
        <span class="ctos-modal-close">&times;</span>
        
        <h2 class="ctos-modal-title"><?php _e('Request Custom Track', 'custom-track-ordering-system'); ?></h2>
        
        <form id="ctos-order-form" class="ctos-form">
            <input type="hidden" name="producer_id" id="ctos-producer-id" value="">
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
            
            <div class="ctos-form-row">
                <label class="ctos-form-label"><?php _e('Additional Services', 'custom-track-ordering-system'); ?></label>
                <div class="ctos-addons-list">
                    <!-- Addons will be dynamically loaded here -->
                    <p class="ctos-loading"><?php _e('Loading available add-ons...', 'custom-track-ordering-system'); ?></p>
                </div>
            </div>
            
            <div class="ctos-form-row">
                <label class="ctos-form-label"><?php _e('Pricing Summary', 'custom-track-ordering-system'); ?></label>
                <div class="ctos-pricing-summary">
                    <div class="ctos-price-row">
                        <span class="ctos-price-label"><?php _e('Base Price:', 'custom-track-ordering-system'); ?></span>
                        <span class="ctos-base-price">€0.00</span>
                    </div>
                    <div class="ctos-price-row">
                        <span class="ctos-price-label"><?php _e('Total Price:', 'custom-track-ordering-system'); ?></span>
                        <span class="ctos-total-price">€0.00</span>
                    </div>
                    <div class="ctos-price-row">
                        <span class="ctos-price-label"><?php _e('Initial Deposit (30%):', 'custom-track-ordering-system'); ?></span>
                        <span class="ctos-deposit-amount">€0.00</span>
                    </div>
                    <p class="ctos-price-note"><?php _e('You will pay the initial deposit now. The remaining balance will be due after you approve the demo.', 'custom-track-ordering-system'); ?></p>
                </div>
            </div>
            
            <div class="ctos-form-row">
                <button type="submit" class="ctos-button" id="ctos-submit-order"><?php _e('Submit Order', 'custom-track-ordering-system'); ?></button>
            </div>
        </form>
    </div>
</div>
