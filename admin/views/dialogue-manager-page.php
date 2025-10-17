<?php
/**
 * Dialogue Manager page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-dialogue-manager">

    <h2 class="nav-tab-wrapper">
        <a href="?page=conversaai-pro-dialogue-manager&tab=conversations" class="nav-tab <?php echo $active_tab == 'conversations' ? 'nav-tab-active' : ''; ?>"><?php _e('Conversations', 'conversaai-pro-wp'); ?></a>
        <a href="?page=conversaai-pro-dialogue-manager&tab=learning" class="nav-tab <?php echo $active_tab == 'learning' ? 'nav-tab-active' : ''; ?>"><?php _e('Autonomous Learning', 'conversaai-pro-wp'); ?></a>
        <a href="?page=conversaai-pro-dialogue-manager&tab=trigger_words" class="nav-tab <?php echo $active_tab == 'trigger_words' ? 'nav-tab-active' : ''; ?>"><?php _e('Trigger Words', 'conversaai-pro-wp'); ?></a>
    </h2>

    <?php if ($active_tab == 'conversations'): ?>    

    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Dialogue Manager', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Review and analyze conversations between your AI assistant and users. Extract valuable insights and improve your knowledge base.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-admin-comments"></span>
        </div>
    </div>
        
    <div class="conversaai-filters conversaai-dialogue-filters">
        <div class="conversaai-filter-row">
            <div class="conversaai-filter-group">
                <label for="conversaai-date-from"><?php _e('From:', 'conversaai-pro-wp'); ?></label>
                <input type="date" id="conversaai-date-from" class="conversaai-date-filter">
            </div>
            
            <div class="conversaai-filter-group">
                <label for="conversaai-date-to"><?php _e('To:', 'conversaai-pro-wp'); ?></label>
                <input type="date" id="conversaai-date-to" class="conversaai-date-filter">
            </div>
            
            <div class="conversaai-filter-group">
                <label for="conversaai-channel-filter"><?php _e('Channel:', 'conversaai-pro-wp'); ?></label>
                <select id="conversaai-channel-filter">
                    <option value=""><?php _e('All Channels', 'conversaai-pro-wp'); ?></option>
                    <option value="webchat"><?php _e('Website Chat', 'conversaai-pro-wp'); ?></option>
                    <option value="whatsapp"><?php _e('WhatsApp', 'conversaai-pro-wp'); ?></option>
                    <option value="messenger"><?php _e('Facebook Messenger', 'conversaai-pro-wp'); ?></option>
                    <option value="instagram"><?php _e('Instagram', 'conversaai-pro-wp'); ?></option>
                </select>
            </div>
            
            <div class="conversaai-filter-group">
                <label for="conversaai-success-filter"><?php _e('Success Score:', 'conversaai-pro-wp'); ?></label>
                <div class="conversaai-range-filter">
                    <input type="range" id="conversaai-success-filter" min="0" max="1" step="0.1" value="0">
                    <span id="conversaai-success-value">0%</span>
                </div>
            </div>
            
            <div class="conversaai-filter-actions">
                <button id="conversaai-apply-filters" class="button button-primary"><?php _e('Apply Filters', 'conversaai-pro-wp'); ?></button>
                <button id="conversaai-reset-filters" class="button"><?php _e('Reset', 'conversaai-pro-wp'); ?></button>
            </div>
        </div>
    </div>
    
    <div class="conversaai-dialogue-manager-content">
        <div class="conversaai-dialogue-sidebar">
            <div class="conversaai-bulk-actions">
                <select id="conversaai-bulk-action">
                    <option value=""><?php _e('Bulk Actions', 'conversaai-pro-wp'); ?></option>
                    <option value="extract_knowledge"><?php _e('Extract Knowledge', 'conversaai-pro-wp'); ?></option>
                    <option value="approve"><?php _e('Approve', 'conversaai-pro-wp'); ?></option>
                    <option value="disapprove"><?php _e('Disapprove', 'conversaai-pro-wp'); ?></option>
                    <option value="delete"><?php _e('Delete', 'conversaai-pro-wp'); ?></option>
                </select>
                <button id="conversaai-apply-bulk-action" class="button"><?php _e('Apply', 'conversaai-pro-wp'); ?></button>
            </div>
            
            <div class="conversaai-dialogue-list-header">
                <label>
                    <input type="checkbox" id="conversaai-select-all">
                    <?php _e('Select All', 'conversaai-pro-wp'); ?>
                </label>
                <div class="conversaai-dialogue-count">
                    <span id="conversaai-selected-count">0</span> <?php _e('selected', 'conversaai-pro-wp'); ?>
                </div>
            </div>
            
            <div class="conversaai-dialogue-list">
                <?php if (empty($recent_conversations)): ?>
                    <div class="conversaai-no-dialogues">
                        <?php _e('No conversations found.', 'conversaai-pro-wp'); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_conversations as $convo): ?>
                        <div class="conversaai-dialogue-item" data-session-id="<?php echo esc_attr($convo['session_id']); ?>">
                            <div class="conversaai-dialogue-checkbox">
                                <input type="checkbox" class="conversaai-dialogue-select" data-session-id="<?php echo esc_attr($convo['session_id']); ?>">
                            </div>
                            <div class="conversaai-dialogue-info">
                                <div class="conversaai-dialogue-meta">
                                    <span class="conversaai-dialogue-channel">
                                        <?php echo esc_html(ucfirst($convo['channel'])); ?>
                                    </span>
                                    <span class="conversaai-dialogue-date">
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($convo['created_at']))); ?>
                                    </span>
                                </div>
                                <div class="conversaai-dialogue-user">
                                    <?php echo esc_html($convo['user_name']); ?>
                                    <?php if (!empty($convo['user_email'])): ?>
                                        <span class="conversaai-user-email">(<?php echo esc_html($convo['user_email']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversaai-dialogue-success-score">
                                    <div class="conversaai-score-label"><?php _e('Success:', 'conversaai-pro-wp'); ?></div>
                                    <div class="conversaai-score-bar">
                                        <div class="conversaai-score-fill" style="width: <?php echo esc_attr($convo['success_score'] * 100); ?>%;"></div>
                                    </div>
                                    <div class="conversaai-score-value"><?php echo esc_html(number_format($convo['success_score'] * 100, 0)); ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="conversaai-pagination">
                <button id="conversaai-prev-page" class="button" disabled><?php _e('Previous', 'conversaai-pro-wp'); ?></button>
                <span id="conversaai-page-info"><?php _e('Page', 'conversaai-pro-wp'); ?> 1</span>
                <button id="conversaai-next-page" class="button" <?php echo count($recent_conversations) < 20 ? 'disabled' : ''; ?>><?php _e('Next', 'conversaai-pro-wp'); ?></button>
            </div>
        </div>
        
        <div class="conversaai-dialogue-detail">
            <div class="conversaai-dialogue-placeholder">
                <div class="conversaai-placeholder-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <p><?php _e('Select a conversation from the list to view details.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-dialogue-content" style="display: none;">
                <div class="conversaai-dialogue-header">
                <h2 class="conversaai-dialogue-title"><?php _e('Conversation Details', 'conversaai-pro-wp'); ?></h2>
                    <div class="conversaai-dialogue-actions">
                        <button class="button conversaai-extract-knowledge"><?php _e('Extract Knowledge', 'conversaai-pro-wp'); ?></button>
                        <button class="button conversaai-export-dialogue"><?php _e('Export', 'conversaai-pro-wp'); ?></button>
                        <button class="button conversaai-flag-dialogue"><?php _e('Flag for Review', 'conversaai-pro-wp'); ?></button>
                        <button class="button conversaai-archive-dialogue"><?php _e('Archive', 'conversaai-pro-wp'); ?></button>
                        <button class="button button-link-delete conversaai-delete-dialogue"><?php _e('Delete', 'conversaai-pro-wp'); ?></button>
                    </div>
                </div>
                
                <div class="conversaai-dialogue-info-panel">
                    <div class="conversaai-dialogue-user-info">
                        <h3><?php _e('User Information', 'conversaai-pro-wp'); ?></h3>
                        <div class="conversaai-info-grid conversaai-user-details">
                            <!-- User details will be filled by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="conversaai-dialogue-metrics">
                        <h3><?php _e('Conversation Metrics', 'conversaai-pro-wp'); ?></h3>
                        <div class="conversaai-info-grid conversaai-metrics-details">
                            <!-- Metrics will be filled by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <div class="conversaai-messages-container">
                    <h3><?php _e('Messages', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-messages">
                        <!-- Messages will be filled by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Variables
    let currentPage = 1;
    let totalPages = 1;
    let selectedDialogues = [];
    let currentSessionId = '';
    
    // Initialize with current date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $('#conversaai-date-from').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#conversaai-date-to').val(today.toISOString().split('T')[0]);
    
    // Success score slider
    $('#conversaai-success-filter').on('input', function() {
        const value = $(this).val();
        $('#conversaai-success-value').text(Math.round(value * 100) + '%');
    });
    
    // Apply filters
    $('#conversaai-apply-filters').on('click', function() {
        currentPage = 1;
        loadDialogues();
    });
    
    // Reset filters
    $('#conversaai-reset-filters').on('click', function() {
        $('#conversaai-date-from').val(thirtyDaysAgo.toISOString().split('T')[0]);
        $('#conversaai-date-to').val(today.toISOString().split('T')[0]);
        $('#conversaai-channel-filter').val('');
        $('#conversaai-success-filter').val(0);
        $('#conversaai-success-value').text('0%');
        
        currentPage = 1;
        loadDialogues();
    });
    
    // Pagination
    $('#conversaai-prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadDialogues();
        }
    });
    
    $('#conversaai-next-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadDialogues();
        }
    });
    
    // Select all dialogues
    $('#conversaai-select-all').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.conversaai-dialogue-select').prop('checked', isChecked);
        
        if (isChecked) {
            selectedDialogues = $('.conversaai-dialogue-select').map(function() {
                return $(this).data('session-id');
            }).get();
        } else {
            selectedDialogues = [];
        }
        
        updateSelectedCount();
    });
    
    // Select individual dialogue
    $(document).on('change', '.conversaai-dialogue-select', function() {
        const sessionId = $(this).data('session-id');
        
        if ($(this).prop('checked')) {
            if (!selectedDialogues.includes(sessionId)) {
                selectedDialogues.push(sessionId);
            }
        } else {
            const index = selectedDialogues.indexOf(sessionId);
            if (index !== -1) {
                selectedDialogues.splice(index, 1);
            }
        }
        
        updateSelectedCount();
    });
    
    // Click on dialogue item
    $(document).on('click', '.conversaai-dialogue-item', function(e) {
        // Don't trigger if clicking on checkbox
        if ($(e.target).hasClass('conversaai-dialogue-select') || $(e.target).closest('.conversaai-dialogue-checkbox').length) {
            return;
        }
        
        const sessionId = $(this).data('session-id');
        loadDialogueDetails(sessionId);
        
        // Highlight selected dialogue
        $('.conversaai-dialogue-item').removeClass('active');
        $(this).addClass('active');
    });
    
    // Bulk actions
    $('#conversaai-apply-bulk-action').on('click', function() {
        const action = $('#conversaai-bulk-action').val();
        
        if (!action || selectedDialogues.length === 0) {
            alert('<?php _e('Please select an action and at least one conversation.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Confirm the action
        let confirmMessage = '';
        switch (action) {
            case 'delete':
                confirmMessage = '<?php _e('Are you sure you want to delete the selected conversations? This cannot be undone.', 'conversaai-pro-wp'); ?>';
                break;
            case 'archive':
                confirmMessage = '<?php _e('Are you sure you want to archive the selected conversations?', 'conversaai-pro-wp'); ?>';
                break;
            case 'flag':
                confirmMessage = '<?php _e('Are you sure you want to flag the selected conversations for review?', 'conversaai-pro-wp'); ?>';
                break;
            case 'extract_knowledge':
                confirmMessage = '<?php _e('Are you sure you want to extract knowledge from the selected conversations? This will add entries to your knowledge base for review.', 'conversaai-pro-wp'); ?>';
                break;    
        }
        
        if (confirm(confirmMessage)) {
            applyBulkAction(action, selectedDialogues);
        }
    });
    
    // Individual dialogue actions
    $('.conversaai-delete-dialogue').on('click', function() {
        if (!currentSessionId) return;
        
        if (confirm('<?php _e('Are you sure you want to delete this conversation? This cannot be undone.', 'conversaai-pro-wp'); ?>')) {
            applyBulkAction('delete', [currentSessionId]);
        }
    });
    
    // Extract Knowledge button
    $('.conversaai-extract-knowledge').on('click', function() {
        if (!currentSessionId) return;
        
        if (confirm('<?php _e('Are you sure you want to extract knowledge from this conversation?', 'conversaai-pro-wp'); ?>')) {
            // Show processing overlay
            $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Extracting...', 'conversaai-pro-wp'); ?></div></div>');
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_extract_knowledge',
                    nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                    source: 'specific',
                    session_id: currentSessionId
                },
                success: function(response) {
                    $('#conversaai-processing-overlay').remove();
                    
                    if (response.success) {
                        alert(sprintf(
                            '<?php _e('Knowledge extraction completed. %d entries extracted, %d auto-approved.', 'conversaai-pro-wp'); ?>', 
                            response.data.total_extracted, 
                            response.data.auto_approved
                        ));
                    } else {
                        alert(response.data.message || '<?php _e('Error extracting knowledge.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function() {
                    $('#conversaai-processing-overlay').remove();
                    alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                }
            });
        }
    });
    
    // Flag dialogue button
    $('.conversaai-flag-dialogue').on('click', function() {
        if (!currentSessionId) return;
        
        if (confirm('<?php _e('Are you sure you want to flag this conversation for review?', 'conversaai-pro-wp'); ?>')) {
            applyBulkAction('flag', [currentSessionId]);
        }
    });
    
    // Archive dialogue button
    $('.conversaai-archive-dialogue').on('click', function() {
        if (!currentSessionId) return;
        
        if (confirm('<?php _e('Are you sure you want to archive this conversation?', 'conversaai-pro-wp'); ?>')) {
            applyBulkAction('archive', [currentSessionId]);
        }
    });
    
    // Export dialogue button
    $('.conversaai-export-dialogue').on('click', function() {
        if (!currentSessionId) return;
        
        // Generate export filename
        const filename = 'conversation-' + currentSessionId.substring(0, 8) + '.txt';
        
        // Collect dialogue content
        let dialogueText = '=== Conversation Export ===\n';
        dialogueText += 'Session ID: ' + currentSessionId + '\n';
        dialogueText += 'Export Date: ' + new Date().toLocaleString() + '\n\n';
        
        // Add user info if available
        const userInfo = $('.conversaai-user-details').text().trim();
        if (userInfo) {
            dialogueText += '=== User Information ===\n' + userInfo + '\n\n';
        }
        
        // Add metrics info
        const metricsInfo = $('.conversaai-metrics-details').text().trim();
        if (metricsInfo) {
            dialogueText += '=== Conversation Metrics ===\n' + metricsInfo + '\n\n';
        }
        
        // Add messages
        dialogueText += '=== Messages ===\n';
        $('.conversaai-message').each(function() {
            const isUser = $(this).hasClass('conversaai-user-message');
            const role = isUser ? 'User' : 'Assistant';
            const content = $(this).find('.conversaai-message-content').text().trim();
            const time = $(this).find('.conversaai-message-time').text().trim();
            
            dialogueText += '[' + role + '] ' + (time ? '(' + time + ')' : '') + '\n';
            dialogueText += content + '\n\n';
        });
        
        // Create and trigger download
        const blob = new Blob([dialogueText], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    });
    
    // Load dialogues function
    function loadDialogues() {
        let startDate = $('#conversaai-date-from').val();
        let endDate = $('#conversaai-date-to').val();
        const channel = $('#conversaai-channel-filter').val();
        const minScore = parseFloat($('#conversaai-success-filter').val());
        
        // Ensure dates are properly formatted
        if (startDate) {
            // Make sure we use the beginning of the day
            startDate = startDate + ' 00:00:00';
        }
        
        if (endDate) {
            // Make sure we use the end of the day
            endDate = endDate + ' 23:59:59';
        }
        
        // Log the date range being used (helpful for debugging)
        console.log('Date range:', { startDate, endDate });
        
        // Show loading state
        $('.conversaai-dialogue-list').html('<div class="conversaai-loading"><?php _e('Loading conversations...', 'conversaai-pro-wp'); ?></div>');
        
        // Reset selected dialogues
        selectedDialogues = [];
        $('#conversaai-select-all').prop('checked', false);
        updateSelectedCount();
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_dialogues',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                page: currentPage,
                per_page: 20,
                start_date: startDate,
                end_date: endDate,
                channel: channel,
                min_score: minScore,
                max_score: 1
            },
            success: function(response) {
                if (response.success) {
                    totalPages = response.data.pages;
                    
                    // Update pagination
                    $('#conversaai-prev-page').prop('disabled', currentPage <= 1);
                    $('#conversaai-next-page').prop('disabled', currentPage >= totalPages);
                    $('#conversaai-page-info').text('<?php _e('Page', 'conversaai-pro-wp'); ?> ' + currentPage + ' <?php _e('of', 'conversaai-pro-wp'); ?> ' + totalPages);
                    
                    // Update dialogue list
                    if (response.data.dialogues.length === 0) {
                        $('.conversaai-dialogue-list').html('<div class="conversaai-no-dialogues"><?php _e('No conversations found matching your filters.', 'conversaai-pro-wp'); ?></div>');
                    } else {
                        let html = '';
                        
                        // Ensure conversations are sorted by updated_at in descending order
                        const sortedDialogues = [...response.data.dialogues].sort((a, b) => {
                            return new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at);
                        });
                        
                        sortedDialogues.forEach(function(dialogue) {
                            html += `
                                <div class="conversaai-dialogue-item" data-session-id="${dialogue.session_id}">
                                    <div class="conversaai-dialogue-checkbox">
                                        <input type="checkbox" class="conversaai-dialogue-select" data-session-id="${dialogue.session_id}">
                                    </div>
                                    <div class="conversaai-dialogue-info">
                                        <div class="conversaai-dialogue-meta">
                                            <span class="conversaai-dialogue-channel">
                                                ${dialogue.channel.charAt(0).toUpperCase() + dialogue.channel.slice(1)}
                                            </span>
                                            <span class="conversaai-dialogue-date">
                                                ${new Date(dialogue.updated_at || dialogue.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                        <div class="conversaai-dialogue-user">
                                            ${dialogue.user_name}
                                            ${dialogue.user_email ? `<span class="conversaai-user-email">(${dialogue.user_email})</span>` : ''}
                                        </div>
                                        <div class="conversaai-dialogue-success-score">
                                            <div class="conversaai-score-label"><?php _e('Success:', 'conversaai-pro-wp'); ?></div>
                                            <div class="conversaai-score-bar">
                                                <div class="conversaai-score-fill" style="width: ${dialogue.success_score * 100}%;"></div>
                                            </div>
                                            <div class="conversaai-score-value">${Math.round(dialogue.success_score * 100)}%</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $('.conversaai-dialogue-list').html(html);
                    }
                } else {
                    $('.conversaai-dialogue-list').html('<div class="conversaai-error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('.conversaai-dialogue-list').html('<div class="conversaai-error"><?php _e('Error loading conversations. Please try again.', 'conversaai-pro-wp'); ?></div>');
            }
        });
    }
    
    // Load dialogue details
    function loadDialogueDetails(sessionId) {
        currentSessionId = sessionId;
        
        // Show loading state
        $('.conversaai-dialogue-placeholder').hide();
        $('.conversaai-dialogue-content').show();
        $('.conversaai-messages').html('<div class="conversaai-loading"><?php _e('Loading conversation details...', 'conversaai-pro-wp'); ?></div>');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_dialogue_details',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update user information
                    let userHtml = '';
                    userHtml += `<div class="conversaai-info-label"><?php _e('Name', 'conversaai-pro-wp'); ?></div>`;
                    userHtml += `<div class="conversaai-info-value">${data.user_info.name || '<?php _e('Guest', 'conversaai-pro-wp'); ?>'}</div>`;
                    
                    if (data.user_info.email) {
                        userHtml += `<div class="conversaai-info-label"><?php _e('Email', 'conversaai-pro-wp'); ?></div>`;
                        userHtml += `<div class="conversaai-info-value">${data.user_info.email}</div>`;
                    }
                    
                    if (data.user_info.role) {
                        userHtml += `<div class="conversaai-info-label"><?php _e('Role', 'conversaai-pro-wp'); ?></div>`;
                        userHtml += `<div class="conversaai-info-value">${data.user_info.role}</div>`;
                    }
                    
                    userHtml += `<div class="conversaai-info-label"><?php _e('IP Address', 'conversaai-pro-wp'); ?></div>`;
                    userHtml += `<div class="conversaai-info-value">${data.metadata.ip_address || '<?php _e('Not available', 'conversaai-pro-wp'); ?>'}</div>`;
                    
                    if (data.metadata.page_url) {
                        userHtml += `<div class="conversaai-info-label"><?php _e('Page URL', 'conversaai-pro-wp'); ?></div>`;
                        userHtml += `<div class="conversaai-info-value">${data.metadata.page_url}</div>`;
                    }
                    
                    userHtml += `<div class="conversaai-info-label"><?php _e('User Agent', 'conversaai-pro-wp'); ?></div>`;
                    userHtml += `<div class="conversaai-info-value">${data.metadata.user_agent || '<?php _e('Not available', 'conversaai-pro-wp'); ?>'}</div>`;
                    
                    $('.conversaai-user-details').html(userHtml);
                    
                    // Update metrics
                    let metricsHtml = '';
                    metricsHtml += `<div class="conversaai-info-label"><?php _e('Channel', 'conversaai-pro-wp'); ?></div>`;
                    metricsHtml += `<div class="conversaai-info-value">${data.metadata.channel || 'webchat'}</div>`;
                    
                    metricsHtml += `<div class="conversaai-info-label"><?php _e('Started', 'conversaai-pro-wp'); ?></div>`;
                    metricsHtml += `<div class="conversaai-info-value">${new Date(data.metadata.started_at).toLocaleString()}</div>`;
                    
                    metricsHtml += `<div class="conversaai-info-label"><?php _e('Duration', 'conversaai-pro-wp'); ?></div>`;
                    
                    // Calculate duration if possible
                    let duration = '<?php _e('Not available', 'conversaai-pro-wp'); ?>';
                    if (data.messages.length >= 2) {
                        const firstMsg = new Date(data.messages[0].timestamp);
                        const lastMsg = new Date(data.messages[data.messages.length - 1].timestamp);
                        const diffMs = lastMsg - firstMsg;
                        const diffMins = Math.floor(diffMs / 60000);
                        const diffSecs = Math.floor((diffMs % 60000) / 1000);
                        duration = `${diffMins}m ${diffSecs}s`;
                    }
                    metricsHtml += `<div class="conversaai-info-value">${duration}</div>`;
                    
                    metricsHtml += `<div class="conversaai-info-label"><?php _e('Messages', 'conversaai-pro-wp'); ?></div>`;
                    metricsHtml += `<div class="conversaai-info-value">${data.messages.length}</div>`;
                    
                    metricsHtml += `<div class="conversaai-info-label"><?php _e('Success Score', 'conversaai-pro-wp'); ?></div>`;
                    metricsHtml += `<div class="conversaai-info-value">
                                        <div class="conversaai-score-bar">
                                            <div class="conversaai-score-fill" style="width: ${(data.metadata.success_score || 0) * 100}%;"></div>
                                        </div>
                                        <span>${Math.round((data.metadata.success_score || 0) * 100)}%</span>
                                    </div>`;
                    
                    $('.conversaai-metrics-details').html(metricsHtml);
                    
                    // Update messages
                    let messagesHtml = '';
                    data.messages.forEach(function(message) {
                        const isUser = message.role === 'user';
                        const messageClass = isUser ? 'conversaai-user-message' : 'conversaai-assistant-message';
                        
                        messagesHtml += `
                            <div class="conversaai-message ${messageClass}">
                                <div class="conversaai-message-content">
                                    ${message.content}
                                </div>
                                <div class="conversaai-message-meta">
                                    <span class="conversaai-message-time">
                                        ${message.timestamp ? new Date(message.timestamp).toLocaleString() : ''}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('.conversaai-messages').html(messagesHtml);
                } else {
                    $('.conversaai-messages').html('<div class="conversaai-error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('.conversaai-messages').html('<div class="conversaai-error"><?php _e('Error loading conversation details. Please try again.', 'conversaai-pro-wp'); ?></div>');
            }
        });
    }
    
    // Apply bulk action
    function applyBulkAction(action, sessionIds) {
        if (!action || sessionIds.length === 0) {
            console.warn('[ConversaAI Pro Dialogue] No action or session IDs provided');
            alert('<?php _e('Please select at least one conversation and an action.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Ensure sessionIds is properly formatted for the server
        if (!Array.isArray(sessionIds)) {
            console.error('SessionIds is not an array:', sessionIds);
            alert('<?php _e('Error: Session IDs not properly formatted.', 'conversaai-pro-wp'); ?>');
            return;
        }

        // Log action and IDs for debugging
        console.log('Bulk action details:', {
            action: action,
            sessionIds: sessionIds,
            length: sessionIds.length
        });
        
        try {
            console.log('[ConversaAI Pro Dialogue] Applying bulk action:', { action, sessionIds });
            
            // Show loading status
            $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'conversaai_bulk_action_dialogues',
                    nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                    action_type: action,
                    session_ids: sessionIds
                },
                beforeSend: function() {
                    console.log('[ConversaAI Pro Dialogue] Sending AJAX for bulk action');
                },
                success: function(response) {
                    console.log('[ConversaAI Pro Dialogue] Bulk action response:', response);
                    $('#conversaai-processing-overlay').remove();
                    
                    if (response.success) {
                        alert(response.data.message);
                        
                        // Reset selected dialogues
                        selectedDialogues = [];
                        updateSelectedCount();
                        
                        // Reload dialogues
                        loadDialogues();
                        
                        // Reset detail view if current session was affected
                        if (sessionIds.includes(currentSessionId)) {
                            $('.conversaai-dialogue-content').hide();
                            $('.conversaai-dialogue-placeholder').show();
                            currentSessionId = '';
                        }
                    } else {
                        console.error('[ConversaAI Pro Dialogue] Bulk action failed:', response.data);
                        alert(response.data.message || '<?php _e('Error processing action.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[ConversaAI Pro Dialogue] AJAX error details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        request: {
                            action: 'conversaai_bulk_action_dialogues',
                            action_type: action,
                            session_ids_count: sessionIds.length
                        }
                    });

                    // Try to parse JSON response for more details
                    let errorMsg = '<?php _e('Error processing action. Please try again.', 'conversaai-pro-wp'); ?>';
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                            errorMsg = jsonResponse.data.message;
                        }
                    } catch (e) {
                        // Use default error message if parsing fails
                    }

                    $('#conversaai-processing-overlay').remove();
                    alert(errorMsg);
                },
                complete: function() {
                    // Always remove the overlay in case of any issues
                    $('#conversaai-processing-overlay').remove();
                }
            });
        } catch (error) {
            console.error('[ConversaAI Pro Dialogue] Bulk action error:', {
                message: error.message,
                stack: error.stack
            });
            $('#conversaai-processing-overlay').remove();
            alert('<?php _e('Unexpected error during bulk action.', 'conversaai-pro-wp'); ?>');
        }
    }
    
    // Update selected count
    function updateSelectedCount() {
        $('#conversaai-selected-count').text(selectedDialogues.length);
    }
    
    // Helper function for string formatting
    function sprintf(format) {
        let args = Array.prototype.slice.call(arguments, 1);
        return format.replace(/%(\d+)\$d|%d/g, function(match, number) {
            if (match === '%d') {
                return args.shift() !== undefined ? args.shift() : '';
            }
            const num = parseInt(number, 10) - 1;
            return num >= 0 && num < args.length ? args[num] : '';
        });
    }
    
    // Load initial dialogues
    loadDialogues();
});
</script>

<style>
/* Dialogue Manager styles */
.conversaai-dialogue-manager-content {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.conversaai-dialogue-filters {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.conversaai-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.conversaai-filter-group {
    margin-bottom: 10px;
}

.conversaai-filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.conversaai-range-filter {
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-filter-actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

.conversaai-dialogue-sidebar {
    width: 40%;
    max-width: 400px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
}

.conversaai-bulk-actions {
    padding: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.conversaai-dialogue-list-header {
    padding: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
}

.conversaai-dialogue-list {
    flex: 1;
    overflow-y: auto;
    max-height: 600px;
}

.conversaai-dialogue-item {
    padding: 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    display: flex;
    gap: 10px;
    transition: background-color 0.2s ease;
}

.conversaai-dialogue-item:hover {
    background-color: #f9f9f9;
}

.conversaai-dialogue-item.active {
    background-color: #f0f7ff;
    border-left: 3px solid #4c66ef;
}

.conversaai-dialogue-info {
    flex: 1;
}

.conversaai-dialogue-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.conversaai-dialogue-channel {
    font-weight: 500;
    color: #4c66ef;
}

.conversaai-dialogue-date {
    font-size: 12px;
    color: #777;
}

.conversaai-dialogue-user {
    margin-bottom: 8px;
}

.conversaai-user-email {
    color: #777;
}

.conversaai-dialogue-success-score {
    display: flex;
    align-items: center;
    gap: 8px;
}

.conversaai-score-label {
    font-size: 12px;
    color: #555;
    min-width: 60px;
}

.conversaai-score-bar {
    flex: 1;
    height: 8px;
    background-color: #eee;
    border-radius: 4px;
    overflow: hidden;
}

.conversaai-score-fill {
    height: 100%;
    background-color: #4CAF50;
    border-radius: 4px;
}

.conversaai-score-value {
    font-size: 12px;
    font-weight: 500;
    min-width: 35px;
    text-align: right;
}

.conversaai-pagination {
    padding: 10px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversaai-dialogue-detail {
    flex: 1;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    overflow-y: auto;
    max-height: 700px;
}

.conversaai-dialogue-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #777;
}

.conversaai-placeholder-icon {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ddd;
}

.conversaai-placeholder-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}

.conversaai-dialogue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.conversaai-dialogue-title {
    margin: 0;
}

.conversaai-dialogue-actions {
    display: flex;
    gap: 10px;
}

.conversaai-dialogue-info-panel {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.conversaai-dialogue-user-info,
.conversaai-dialogue-metrics {
    flex: 1;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 15px;
}

.conversaai-info-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 8px;
    margin-top: 10px;
}

.conversaai-info-label {
    font-weight: 500;
    color: #555;
}

.conversaai-messages-container {
    margin-top: 20px;
}

.conversaai-messages {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.conversaai-message {
    max-width: 80%;
    padding: 12px 15px;
    border-radius: 8px;
}

.conversaai-user-message {
    align-self: flex-end;
    background-color: #e1ebff;
    margin-left: 20%;
}

.conversaai-assistant-message {
    align-self: flex-start;
    background-color: #f0f4ff;
    margin-right: 20%;
}

.conversaai-message-meta {
    font-size: 11px;
    color: #777;
    margin-top: 5px;
    text-align: right;
}

.conversaai-loading {
    padding: 20px;
    text-align: center;
    color: #777;
}

.conversaai-error {
    padding: 20px;
    text-align: center;
    color: #dc3545;
}

.conversaai-no-dialogues {
    padding: 20px;
    text-align: center;
    color: #777;
}

#conversaai-processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.conversaai-processing-message {
    background-color: white;
    padding: 20px 30px;
    border-radius: 4px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

/* Responsive styles */
@media (max-width: 992px) {
    .conversaai-dialogue-manager-content {
        flex-direction: column;
    }
    
    .conversaai-dialogue-sidebar {
        width: 100%;
        max-width: none;
        margin-bottom: 20px;
    }
}
</style>