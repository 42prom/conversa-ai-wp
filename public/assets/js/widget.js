/**
 * Chat widget functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/public/assets/js
 */

(function($) {
    'use strict';

    // Chat widget functionality
    $(document).ready(function() {
                  
        // Network connection detection and handling
        let isLowBandwidth = false;
        let connectionType = 'unknown';

        // Check if the Network Information API is available
        if (navigator.connection) {
            // Set up connection monitoring
            updateConnectionInfo();
            
            // Update when connection changes
            navigator.connection.addEventListener('change', updateConnectionInfo);
        }

        // Function to update connection information
        function updateConnectionInfo() {
            if (!navigator.connection) return;
            
            connectionType = navigator.connection.effectiveType || 'unknown';
            
            // Consider slow connections to be 2G and sometimes 3G
            isLowBandwidth = (connectionType === '2g' || connectionType === 'slow-2g');
            
            // Adjust UI for slow connections
            if (isLowBandwidth) {
                // Add a class to the widget for low-bandwidth optimizations
                $('.conversaai-pro-widget-container').addClass('low-bandwidth');
                
                // Potentially show a small indicator to the user
                if ($('.connection-indicator').length === 0) {
                    $('<div class="connection-indicator">Slow connection detected</div>')
                        .appendTo('.conversaai-pro-widget')
                        .fadeOut(5000);
                }
            } else {
                $('.conversaai-pro-widget-container').removeClass('low-bandwidth');
            }
            
            console.log('Connection type:', connectionType);
        }

        // Mobile-specific handling
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        if (isMobile) {
            // Add mobile-specific class to enable special CSS rules
            $('.conversaai-pro-widget-container').addClass('mobile-device');
            
            // For very small screens, adjust the widget size
            if (window.innerWidth < 360) {
                $('.conversaai-pro-widget-container').addClass('very-small-screen');
            }
            
            // Handle visibility changes (when app goes to background on mobile)
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible' && isSending) {
                    // The page became visible again but we were in the middle of sending
                    // Consider refreshing the conversation or showing a status update
                    const $statusMessage = $('<div>').addClass('conversaai-pro-system-message')
                        .text('Connection status being verified...');
                    $messagesContainer.append($statusMessage);
                    
                    // Check if we still have a valid session
                    checkSessionStatus();
                }
            });
        }

        // Function to check session status after page visibility changes
        function checkSessionStatus() {
            if (!sessionId) return;
            
            $.ajax({
                url: conversaai_pro.ajax_url,
                type: 'POST',
                data: {
                    action: 'conversaai_get_conversation',
                    nonce: conversaai_pro.nonce,
                    session_id: sessionId
                },
                timeout: 10000, // Short timeout for status check
                success: function(response) {
                    // If successful, the session is still valid
                    $('.conversaai-pro-system-message').remove();
                    
                    // If we were in the middle of sending, but no new messages appeared
                    // Consider informing the user they may need to retry
                    if (isSending) {
                        isSending = false; // Reset the sending flag
                        const $retryNotice = $('<div>').addClass('conversaai-pro-system-message')
                            .text('Your previous message may not have been sent. Please try again if needed.');
                        $messagesContainer.append($retryNotice);
                        
                        // Remove notice after a few seconds
                        setTimeout(function() {
                            $retryNotice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    }
                },
                error: function() {
                    // If error, inform user and reset session
                    $('.conversaai-pro-system-message').text('Connection lost. Please refresh the page to start a new conversation.');
                }
            });
        }
        
        // Variables
        let sessionId = localStorage.getItem('conversaai_pro_session_id') || '';
        let isOpen = localStorage.getItem('conversaai_pro_widget_open') === 'true';
        let isSending = false;

        // DOM elements
        const $widget = $('.conversaai-pro-widget-container');
        const $toggleButton = $('.conversaai-pro-toggle-button');
        const $closeButton = $('.conversaai-pro-close-button');
        const $messagesContainer = $('.conversaai-pro-messages');
        const $input = $('.conversaai-pro-input');
        const $sendButton = $('.conversaai-pro-send-button');

        // Initialize widget state
        if (isOpen) {
            toggleWidget(); // Open the widget
        }

        // Helper functions
        function addMessage(message, isUser = false) {
            const $messageDiv = $('<div>').addClass(isUser ? 'conversaai-pro-user-message' : 'conversaai-pro-bot-message');
            const $messageContent = $('<div>').addClass('conversaai-pro-message-content').html(message);
            const $avatar = $('<div>').addClass('conversaai-pro-avatar');
            
            // Apply correct avatar background
            if (isUser && userAvatar) {
                $avatar.css('background-image', `url(${userAvatar})`);
            } else if (!isUser && botAvatar) {
                $avatar.css('background-image', `url(${botAvatar})`);
            }
            
            const $messageWrapper = $('<div>').addClass('conversaai-pro-message-wrapper');
            $messageWrapper.append($avatar);
            $messageWrapper.append($messageContent);
            $messageDiv.append($messageWrapper);
            
            $messagesContainer.append($messageDiv);
            scrollToBottom();
        }

        function scrollToBottom() {
            const $container = $('.conversaai-pro-messages-container');
            $container.scrollTop($container[0].scrollHeight);
        }

        function toggleWidget() {
            isOpen = !isOpen;
            $widget.toggleClass('conversaai-pro-widget-closed', !isOpen);
            
            // Save state to localStorage
            localStorage.setItem('conversaai_pro_widget_open', isOpen.toString());
            
            if (isOpen) {
                // If this is the first time opening and no welcome message, load conversation history
                if ($messagesContainer.children().length === 0) {
                    loadConversationHistory();
                }
                $input.focus();
            }
        }

        function loadConversationHistory() {
            // If no session ID, this is a brand new conversation
            if (!sessionId) {
                // Add welcome message only for new conversations
                if (typeof conversaai_pro.welcome_message !== 'undefined' && conversaai_pro.welcome_message) {
                    addMessage(conversaai_pro.welcome_message, false);
                }
                return;
            }
            
            // If session ID exists, load conversation from server
            // Add a loading indicator
            const $loading = $('<div>').addClass('conversaai-pro-loading').text(conversaai_pro.connecting_text);
            $messagesContainer.append($loading);
            
            // Make AJAX request to get history
            $.ajax({
                url: conversaai_pro.ajax_url,
                type: 'POST',
                data: {
                    action: 'conversaai_get_conversation',
                    nonce: conversaai_pro.nonce,
                    session_id: sessionId
                },
                success: function(response) {
                    $loading.remove();
                    
                    if (response.success && response.data.messages) {
                        // Clear any existing messages
                        $messagesContainer.empty();
                        
                        // Add messages to the container - already includes welcome message
                        response.data.messages.forEach(function(msg) {
                            addMessage(msg.content, msg.role === 'user');
                        });
                    }
                },
                error: function() {
                    $loading.remove();
                    // Do not add welcome message on error for existing sessions
                }
            });
        }

        // Auto-open functionality
        const autoOpenDelay = parseInt($widget.data('auto-open-delay')) || 0;

        if (autoOpenDelay > 0) {
            setTimeout(function() {
                // Only open if not already open
                if (!isOpen) {
                    toggleWidget();
                }
            }, autoOpenDelay * 1000); // Convert to milliseconds
        }

        // Responsive classes based on settings
        function setResponsiveClasses() {
            // Get settings from data attributes (set by PHP)
            const mobileWidth = $widget.data('mobile-width') || 'full';
            const mobileHeight = $widget.data('mobile-height') || 'full';
            
            // Add appropriate classes
            if (mobileWidth === 'full') {
                $widget.addClass('mobile-full-width');
            } else {
                $widget.removeClass('mobile-full-width');
            }
            
            if (mobileHeight === 'full') {
                $widget.addClass('mobile-full-height');
            } else {
                $widget.removeClass('mobile-full-height');
            }
        }

        // Call on page load
        setResponsiveClasses();

        function sendMessage() {
            const message = $input.val().trim();
            
            if (message === '' || isSending) {
                return;
            }
            
            // Clear input and set sending state
            $input.val('');
            isSending = true;
            
            // Add user message to chat
            addMessage(message, true);
            
            // Add typing indicator
            const $typing = $('<div>').addClass('conversaai-pro-typing').text(conversaai_pro.sending_text);
            $messagesContainer.append($typing);
            scrollToBottom();
            
            // Track retry attempts
            let retryCount = 0;
            const maxRetries = 2;
            
            // Function to handle the AJAX request with retry logic
            function makeRequest() {
                // Check network connection
                if (navigator.onLine === false) {
                    $typing.remove();
                    addMessage('You appear to be offline. Please check your internet connection and try again.', false);
                    isSending = false;
                    return;
                }
                
                // Make AJAX request with improved configuration
                $.ajax({
                    url: conversaai_pro.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'conversaai_send_message',
                        nonce: conversaai_pro.nonce,
                        message: message,
                        session_id: sessionId
                    },
                    timeout: 60000, // 60 second timeout, better for mobile networks
                    success: function(response) {
                        $typing.remove();
                        
                        if (response.success) {
                            // Add bot response
                            addMessage(response.data.message);
                            
                            // Store session ID
                            sessionId = response.data.session_id;
                            localStorage.setItem('conversaai_pro_session_id', sessionId);
                        } else {
                            // Add error message
                            addMessage('Sorry, there was an error processing your message. Please try again.');
                        }
                        
                        isSending = false;
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error);
                        
                        // If it's a timeout or server error and we haven't reached max retries
                        if ((status === 'timeout' || xhr.status >= 500) && retryCount < maxRetries) {
                            retryCount++;
                            // Update typing message to show retry attempt
                            $typing.text(conversaai_pro.sending_text + ' (Retry ' + retryCount + '/' + maxRetries + ')');
                            // Wait 2 seconds before retrying
                            setTimeout(makeRequest, 2000);
                            return;
                        }
                        
                        $typing.remove();
                        
                        // Provide more specific error messages based on the error type
                        if (status === 'timeout') {
                            addMessage('The request took too long to complete. This might be due to a slow internet connection. Please try again later.', false);
                        } else if (xhr.status === 0) {
                            addMessage('Connection lost. Please check your internet connection and try again.', false);
                        } else {
                            addMessage('Sorry, there was an error connecting to the server. Please try again later.', false);
                        }
                        
                        isSending = false;
                    }
                });
            }
            
            // Start the request process
            makeRequest();
        }

        // Event listeners
        $toggleButton.on('click', toggleWidget);
        $closeButton.on('click', toggleWidget);

        // Reset button
        $('.conversaai-pro-reset-button').on('click', function() {
            // Clear conversation history from local storage
            localStorage.removeItem('conversaai_pro_session_id');
            sessionId = '';
            
            // Clear messages container
            $messagesContainer.empty();
            
            // Add success message
            const $successMessage = $('<div>').addClass('conversaai-pro-system-message').text(conversaai_pro.reset_text);
            $messagesContainer.append($successMessage);
            
            // Remove success message after a few seconds
            setTimeout(function() {
                $successMessage.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        });

        // Close widget when clicking outside
        $(document).on('click', function(e) {
            // If widget is open and click is outside widget and toggle button
            if (isOpen && 
                !$(e.target).closest('.conversaai-pro-widget').length && 
                !$(e.target).closest('.conversaai-pro-toggle-button').length) {
                toggleWidget();
            }
        });
        
        $sendButton.on('click', sendMessage);
        
        $input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Get avatar URLs from data attributes set by PHP
        const botAvatar = $widget.data('bot-avatar');
        const userAvatar = $widget.data('user-avatar');

        // Function to apply avatar to message elements
        function applyAvatars() {
            if (botAvatar) {
                $('.conversaai-pro-bot-message .conversaai-pro-avatar').css('background-image', `url(${botAvatar})`);
            }
            
            if (userAvatar) {
                $('.conversaai-pro-user-message .conversaai-pro-avatar').css('background-image', `url(${userAvatar})`);
            }
        }

        // Apply avatars immediately and after new messages are added
        applyAvatars();
    });
})(jQuery);