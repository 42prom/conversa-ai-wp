// WooCommerce Display Settings Preview
jQuery(document).ready(function($) {
    if ($('.conversaai-woocommerce-display-settings').length) {
        function updateProductDisplayPreview() {
            const name = 'Sample Product';
            const price = $('#woocommerce_price_format').val() || '€49.99';
            const productIntro = ($('#product_intro').val() || '"%s" is a WooCommerce product.').replace('%s', name);
            const productPrice = ($('#product_price').val() || 'Priced at %s.').replace('%s', price);
            const linkColor = $('#link_color').val() || '#4c66ef';
            const productLink = ($('#product_link').val() || 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.')
                .replace('%1$s', '#')
                .replace('%2$s', linkColor);
            
            let categoriesText = '';
            if ($('#show_categories').is(':checked')) {
                categoriesText = ' ' + ($('#categories_format').val() || 'Product categories: %s.').replace('%s', 'Category 1, Category 2');
            }
            
            $('#product-display-preview').html(
                productIntro + ' ' + productPrice + categoriesText + ' ' + productLink
            );
        }
        
        // Update preview on input change
        $('.conversaai-woocommerce-display-settings input, .conversaai-woocommerce-display-settings select').on('input change', updateProductDisplayPreview);
        
        // Initial update
        updateProductDisplayPreview();
    }

    // Generic tab initialization function
    function initTabs(tabContainer, contentContainer) {
        $(tabContainer).on('click', function() {
            const tabId = $(this).data('tab');
            
            // Activate the tab
            $(tabContainer).removeClass('active');
            $(this).addClass('active');
            
            // Show the tab content
            $(contentContainer).removeClass('active').hide();
            $('#' + tabId).addClass('active').fadeIn(300);
            
            // Store the active tab in localStorage if possible
            if (typeof(Storage) !== "undefined") {
                const storageKey = 'conversaai_active_tab_' + window.location.pathname.replace(/\//g, '_');
                localStorage.setItem(storageKey, tabId);
            }
            
            return false; // Prevent default action
        });
        
        // Restore the active tab from localStorage if possible
        if (typeof(Storage) !== "undefined") {
            const storageKey = 'conversaai_active_tab_' + window.location.pathname.replace(/\//g, '_');
            const activeTab = localStorage.getItem(storageKey);
            
            if (activeTab && $(tabContainer + '[data-tab="' + activeTab + '"]').length) {
                $(tabContainer + '[data-tab="' + activeTab + '"]').trigger('click');
            }
        }
    }
    
    // Initialize any new tab navigation on the page
    // Check for different tab selectors that might exist across pages
    if ($('.conversaai-tab[data-tab]').not('#trigger-word-modal .conversaai-tab').length) {
        initTabs('.conversaai-tab[data-tab]:not(#trigger-word-modal .conversaai-tab)', '.conversaai-tab-content[data-tab-content]');
    }
    
    if ($('.conversaai-settings-tab').length) {
        initTabs('.conversaai-settings-tab', '.conversaai-settings-tab-content');
    }
    
    // Modal functionality
    $('.conversaai-modal-trigger').on('click', function() {
        const modalId = $(this).data('modal');
        $('#' + modalId).fadeIn(200);
    });
    
    $('.conversaai-modal-close, .conversaai-modal-cancel').on('click', function() {
        $(this).closest('.conversaai-modal').fadeOut(200);
    });
    
    // Close modal on backdrop click
    $('.conversaai-modal').on('click', function(e) {
        if ($(e.target).hasClass('conversaai-modal')) {
            $(this).fadeOut(200);
        }
    });
    
    // Add a common notice display function
    window.showConversaAINotice = function(message, type = 'info', duration = 5000) {
        // Remove any existing notices with the same message to prevent duplicates
        $('.conversaai-notice:contains("' + message + '")').remove();
        
        // Create the notice
        const $notice = $('<div class="conversaai-notice ' + type + '">' + message + '</div>')
            .hide()
            .prependTo('.wrap')
            .slideDown(300);
        
        // Automatically dismiss after duration
        if (duration > 0) {
            setTimeout(function() {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            }, duration);
        }
        
        // Add a close button
        const $closeButton = $('<span class="dashicons dashicons-dismiss" style="margin-left: auto; cursor: pointer;"></span>')
            .appendTo($notice)
            .on('click', function() {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            });
    };

    function adjustContentHeights() {
        // Reset heights first
        $('.conversaai-content-container').css('min-height', '');
        
        // Get the window height
        const windowHeight = $(window).height();
        // Calculate a reasonable minimum height (80% of viewport minus header space)
        const minHeight = Math.max(500, windowHeight * 0.8 - 200);
        
        // Apply minimum height to content containers
        $('.conversaai-content-container').css('min-height', minHeight + 'px');
    }
    
    // Run on page load
    adjustContentHeights();
    
    // Run when window is resized
    $(window).on('resize', function() {
        adjustContentHeights();
    });
    
    // Run when tabs are changed
    $('.nav-tab, .conversaai-tab').on('click', function() {
        setTimeout(adjustContentHeights, 100);
    });
});