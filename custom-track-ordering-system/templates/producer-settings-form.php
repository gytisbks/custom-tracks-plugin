<?php
/**
 * Template for producer settings form in MarketKing dashboard.
 */
defined('ABSPATH') || exit;

// Safety check - if $settings is not defined, initialize it
if (!isset($settings) || empty($settings)) {
    if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
        error_log('Settings not provided to template, initializing default settings');
    }
    
    // Initialize settings with default values
    $settings = (object) array(
        'enable_custom_orders' => 1,
        'genres' => '',
        'daw_compatibility' => '',
        'similar_artists' => '',
        'base_price' => 0,
        'delivery_time' => 7,
        'revisions' => 3,
        'addons' => array(
            array('name' => 'Project File', 'price' => 30),
            array('name' => 'Stems', 'price' => 20),
            array('name' => 'Fast Delivery (3 days)', 'price' => 50)
        )
    );
}

// Debug
if (defined('CTOS_DEBUG') && CTOS_DEBUG) {
    error_log('Rendering producer settings form with settings: ' . print_r($settings, true));
}
?>

<div class="ctos-settings-container">
    <h2><?php _e('Custom Track Orders Settings', 'custom-track-ordering-system'); ?></h2>
    
    <form id="ctos-producer-settings-form" class="ctos-form">
        <?php wp_nonce_field('ctos-nonce', 'ctos_nonce'); ?>
        
        <div class="ctos-form-row">
            <label for="ctos-enable-orders" class="ctos-form-label">
                <input type="checkbox" name="enable_custom_orders" id="ctos-enable-orders" value="1" <?php checked($settings->enable_custom_orders, 1); ?>>
                <?php _e('Enable Custom Track Orders', 'custom-track-ordering-system'); ?>
            </label>
            <p class="ctos-form-help"><?php _e('Allow customers to request custom tracks from you.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-genres" class="ctos-form-label"><?php _e('Genres You Work With', 'custom-track-ordering-system'); ?></label>
            <input type="text" name="genres" id="ctos-genres" class="ctos-input" value="<?php echo esc_attr($settings->genres); ?>" placeholder="<?php _e('e.g., House, Techno, Pop, Hip-Hop', 'custom-track-ordering-system'); ?>">
            <p class="ctos-form-help"><?php _e('Comma-separated list of genres you specialize in.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-daw" class="ctos-form-label"><?php _e('DAW Compatibility', 'custom-track-ordering-system'); ?></label>
            <input type="text" name="daw_compatibility" id="ctos-daw" class="ctos-input" value="<?php echo esc_attr($settings->daw_compatibility); ?>" placeholder="<?php _e('e.g., FL Studio, Ableton Live, Logic Pro', 'custom-track-ordering-system'); ?>">
            <p class="ctos-form-help"><?php _e('What DAWs do you use? This is important if customers request project files.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-similar-artists" class="ctos-form-label"><?php _e('Similar Artists / Labels', 'custom-track-ordering-system'); ?></label>
            <input type="text" name="similar_artists" id="ctos-similar-artists" class="ctos-input" value="<?php echo esc_attr($settings->similar_artists); ?>" placeholder="<?php _e('e.g., Daft Punk, Disclosure, Anjunabeats', 'custom-track-ordering-system'); ?>">
            <p class="ctos-form-help"><?php _e('Artists or labels with a similar style to your music.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-base-price" class="ctos-form-label"><?php _e('Base Price (€)', 'custom-track-ordering-system'); ?></label>
            <input type="number" name="base_price" id="ctos-base-price" class="ctos-input" value="<?php echo esc_attr($settings->base_price); ?>" min="0" step="0.01">
            <p class="ctos-form-help"><?php _e('Your starting price for a standard custom track.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-delivery-time" class="ctos-form-label"><?php _e('Standard Delivery Time (Days)', 'custom-track-ordering-system'); ?></label>
            <input type="number" name="delivery_time" id="ctos-delivery-time" class="ctos-input" value="<?php echo esc_attr($settings->delivery_time); ?>" min="1" max="90">
            <p class="ctos-form-help"><?php _e('How many days it typically takes you to deliver a demo.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label for="ctos-revisions" class="ctos-form-label"><?php _e('Number of Revisions', 'custom-track-ordering-system'); ?></label>
            <input type="number" name="revisions" id="ctos-revisions" class="ctos-input" value="<?php echo esc_attr($settings->revisions); ?>" min="0" max="10">
            <p class="ctos-form-help"><?php _e('How many revisions you offer after the initial demo.', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <label class="ctos-form-label"><?php _e('Extra Add-ons', 'custom-track-ordering-system'); ?></label>
            
            <div id="ctos-addons-container">
                <?php 
                $addons = $settings->addons;
                if (!empty($addons)) {
                    foreach ($addons as $index => $addon) {
                        ?>
                        <div class="ctos-addon-row">
                            <input type="text" name="addons[<?php echo $index; ?>][name]" class="ctos-input ctos-addon-name" value="<?php echo esc_attr($addon['name']); ?>" placeholder="<?php _e('Add-on Name', 'custom-track-ordering-system'); ?>">
                            <input type="number" name="addons[<?php echo $index; ?>][price]" class="ctos-input ctos-addon-price" value="<?php echo esc_attr($addon['price']); ?>" placeholder="<?php _e('Price (€)', 'custom-track-ordering-system'); ?>" min="0" step="0.01">
                            <button type="button" class="ctos-button ctos-button-secondary ctos-remove-addon"><?php _e('Remove', 'custom-track-ordering-system'); ?></button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" id="ctos-add-addon" class="ctos-button ctos-button-secondary"><?php _e('Add New Add-on', 'custom-track-ordering-system'); ?></button>
            <p class="ctos-form-help"><?php _e('Add optional services customers can add to their order (e.g., Project Files, Stems, Fast Delivery).', 'custom-track-ordering-system'); ?></p>
        </div>
        
        <div class="ctos-form-row">
            <button type="submit" class="ctos-button" id="ctos-save-settings"><?php _e('Save Settings', 'custom-track-ordering-system'); ?></button>
            <span id="ctos-settings-message" style="display: none; margin-left: 10px;"></span>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        console.log('Producer settings form initialized');
        
        // Add new add-on
        $('#ctos-add-addon').on('click', function() {
            var index = $('.ctos-addon-row').length;
            var newAddon = `
                <div class="ctos-addon-row">
                    <input type="text" name="addons[${index}][name]" class="ctos-input ctos-addon-name" placeholder="<?php _e('Add-on Name', 'custom-track-ordering-system'); ?>">
                    <input type="number" name="addons[${index}][price]" class="ctos-input ctos-addon-price" placeholder="<?php _e('Price (€)', 'custom-track-ordering-system'); ?>" min="0" step="0.01">
                    <button type="button" class="ctos-button ctos-button-secondary ctos-remove-addon"><?php _e('Remove', 'custom-track-ordering-system'); ?></button>
                </div>
            `;
            $('#ctos-addons-container').append(newAddon);
        });
        
        // Remove add-on
        $(document).on('click', '.ctos-remove-addon', function() {
            $(this).closest('.ctos-addon-row').remove();
        });
        
        // Save settings
        $('#ctos-producer-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            console.log('Submitting producer settings form');
            
            // Show saving message
            $('#ctos-settings-message').text('<?php _e('Saving...', 'custom-track-ordering-system'); ?>').css('color', '#666').show();
            
            var formData = new FormData(this);
            formData.append('action', 'ctos_save_producer_settings');
            
            // Debug form data
            console.log('Form nonce:', $(this).find('#ctos_nonce').val());
            console.log('Enable orders:', $(this).find('#ctos-enable-orders').is(':checked'));
            
            // Ensure the checkbox value is properly captured even when unchecked
            if (!$(this).find('#ctos-enable-orders').is(':checked')) {
                formData.delete('enable_custom_orders');
            }
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Settings save response:', response);
                    if (response.success) {
                        $('#ctos-settings-message').text('<?php _e('Settings saved successfully', 'custom-track-ordering-system'); ?>').css('color', 'green').show();
                        setTimeout(function() {
                            $('#ctos-settings-message').fadeOut();
                        }, 3000);
                    } else {
                        $('#ctos-settings-message').text(response.data || '<?php _e('Error saving settings', 'custom-track-ordering-system'); ?>').css('color', 'red').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                    console.error('Response:', xhr.responseText);
                    $('#ctos-settings-message').text('<?php _e('Connection error. Please try again.', 'custom-track-ordering-system'); ?>').css('color', 'red').show();
                }
            });
        });
    });
</script>
