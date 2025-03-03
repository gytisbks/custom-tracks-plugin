/**
 * Public JavaScript for Custom Track Ordering System
 */
(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('CTOS Public JS loaded');
        
        // Producer Settings Form
        initProducerSettingsForm();
        
        // Order Form Init (if present)
        initOrderForm();
        
        // Order Modal Init
        initOrderModal();
        
        // Track delivery tools (if present)
        initTrackDelivery();
    });
    
    /**
     * Initialize Producer Settings Form
     */
    function initProducerSettingsForm() {
        var $form = $('#ctos-producer-settings-form');
        
        if (!$form.length) {
            return;
        }
        
        console.log('Producer settings form found');
        
        // Add new add-on
        $('#ctos-add-addon').on('click', function() {
            var index = $('.ctos-addon-row').length;
            var newAddon = `
                <div class="ctos-addon-row">
                    <input type="text" name="addons[${index}][name]" class="ctos-input ctos-addon-name" placeholder="Add-on Name">
                    <input type="number" name="addons[${index}][price]" class="ctos-input ctos-addon-price" placeholder="Price (€)" min="0" step="0.01">
                    <button type="button" class="ctos-button ctos-button-secondary ctos-remove-addon">Remove</button>
                </div>
            `;
            $('#ctos-addons-container').append(newAddon);
        });
        
        // Remove add-on
        $(document).on('click', '.ctos-remove-addon', function() {
            $(this).closest('.ctos-addon-row').remove();
        });
        
        // Save settings
        $form.on('submit', function(e) {
            e.preventDefault();
            console.log('Submitting producer settings form');
            
            // Show saving message
            $('#ctos-settings-message').text('Saving...').css('color', '#666').show();
            
            var formData = new FormData(this);
            formData.append('action', 'ctos_save_producer_settings');
            formData.append('nonce', ctos_vars.nonce);
            
            // Debug the form data
            console.log('Form data being submitted:');
            for (var pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Ensure checkbox value is properly handled
            if (!$('#ctos-enable-orders').is(':checked')) {
                // Remove the enable_custom_orders field if it exists
                formData.delete('enable_custom_orders');
            }
            
            // Process addons for consistency
            var addonRows = $('.ctos-addon-row');
            if (addonRows.length > 0) {
                // Clear any existing addon entries to rebuild them properly
                for (var i = 0; i < 50; i++) { // arbitrary large number to catch all possible addons
                    formData.delete(`addons[${i}][name]`);
                    formData.delete(`addons[${i}][price]`);
                }
                
                // Add each addon with proper index
                addonRows.each(function(index) {
                    var name = $(this).find('.ctos-addon-name').val();
                    var price = $(this).find('.ctos-addon-price').val();
                    
                    if (name && price) {
                        formData.append(`addons[${index}][name]`, name);
                        formData.append(`addons[${index}][price]`, price);
                    }
                });
            }
            
            $.ajax({
                url: ctos_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Settings save response:', response);
                    if (response.success) {
                        $('#ctos-settings-message').text('Settings saved successfully').css('color', 'green').show();
                        setTimeout(function() {
                            $('#ctos-settings-message').fadeOut();
                        }, 3000);
                    } else {
                        $('#ctos-settings-message').text('Error saving settings: ' + (response.data || '')).css('color', 'red').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving settings:', error);
                    console.error('Response text:', xhr.responseText);
                    $('#ctos-settings-message').text('Error saving settings: ' + error).css('color', 'red').show();
                }
            });
        });
    }
    
    /**
     * Initialize Order Modal Functionality
     */
    function initOrderModal() {
        console.log('Initializing order modal');
        
        // Add button click handler using direct event binding
        $('.ctos-request-button').on('click', function(e) {
            e.preventDefault();
            console.log('Order button clicked via jQuery direct binding');
            
            const producerId = $(this).data('producer-id');
            console.log('Producer ID:', producerId);
            
            // Set producer ID in the modal form
            $('#ctos-producer-id').val(producerId);
            
            // Fetch producer's settings to populate pricing
            fetchProducerSettings(producerId);
            
            // Show the modal
            $('#ctos-order-modal').fadeIn();
            
            return false;
        });
        
        // Also use document delegation for dynamically added buttons
        $(document).on('click', '.ctos-request-button', function(e) {
            e.preventDefault();
            console.log('Order button clicked via jQuery delegation');
            
            const producerId = $(this).data('producer-id');
            console.log('Producer ID:', producerId);
            
            // Set producer ID in the modal form
            $('#ctos-producer-id').val(producerId);
            
            // Fetch producer's settings to populate pricing
            fetchProducerSettings(producerId);
            
            // Show the modal
            $('#ctos-order-modal').fadeIn();
            
            return false;
        });
        
        // Listen for our custom event from inline handler
        $(document).on('ctos_modal_opened', function(e, producerId) {
            console.log('Modal opened event triggered for producer:', producerId);
            fetchProducerSettings(producerId);
        });
        
        // Close modal when clicking the close button or outside the modal
        $('.ctos-modal-close, .ctos-modal').on('click', function(e) {
            if (e.target === this) {
                $('#ctos-order-modal').fadeOut();
            }
        });
        
        // Prevent closing when clicking inside the modal content
        $('.ctos-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Handle file selection for reference tracks
        $('#ctos-reference-upload').on('change', function() {
            var files = this.files;
            var $fileList = $('.ctos-file-list');
            
            $fileList.empty();
            
            if (files.length > 0) {
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var fileSize = formatFileSize(file.size);
                    
                    $fileList.append(`
                        <div class="ctos-file-item">
                            <span class="ctos-file-name">${file.name}</span>
                            <span class="ctos-file-size">(${fileSize})</span>
                        </div>
                    `);
                }
            }
        });
        
        // Handle form submission
        $('#ctos-order-form').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            $('#ctos-submit-order').prop('disabled', true).text('Processing...');
            
            var formData = new FormData(this);
            formData.append('action', 'ctos_create_order');
            formData.append('nonce', ctos_vars.nonce);
            
            // Add selected addons
            var selectedAddons = [];
            $('.ctos-addon-checkbox:checked').each(function() {
                selectedAddons.push($(this).val());
            });
            
            for (var i = 0; i < selectedAddons.length; i++) {
                formData.append('addons[]', selectedAddons[i]);
            }
            
            // Submit the form via AJAX
            $.ajax({
                url: ctos_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Order form submission response:', response);
                    if (response.success && response.data.redirect) {
                        // Redirect to checkout
                        window.location.href = response.data.redirect;
                    } else {
                        // Show error
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $('#ctos-submit-order').prop('disabled', false).text('Submit Order');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error submitting order:', error);
                    alert('Error: ' + error);
                    $('#ctos-submit-order').prop('disabled', false).text('Submit Order');
                }
            });
        });
    }
    
    /**
     * Fetch producer settings via AJAX
     */
    function fetchProducerSettings(producerId) {
        console.log('Fetching settings for producer:', producerId);
        $.ajax({
            url: ctos_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'ctos_get_producer_settings',
                producer_id: producerId,
                nonce: ctos_vars.nonce
            },
            success: function(response) {
                console.log('Producer settings response:', response);
                if (response.success) {
                    updateOrderFormPricing(response.data);
                } else {
                    console.error('Error fetching producer settings:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error fetching producer settings:', error);
            }
        });
    }
    
    /**
     * Update order form with producer pricing information
     */
    function updateOrderFormPricing(settings) {
        console.log('Updating pricing with settings:', settings);
        
        // Ensure base price is a number
        var basePrice = parseFloat(settings.base_price) || 99.99;
        
        // Set base price
        $('.ctos-base-price').text('€' + basePrice.toFixed(2));
        
        // Set initial total price (same as base price initially)
        $('.ctos-total-price').text('€' + basePrice.toFixed(2));
        
        // Calculate initial deposit amount
        var depositAmount = basePrice * 0.3;
        $('.ctos-deposit-amount').text('€' + depositAmount.toFixed(2));
        
        // Clear existing addons
        $('.ctos-addons-list').empty();
        
        // Add addons if available
        if (settings.addons && settings.addons.length > 0) {
            settings.addons.forEach(function(addon, index) {
                // Ensure price is a number
                var addonPrice = parseFloat(addon.price) || 0;
                
                $('.ctos-addons-list').append(`
                    <div class="ctos-addon-item">
                        <label>
                            <input type="checkbox" class="ctos-addon-checkbox" 
                                   value="${addon.name}" 
                                   data-price="${addonPrice}">
                            ${addon.name} (+€${addonPrice.toFixed(2)})
                        </label>
                    </div>
                `);
            });
            
            // Add change handler for addons
            $('.ctos-addon-checkbox').on('change', function() {
                updateTotalPrice(basePrice);
            });
        } else {
            $('.ctos-addons-list').html('<p>No additional services available</p>');
        }
    }
    
    /**
     * Update total price when addons are selected/deselected
     */
    function updateTotalPrice(basePrice) {
        console.log('Updating total price, base price:', basePrice);
        
        var total = parseFloat(basePrice) || 0;
        console.log('Starting total:', total);
        
        // Add selected addons
        $('.ctos-addon-checkbox:checked').each(function() {
            var addonPrice = parseFloat($(this).data('price')) || 0;
            console.log('Adding addon price:', addonPrice);
            total += addonPrice;
        });
        
        console.log('Final total price:', total);
        
        // Update total price display
        $('.ctos-total-price').text('€' + total.toFixed(2));
        
        // Update deposit amount (30%)
        var depositAmount = total * 0.3;
        console.log('Deposit amount:', depositAmount);
        $('.ctos-deposit-amount').text('€' + depositAmount.toFixed(2));
    }
    
    /**
     * Initialize Order Form
     */
    function initOrderForm() {
        var $form = $('#ctos-order-form:not(.ctos-modal #ctos-order-form)');
        
        if (!$form.length) {
            return;
        }
        
        console.log('Order form found');
        
        // Calculate total price
        function updateTotalPrice() {
            var basePrice = parseFloat($('#ctos-base-price-value').val()) || 0;
            var total = basePrice;
            
            // Add selected add-ons
            $('.ctos-addon-checkbox:checked').each(function() {
                total += parseFloat($(this).data('price')) || 0;
            });
            
            $('#ctos-total-price').text('€' + total.toFixed(2));
            
            // Update deposit amount (30%)
            var depositAmount = total * 0.3;
            $('.ctos-deposit-amount').text('€' + depositAmount.toFixed(2));
        }
        
        // Update price when add-ons are selected/deselected
        $('.ctos-addon-checkbox').on('change', updateTotalPrice);
        
        // Initial price calculation
        updateTotalPrice();
        
        // Form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            var isValid = true;
            var requiredFields = $form.find('[required]');
            
            requiredFields.each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('ctos-error');
                } else {
                    $(this).removeClass('ctos-error');
                }
            });
            
            if (!isValid) {
                $('#ctos-form-message').text('Please fill in all required fields').css('color', 'red').show();
                return;
            }
            
            // Show submitting message
            $('#ctos-form-message').text('Submitting...').css('color', '#666').show();
            
            var formData = new FormData(this);
            formData.append('action', 'ctos_submit_order');
            formData.append('nonce', ctos_vars.nonce);
            
            $.ajax({
                url: ctos_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $form.html('<div class="ctos-success-message"><p>Your order has been submitted successfully!</p><p>Order ID: ' + response.data.order_id + '</p><p>You will receive a confirmation email shortly.</p></div>');
                    } else {
                        $('#ctos-form-message').text('Error submitting order: ' + (response.data || '')).css('color', 'red').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#ctos-form-message').text('Error submitting order: ' + error).css('color', 'red').show();
                }
            });
        });
    }
    
    /**
     * Initialize Track Delivery Tools
     */
    function initTrackDelivery() {
        var $container = $('.ctos-track-delivery');
        
        if (!$container.length) {
            return;
        }
        
        console.log('Track delivery container found');
        
        // File upload preview
        $('.ctos-file-upload').on('change', function() {
            var file = this.files[0];
            var $preview = $(this).siblings('.ctos-file-preview');
            
            if (file) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    $preview.html('<div class="ctos-file-info"><span class="ctos-file-name">' + file.name + '</span><span class="ctos-file-size">(' + formatFileSize(file.size) + ')</span></div>');
                };
                
                reader.readAsDataURL(file);
            } else {
                $preview.html('');
            }
        });
        
        // Submit delivery
        $('.ctos-delivery-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $form.find('.ctos-form-message');
            
            // Check if file is selected
            var fileInput = $form.find('.ctos-file-upload')[0];
            if (fileInput && fileInput.files.length === 0) {
                $message.text('Please select a file to upload').css('color', 'red').show();
                return;
            }
            
            // Show submitting message
            $message.text('Uploading...').css('color', '#666').show();
            
            var formData = new FormData(this);
            formData.append('action', 'ctos_submit_delivery');
            formData.append('nonce', ctos_vars.nonce);
            
            $.ajax({
                url: ctos_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.text('File uploaded successfully!').css('color', 'green').show();
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $message.text('Error uploading file: ' + (response.data || '')).css('color', 'red').show();
                    }
                },
                error: function(xhr, status, error) {
                    $message.text('Error uploading file: ' + error).css('color', 'red').show();
                }
            });
        });
    }
    
    /**
     * Helper function to format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
})(jQuery);
