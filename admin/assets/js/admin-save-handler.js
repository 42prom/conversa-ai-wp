/**
 * Admin AJAX save handler.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/assets/js
 */

(function($) {
    'use strict';
    
    // Settings form save handler
    $(document).ready(function() {
        $('.conversaai-save-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var form = button.closest('form');
            var settingsGroup = button.data('settings-group');
            var statusElement = form.find('.conversaai-save-status');
            
            // Disable button and show saving status
            button.prop('disabled', true);
            statusElement.text(conversaai_pro_ajax.saving_text).show();
            
            // Serialize form data
            var formData = form.serialize();
            
            // Collect all form data into an object
            var settingsData = {};
            form.find('input, select, textarea').each(function() {
                var input = $(this);
                var name = input.attr('name');
                
                // Skip inputs without names or submit buttons
                if (!name || input.attr('type') === 'submit') {
                    return;
                }
                
                // Handle checkboxes specially
                if (input.attr('type') === 'checkbox') {
                    var checkboxName = name.match(/\[(.*?)\]/);
                    if (checkboxName && checkboxName[1]) {
                        settingsData[checkboxName[1]] = input.is(':checked') ? 1 : 0;
                    }
                } else {
                    // Extract the key from the name (e.g., "conversaai_pro_general_settings[key]")
                    var matches = name.match(/\[(.*?)\]/);
                    if (matches && matches[1]) {
                        settingsData[matches[1]] = input.val();
                    }
                }
            });
            
            // Make AJAX request
            $.ajax({
                url: conversaai_pro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'conversaai_save_settings',
                    nonce: conversaai_pro_ajax.nonce,
                    settings_group: settingsGroup,
                    settings_data: settingsData
                },
                success: function(response) {
                    if (response.success) {
                        statusElement.text(conversaai_pro_ajax.saved_text);
                        
                        // Show success notice
                        $('#setting-error-settings_updated').show().delay(3000).fadeOut();
                    } else {
                        statusElement.text(conversaai_pro_ajax.error_text);
                        
                        // Show error notice
                        $('#setting-error-settings_error').show().delay(3000).fadeOut();
                        console.error('Error saving settings:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    statusElement.text(conversaai_pro_ajax.error_text);
                    
                    // Show error notice
                    $('#setting-error-settings_error').show().delay(3000).fadeOut();
                    console.error('AJAX error:', error);
                },
                complete: function() {
                    // Re-enable button after request completes
                    button.prop('disabled', false);
                    
                    // Hide status after a delay
                    setTimeout(function() {
                        statusElement.fadeOut();
                    }, 3000);
                }
            });
        });
    });
    
})(jQuery);