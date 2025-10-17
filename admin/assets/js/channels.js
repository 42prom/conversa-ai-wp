/**
 * Messaging channels admin JavaScript.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/assets/js
 */

(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
         // Debug initialization
        console.log('Channels.js initialized');
        console.log('AJAX URL:', conversaai_channels.ajax_url);
        console.log('Nonce:', conversaai_channels.nonce);
        console.log('Channels.js loaded');

        // Define global ajaxurl if not already defined (for non-admin pages)
        if (typeof ajaxurl === 'undefined') {
            window.ajaxurl = conversaai_channels.ajax_url;
        }
        
        // Add a global error handler to catch any AJAX issues
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            console.error('Global AJAX error detected:');
            console.error('URL:', settings.url);
            console.error('Status:', jqxhr.status);
            console.error('Error:', thrownError);
            console.error('Response:', jqxhr.responseText);
        });
        
        // Channel tabs functionality
        $('.conversaai-tab').on('click', function() {
            var $this = $(this);
            var tabId = $this.data('tab');
            
            // Activate the tab
            $('.conversaai-tab').removeClass('active');
            $this.addClass('active');
            
            // Show the tab content
            $('.conversaai-tab-content').removeClass('active').hide();
            $('#tab-' + tabId).addClass('active').fadeIn(300);
            
            // Store the active tab in localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('conversaai_active_channel_tab', tabId);
            }
        });
        
        // Restore the active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            var activeTab = localStorage.getItem('conversaai_active_channel_tab');
            if (activeTab) {
                $('.conversaai-tab[data-tab="' + activeTab + '"]').trigger('click');
            }
        }
        
        // Copy webhook URL to clipboard
        $('.copy-webhook-url').on('click', function() {
            var $this = $(this);
            var url = $this.data('url');
            var originalHtml = $this.html();
            
            // Create a temporary input element
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(url).select();
            
            try {
                // Copy the URL
                var successful = document.execCommand('copy');
                $temp.remove();
                
                // Visual feedback
                if (successful) {
                    $this.html('<span class="dashicons dashicons-yes"></span>');
                    setTimeout(function() {
                        $this.html(originalHtml);
                    }, 2000);
                    
                    // Show success message
                    showNotice('success', conversaai_channels.copy_success);
                } else {
                    showNotice('error', conversaai_channels.copy_error);
                }
            } catch(err) {
                $temp.remove();
                showNotice('error', conversaai_channels.copy_error);
            }
        });
        
        // Channel toggle functionality
        $('input[type="checkbox"][id$="-enabled"]').on('change', function() {
            var $this = $(this);
            var channel = $this.attr('id').replace('-enabled', '');
            var isEnabled = $this.is(':checked');
            
            // Visual feedback
            var $channelIcon = $('.conversaai-channel-icon.' + channel);
            var $status = $channelIcon.find('.conversaai-channel-status');
            
            // Add loading indicator
            var $spinner = $this.closest('.channel-toggle').find('.spinner');
            if ($spinner.length === 0) {
                $spinner = $('<span class="spinner"></span>');
                $this.closest('.channel-toggle').append($spinner);
            }
            $spinner.addClass('is-active');
            
            // Temporarily disable toggle
            $this.prop('disabled', true);
            
            console.log('Toggling channel', channel, isEnabled);
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_toggle_channel',
                    nonce: conversaai_channels.nonce,
                    channel_type: channel,
                    status: isEnabled ? 1 : 0
                },
                success: function(response) {
                    console.log('Toggle response:', response);
                    
                    if (response.success) {
                        if (isEnabled) {
                            $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
                        } else {
                            $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
                        }
                        
                        var message = response.data && response.data.message ? 
                            response.data.message : 
                            (isEnabled ? 'Channel enabled' : 'Channel disabled');
                            
                        showNotice('success', message);
                    } else {
                        var errorMessage = response.data && response.data.message ? 
                            response.data.message : 
                            conversaai_channels.toggle_error;
                            
                        showNotice('error', errorMessage);
                        
                        // Revert the toggle if there was an error
                        $this.prop('checked', !isEnabled);
                        
                        if (isEnabled) {
                            $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
                        } else {
                            $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showNotice('error', conversaai_channels.ajax_error + ': ' + error);
                    
                    // Revert the toggle
                    $this.prop('checked', !isEnabled);
                    
                    if (isEnabled) {
                        $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
                    } else {
                        $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
                    }
                },
                complete: function() {
                    // Re-enable toggle and hide spinner
                    $this.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Test connection button
        $('.test-connection').on('click', function() {
            var $this = $(this);
            var channel = $this.data('channel');
            var $form = $('#' + channel + '-settings-form');
            var $spinner = $this.closest('.channel-actions').find('.spinner');
            
            // Disable the button and show spinner
            $this.prop('disabled', true);
            $spinner.addClass('is-active');
            
            console.log('Testing connection for', channel);
            
            // Collect form data
            var formData = $form.serializeArray();
            var data = {
                action: 'conversaai_test_channel_connection',
                nonce: conversaai_channels.nonce,
                channel_type: channel
            };
            
            // Add form fields to data
            $.each(formData, function(index, field) {
                data[field.name] = field.value;
            });
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('Test connection response:', response);
                    
                    if (response && response.success) {
                        var message = response.data && response.data.message ? 
                            response.data.message : 
                            'Connection successful';
                            
                        showNotice('success', message);
                        
                        // Display additional details if available
                        if (response.data && response.data.details) {
                            var details = '';
                            
                            if (typeof response.data.details === 'object') {
                                // Format details as a list
                                details += '<ul class="connection-details">';
                                
                                for (var key in response.data.details) {
                                    if (response.data.details.hasOwnProperty(key)) {
                                        // Skip empty values
                                        if (response.data.details[key] === '') continue;
                                        details += '<li><strong>' + key + ':</strong> ' + response.data.details[key] + '</li>';
                                    }
                                }
                                
                                details += '</ul>';
                            } else {
                                details = response.data.details;
                            }
                            
                            showNotice('success', details, false, 10000);
                        }
                    } else {
                        var errorMessage = response && response.data && response.data.message ? 
                            response.data.message : 
                            conversaai_channels.test_error;
                            
                        showNotice('error', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showNotice('error', conversaai_channels.ajax_error + ': ' + error);
                },
                complete: function() {
                    // Re-enable the button and hide spinner
                    $this.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Channel settings form submission
        $('.channel-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var channel = $form.attr('id').replace('-settings-form', '');
            var $saveButton = $form.find('.save-channel');
            var $spinner = $saveButton.siblings('.spinner');
            
            // Debug
            console.log('Form submission triggered for channel: ' + channel);
            
            // Disable the button and show spinner
            $saveButton.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Collect form data
            var formData = {};
            $form.find('input, select, textarea').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                
                if (!name) return;
                
                if ($input.is(':checkbox')) {
                    formData[name] = $input.is(':checked') ? 1 : 0;
                } else {
                    formData[name] = $input.val();
                }
            });
            
            // Add required action params
            formData.action = 'conversaai_save_channel_settings';
            formData.nonce = conversaai_channels.nonce;
            formData.channel_type = channel;
            
            // Debug data
            console.log('Form data being sent:', formData);
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    console.log('Sending AJAX request to save channel settings');
                },
                success: function(response) {
                    console.log('Full AJAX response:', response);
                    
                    if (response.success) {
                        // Handle success
                        var message = response.data && response.data.message ? 
                            response.data.message : 
                            'Settings saved successfully';
                            
                        showNotice('success', message);
                        console.log('Settings saved successfully');
                    } else {
                        // Handle error
                        var errorMessage = '';
                        
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        } else {
                            errorMessage = conversaai_channels.save_error;
                        }
                        
                        showNotice('error', errorMessage);
                        console.error('Save error:', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    
                    showNotice('error', conversaai_channels.ajax_error + ': ' + error);
                },
                complete: function() {
                    // Re-enable the button and hide spinner
                    $saveButton.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Refresh channel stats
        $('#refresh-channel-stats').on('click', function() {
            var $this = $(this);
            var $spinner = $('<span class="spinner is-active" style="float:none;margin:0 10px;"></span>');
            
            // Disable the button and show spinner
            $this.prop('disabled', true);
            $this.after($spinner);
            
            console.log('Refreshing channel stats');
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_get_channel_stats',
                    nonce: conversaai_channels.nonce
                },
                success: function(response) {
                    console.log('Stats response:', response);
                    
                    if (response && response.success && response.data && response.data.stats) {
                        // Update stats
                        var stats = response.data.stats;
                        
                        for (var channel in stats) {
                            if (stats.hasOwnProperty(channel)) {
                                var channelStats = stats[channel];
                                
                                // Update total conversations
                                $('.conversaai-stat-item.' + channel + ' .stat-value.total-count').text(channelStats.total || 0);
                                
                                // Update last 24 hours
                                $('.conversaai-stat-item.' + channel + ' .stat-value.last-24h-count').text(channelStats.last_24h || 0);
                                
                                // Update last 7 days
                                $('.conversaai-stat-item.' + channel + ' .stat-value.last-week-count').text(channelStats.last_week || 0);
                            }
                        }
                        
                        showNotice('success', conversaai_channels.stats_updated);
                    } else {
                        var errorMessage = response && response.data && response.data.message ? 
                            response.data.message : 
                            conversaai_channels.stats_error;
                            
                        showNotice('error', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showNotice('error', conversaai_channels.ajax_error + ': ' + error);
                },
                complete: function() {
                    // Re-enable the button and remove spinner
                    $this.prop('disabled', false);
                    $spinner.remove();
                }
            });
        });
        
        // Helper function for showing notices
        function showNotice(type, message, autoDismiss = true, duration = 5000) {
            var $notice = type === 'success' ? $('#message') : $('#error-message');
            
            // Set the message (allow HTML)
            $notice.find('p').html(message);
            
            // Show the notice
            $notice.fadeIn(300);
            
            // Auto-dismiss after delay
            if (autoDismiss) {
                setTimeout(function() {
                    $notice.fadeOut(300);
                }, duration);
            }
        }
        
        // Dismiss notices when the X is clicked
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut(300);
        });
    });
    
})(jQuery);