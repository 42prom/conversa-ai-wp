<?php
/**
 * Learning tab template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-learning conversaai-learning-tab">
    <h1 class="conversaai-page-header"><?php _e('Autonomous Learning', 'conversaai-pro-wp'); ?></h1>
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Autonomous Learning', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Enable your AI assistant to learn from conversations and continuously improve its knowledge base. Review and approve AI-generated knowledge entries.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-welcome-learn-more"></span>
        </div>
    </div>
    
    <div id="learning-settings-message" class="notice is-dismissible" style="display:none;">
        <p></p>
    </div>
    
    <div class="conversaai-learning-header">
        <div class="conversaai-learning-stats">
            <div class="conversaai-stat-box">
                <h3><?php _e('Pending Review', 'conversaai-pro-wp'); ?></h3>
                <div class="conversaai-stat-value"><?php echo esc_html(number_format($stats['pending_count'])); ?></div>
            </div>
            
            <div class="conversaai-stat-box">
                <h3><?php _e('Approved', 'conversaai-pro-wp'); ?></h3>
                <div class="conversaai-stat-value"><?php echo esc_html(number_format($stats['approved_count'])); ?></div>
            </div>
            
            <div class="conversaai-stat-box">
                <h3><?php _e('Auto-Approved', 'conversaai-pro-wp'); ?></h3>
                <div class="conversaai-stat-value"><?php echo esc_html(number_format($stats['auto_approved_count'])); ?></div>
            </div>
            
            <div class="conversaai-stat-box">
                <h3><?php _e('Rejected', 'conversaai-pro-wp'); ?></h3>
                <div class="conversaai-stat-value"><?php echo esc_html(number_format($stats['rejected_count'])); ?></div>
            </div>
            
            <div class="conversaai-stat-box">
                <h3><?php _e('Avg. Confidence', 'conversaai-pro-wp'); ?></h3>
                <div class="conversaai-stat-value"><?php echo esc_html(number_format($stats['avg_confidence'] * 100, 1)); ?>%</div>
            </div>
        </div>
        
        <div class="conversaai-learning-actions">
            <button id="conversaai-settings-toggle" class="button">
                <span class="dashicons dashicons-admin-generic"></span> 
                <?php _e('Learning Settings', 'conversaai-pro-wp'); ?>
            </button>
            <button id="conversaai-refresh-entries" class="button">
                <span class="dashicons dashicons-update"></span> 
                <?php _e('Refresh', 'conversaai-pro-wp'); ?>
            </button>
            <button id="conversaai-extract-knowledge" class="button button-primary">
                <span class="dashicons dashicons-welcome-learn-more"></span> 
                <?php _e('Extract Knowledge', 'conversaai-pro-wp'); ?>
            </button>
        </div>
    </div>
    
    <div id="conversaai-learning-settings-panel" class="conversaai-settings-panel" style="display: none;">
        <h3><?php _e('Autonomous Learning Settings', 'conversaai-pro-wp'); ?></h3>
        
        <form id="conversaai-learning-settings-form">
            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="auto-extraction" name="auto_extraction" 
                        <?php checked(isset($stats['settings']['auto_extraction']) ? $stats['settings']['auto_extraction'] : true); ?>>
                    <?php _e('Automatically extract knowledge from conversations', 'conversaai-pro-wp'); ?>
                </label>
            </div>
            
            <div class="conversaai-form-row">
                <label for="min-confidence"><?php _e('Minimum confidence for extraction:', 'conversaai-pro-wp'); ?></label>
                <div class="conversaai-range-control">
                    <input type="range" id="min-confidence" name="min_confidence" min="0" max="1" step="0.05" 
                        value="<?php echo esc_attr(isset($stats['settings']['min_confidence']) ? $stats['settings']['min_confidence'] : 0.7); ?>">
                    <span id="min-confidence-value"><?php echo esc_html(number_format((isset($stats['settings']['min_confidence']) ? $stats['settings']['min_confidence'] : 0.7) * 100, 0)); ?>%</span>
                </div>
            </div>
            
            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="auto-approve" name="auto_approve"
                        <?php checked(isset($stats['settings']['auto_approve']) ? $stats['settings']['auto_approve'] : false); ?>>
                    <?php _e('Auto-approve high confidence entries', 'conversaai-pro-wp'); ?>
                </label>
            </div>
            
            <div class="conversaai-form-row auto-approve-threshold" <?php echo (!isset($stats['settings']['auto_approve']) || !$stats['settings']['auto_approve']) ? 'style="display: none;"' : ''; ?>>
                <label for="min-auto-approve-confidence"><?php _e('Minimum confidence for auto-approval:', 'conversaai-pro-wp'); ?></label>
                <div class="conversaai-range-control">
                    <input type="range" id="min-auto-approve-confidence" name="min_auto_approve_confidence" min="0.7" max="1" step="0.05"
                        value="<?php echo esc_attr(isset($stats['settings']['min_auto_approve_confidence']) ? $stats['settings']['min_auto_approve_confidence'] : 0.9); ?>">
                    <span id="min-auto-approve-value"><?php echo esc_html(number_format((isset($stats['settings']['min_auto_approve_confidence']) ? $stats['settings']['min_auto_approve_confidence'] : 0.9) * 100, 0)); ?>%</span>
                </div>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Settings', 'conversaai-pro-wp'); ?></button>
                <span id="settings-saving-indicator" style="display:none; margin-left: 10px;">
                    <span class="spinner is-active"></span> <?php _e('Saving...', 'conversaai-pro-wp'); ?>
                </span>
            </div>
        </form>
    </div>
    
    <div class="conversaai-pending-entries">
        <div class="conversaai-bulk-actions">
            <select id="conversaai-knowledge-bulk-action">
                <option value=""><?php _e('Bulk Actions', 'conversaai-pro-wp'); ?></option>
                <option value="approve"><?php _e('Approve', 'conversaai-pro-wp'); ?></option>
                <option value="reject"><?php _e('Reject', 'conversaai-pro-wp'); ?></option>
            </select>
            <button id="conversaai-apply-bulk-action" class="button"><?php _e('Apply', 'conversaai-pro-wp'); ?></button>
            <span id="conversaai-selected-count">0</span> <?php _e('selected', 'conversaai-pro-wp'); ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-cb check-column">
                        <input type="checkbox" id="conversaai-select-all-entries">
                    </th>
                    <th><?php _e('Question', 'conversaai-pro-wp'); ?></th>
                    <th><?php _e('Answer', 'conversaai-pro-wp'); ?></th>
                    <th><?php _e('Source', 'conversaai-pro-wp'); ?></th>
                    <th><?php _e('Confidence', 'conversaai-pro-wp'); ?></th>
                    <th><?php _e('Actions', 'conversaai-pro-wp'); ?></th>
                </tr>
            </thead>
            <tbody id="conversaai-pending-entries-list">
                <?php if (empty($pending_entries)): ?>
                    <tr>
                        <td colspan="6" class="conversaai-no-entries">
                            <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                            <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pending_entries as $entry): ?>
                        <tr data-id="<?php echo esc_attr($entry['id']); ?>">
                            <td class="column-cb check-column">
                                <input type="checkbox" class="conversaai-select-entry" value="<?php echo esc_attr($entry['id']); ?>">
                            </td>
                            <td class="column-question"><?php echo esc_html($entry['question']); ?></td>
                            <td class="column-answer"><?php echo wp_kses_post($entry['answer']); ?></td>
                            <td class="column-source">
                                <?php 
                                $metadata = is_string($entry['metadata']) ? json_decode($entry['metadata'], true) : $entry['metadata'];
                                $session_id = isset($metadata['session_id']) ? $metadata['session_id'] : (isset($entry['session_id']) ? $entry['session_id'] : 'unknown');
                                echo esc_html(substr($session_id, 0, 10) . '...');
                                ?>
                            </td>
                            <td class="column-confidence">
                                <div class="conversaai-confidence-meter">
                                    <div class="conversaai-confidence-bar">
                                        <div class="conversaai-confidence-fill" style="width: <?php echo esc_attr($entry['confidence'] * 100); ?>%;"></div>
                                    </div>
                                    <span class="conversaai-confidence-value"><?php echo esc_html(number_format($entry['confidence'] * 100, 0)); ?>%</span>
                                </div>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button conversaai-approve-entry" data-id="<?php echo esc_attr($entry['id']); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                </button>
                                <button type="button" class="button conversaai-edit-entry" data-id="<?php echo esc_attr($entry['id']); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button conversaai-reject-entry" data-id="<?php echo esc_attr($entry['id']); ?>">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Entry Modal -->
<div id="conversaai-edit-entry-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2><?php _e('Edit Knowledge Entry', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-edit-entry-form">
            <input type="hidden" id="edit-entry-id" value="">
            
            <div class="conversaai-form-row">
                <label for="edit-question"><?php _e('Question:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="edit-question" class="widefat" required>
            </div>
            
            <div class="conversaai-form-row">
                <label for="edit-answer"><?php _e('Answer:', 'conversaai-pro-wp'); ?></label>
                <textarea id="edit-answer" class="widefat" rows="6" required></textarea>
            </div>
            
            <div class="conversaai-form-row">
                <label for="edit-topic"><?php _e('Topic:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="edit-topic" class="widefat">
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Approve & Save', 'conversaai-pro-wp'); ?></button>
                <button type="button" id="edit-cancel" class="button"><?php _e('Cancel', 'conversaai-pro-wp'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Entry Modal -->
<div id="conversaai-reject-entry-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2><?php _e('Reject Knowledge Entry', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-reject-entry-form">
            <input type="hidden" id="reject-entry-id" value="">
            
            <div class="conversaai-form-row">
                <label for="reject-reason"><?php _e('Reason for rejection (optional):', 'conversaai-pro-wp'); ?></label>
                <textarea id="reject-reason" class="widefat" rows="4"></textarea>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Reject Entry', 'conversaai-pro-wp'); ?></button>
                <button type="button" id="reject-cancel" class="button"><?php _e('Cancel', 'conversaai-pro-wp'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Extract Knowledge Modal -->
<div id="conversaai-extract-knowledge-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2><?php _e('Extract Knowledge from Conversations', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-extract-knowledge-form">
            <div class="conversaai-form-row">
                <p><?php _e('Select which conversations to analyze for knowledge extraction:', 'conversaai-pro-wp'); ?></p>
                
                <select id="extract-source">
                    <option value="recent"><?php _e('Recent Conversations (Last 7 Days)', 'conversaai-pro-wp'); ?></option>
                    <option value="high_score"><?php _e('High Success Score Conversations (> 80%)', 'conversaai-pro-wp'); ?></option>
                    <option value="specific"><?php _e('Specific Conversation ID', 'conversaai-pro-wp'); ?></option>
                </select>
            </div>
            
            <div class="conversaai-form-row" id="specific-conversation-row" style="display: none;">
                <label for="specific-conversation-id"><?php _e('Conversation ID:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="specific-conversation-id" class="regular-text">
            </div>

            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="force-reprocess">
                    <?php _e('Force reprocess already processed conversations', 'conversaai-pro-wp'); ?>
                </label>
                <p class="description"><?php _e('Enable this to re-extract knowledge from conversations that have already been processed.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Start Extraction', 'conversaai-pro-wp'); ?></button>
                <span id="extraction-status" style="display:none; margin-left: 10px;">
                    <span class="spinner is-active"></span> <?php _e('Extracting...', 'conversaai-pro-wp'); ?>
                </span>
            </div>
        </form>
        
        <div id="extraction-results" style="display: none; margin-top: 20px;">
            <h3><?php _e('Extraction Results', 'conversaai-pro-wp'); ?></h3>
            <div id="extraction-results-content"></div>
            <button type="button" id="close-extraction-results" class="button button-primary" style="margin-top: 15px;">
                <?php _e('Close', 'conversaai-pro-wp'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Settings panel toggle
    $('#conversaai-settings-toggle').on('click', function() {
        $('#conversaai-learning-settings-panel').slideToggle();
    });
    
    // Range sliders
    $('#min-confidence').on('input', function() {
        $('#min-confidence-value').text(Math.round($(this).val() * 100) + '%');
    });
    
    $('#min-auto-approve-confidence').on('input', function() {
        $('#min-auto-approve-value').text(Math.round($(this).val() * 100) + '%');
    });
    
    // Auto-approve settings visibility
    $('#auto-approve').on('change', function() {
        if ($(this).is(':checked')) {
            $('.auto-approve-threshold').slideDown();
        } else {
            $('.auto-approve-threshold').slideUp();
        }
    });
    
    // Show a notification message
    function showNotice(message, type = 'success', duration = 5000) {
        const $message = $('#learning-settings-message');
        
        // Set message type class
        $message.removeClass('notice-success notice-error notice-warning notice-info')
                .addClass('notice-' + type);
        
        // Set message text
        $message.find('p').text(message);
        
        // Show message
        $message.fadeIn();
        
        // Auto-dismiss after duration if provided
        if (duration > 0) {
            setTimeout(function() {
                $message.fadeOut();
            }, duration);
        }
    }
    
    // Save learning settings
    $('#conversaai-learning-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $savingIndicator = $('#settings-saving-indicator');
        const $saveButton = $form.find('button[type="submit"]');
        
        // Disable button and show saving indicator
        $saveButton.prop('disabled', true);
        $savingIndicator.show();
        
        // Collect settings
        const settings = {
            auto_extraction: $('#auto-extraction').is(':checked'),
            min_confidence: parseFloat($('#min-confidence').val()),
            auto_approve: $('#auto-approve').is(':checked'),
            min_auto_approve_confidence: parseFloat($('#min-auto-approve-confidence').val())
        };
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_update_learning_settings',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                settings: settings
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice(response.data.message, 'success');
                    
                    // Update form elements to reflect saved state if needed
                    if (response.data.settings) {
                        // Update form with the returned settings
                        updateFormSettings(response.data.settings);
                    }
                } else {
                    showNotice(response.data.message || '<?php _e('Error saving settings.', 'conversaai-pro-wp'); ?>', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving settings:', error);
                showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
            },
            complete: function() {
                // Re-enable button and hide saving indicator
                $saveButton.prop('disabled', false);
                $savingIndicator.hide();
            }
        });
    });
    
    // Update form settings helper
    function updateFormSettings(settings) {
        // Update checkbox states
        $('#auto-extraction').prop('checked', !!settings.auto_extraction);
        $('#auto-approve').prop('checked', !!settings.auto_approve);
        
        // Update range inputs
        $('#min-confidence').val(settings.min_confidence || 0.7)
                           .trigger('input');
        
        $('#min-auto-approve-confidence').val(settings.min_auto_approve_confidence || 0.9)
                                        .trigger('input');
        
        // Show/hide auto-approve threshold based on auto-approve setting
        if (settings.auto_approve) {
            $('.auto-approve-threshold').slideDown();
        } else {
            $('.auto-approve-threshold').slideUp();
        }
    }
    
    // Selection handling
    let selectedEntries = [];
    
    // Select all entries
    $('#conversaai-select-all-entries').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.conversaai-select-entry').prop('checked', isChecked);
        
        if (isChecked) {
            selectedEntries = $('.conversaai-select-entry').map(function() {
                return $(this).val();
            }).get();
        } else {
            selectedEntries = [];
        }
        
        $('#conversaai-selected-count').text(selectedEntries.length);
    });
    
    // Select individual entry
    $(document).on('change', '.conversaai-select-entry', function() {
        const entryId = $(this).val();
        
        if ($(this).prop('checked')) {
            if (!selectedEntries.includes(entryId)) {
                selectedEntries.push(entryId);
            }
        } else {
            const index = selectedEntries.indexOf(entryId);
            if (index !== -1) {
                selectedEntries.splice(index, 1);
            }
        }
        
        $('#conversaai-selected-count').text(selectedEntries.length);
        
        // Update "select all" checkbox
        const allChecked = $('.conversaai-select-entry:checked').length === $('.conversaai-select-entry').length;
        $('#conversaai-select-all-entries').prop('checked', allChecked && $('.conversaai-select-entry').length > 0);
    });
    
    // Apply bulk action
    $('#conversaai-apply-bulk-action').on('click', function() {
        const action = $('#conversaai-knowledge-bulk-action').val();
        
        if (!action) {
            alert('<?php _e('Please select an action.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        if (selectedEntries.length === 0) {
            alert('<?php _e('Please select at least one entry.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        if (confirm('<?php _e('Are you sure you want to process the selected entries?', 'conversaai-pro-wp'); ?>')) {
            // Show processing overlay
            $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
            
            // Disable the button to prevent multiple submissions
            $(this).prop('disabled', true);
            
            // Make a copy of the selected entries for later removal
            const entriesToProcess = [...selectedEntries];
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_batch_process_knowledge_entries',
                    nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                    batch_action: action,
                    entry_ids: entriesToProcess
                },
                success: function(response) {
                    $('#conversaai-processing-overlay').remove();
                    
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        
                        // Remove processed entries from the list
                        entriesToProcess.forEach(function(id) {
                            $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                                
                                // Check if no entries left
                                if ($('#conversaai-pending-entries-list tr').length === 0) {
                                    $('#conversaai-pending-entries-list').html(`
                                        <tr>
                                            <td colspan="6" class="conversaai-no-entries">
                                                <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                                                <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                                            </td>
                                        </tr>
                                    `);
                                }
                            });
                        });
                        
                        // Clear selectedEntries and update display
                        selectedEntries = [];
                        $('#conversaai-selected-count').text('0');
                        $('#conversaai-select-all-entries').prop('checked', false);
                        
                        // Refresh stats
                        refreshStats();
                    } else {
                        showNotice(response.data.message || '<?php _e('Error processing entries.', 'conversaai-pro-wp'); ?>', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $('#conversaai-processing-overlay').remove();
                    console.error('Bulk action error:', error);
                    showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
                },
                complete: function() {
                    // Re-enable the button
                    $('#conversaai-apply-bulk-action').prop('disabled', false);
                }
            });
        }
    });
    
    // Approve entry directly
    $(document).on('click', '.conversaai-approve-entry', function() {
        const entryId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        if (confirm('<?php _e('Are you sure you want to approve this entry?', 'conversaai-pro-wp'); ?>')) {
            // Show processing overlay
            $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
            
            // Disable the button
            $(this).prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_approve_knowledge_entry',
                    nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                    entry_id: entryId
                },
                success: function(response) {
                    $('#conversaai-processing-overlay').remove();
                    
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        
                        // Remove the row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if no entries left
                            if ($('#conversaai-pending-entries-list tr').length === 0) {
                                $('#conversaai-pending-entries-list').html(`
                                    <tr>
                                        <td colspan="6" class="conversaai-no-entries">
                                            <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                                            <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        // Update selectedEntries if needed
                        const index = selectedEntries.indexOf(entryId);
                        if (index !== -1) {
                            selectedEntries.splice(index, 1);
                            $('#conversaai-selected-count').text(selectedEntries.length);
                        }
                        
                        // Refresh stats
                        refreshStats();
                    } else {
                        showNotice(response.data.message || '<?php _e('Error approving entry.', 'conversaai-pro-wp'); ?>', 'error');
                        // Re-enable the button on error
                        $row.find('.conversaai-approve-entry').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $('#conversaai-processing-overlay').remove();
                    console.error('Approve entry error:', error);
                    showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
                    // Re-enable the button on error
                    $row.find('.conversaai-approve-entry').prop('disabled', false);
                }
            });
        }
    });
    
    // Edit entry
    $(document).on('click', '.conversaai-edit-entry', function() {
        const entryId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        // Fill form
        $('#edit-entry-id').val(entryId);
        $('#edit-question').val($row.find('.column-question').text().trim());
        $('#edit-answer').val($row.find('.column-answer').html().trim());
        $('#edit-topic').val(''); // Default to empty topic
        
        // Show modal
        $('#conversaai-edit-entry-modal').show();
    });
    
    // Reject entry
    $(document).on('click', '.conversaai-reject-entry', function() {
        const entryId = $(this).data('id');
        
        // Fill form
        $('#reject-entry-id').val(entryId);
        $('#reject-reason').val('');
        
        // Show modal
        $('#conversaai-reject-entry-modal').show();
    });
    
    // Close modals
    $('.conversaai-modal-close, #edit-cancel, #reject-cancel').on('click', function() {
        $(this).closest('.conversaai-modal').hide();
    });
    
    // When clicking outside modal, close it
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('conversaai-modal')) {
            $('.conversaai-modal').hide();
        }
    });
    
    // Edit entry form submission
    $('#conversaai-edit-entry-form').on('submit', function(e) {
        e.preventDefault();
        
        const entryId = $('#edit-entry-id').val();
        const question = $('#edit-question').val();
        const answer = $('#edit-answer').val();
        const topic = $('#edit-topic').val();
        
        if (!entryId || !question || !answer) {
            alert('<?php _e('All fields are required.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show processing overlay
        $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
        
        // Disable submit button
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_approve_knowledge_entry',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                entry_id: entryId,
                question: question,
                answer: answer,
                topic: topic
            },
            success: function(response) {
                $('#conversaai-processing-overlay').remove();
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Hide modal
                    $('#conversaai-edit-entry-modal').hide();
                    
                    // Remove the edited row
                    $('tr[data-id="' + entryId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if no entries left
                        if ($('#conversaai-pending-entries-list tr').length === 0) {
                            $('#conversaai-pending-entries-list').html(`
                                <tr>
                                    <td colspan="6" class="conversaai-no-entries">
                                        <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                                        <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                    
                    // Update selectedEntries if needed
                    const index = selectedEntries.indexOf(entryId);
                    if (index !== -1) {
                        selectedEntries.splice(index, 1);
                        $('#conversaai-selected-count').text(selectedEntries.length);
                    }
                    
                    // Refresh stats
                    refreshStats();
                } else {
                    showNotice(response.data.message || '<?php _e('Error approving entry.', 'conversaai-pro-wp'); ?>', 'error');
                }
            },
            error: function(xhr, status, error) {
                $('#conversaai-processing-overlay').remove();
                console.error('Edit and approve error:', error);
                showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Reject entry form submission
    $('#conversaai-reject-entry-form').on('submit', function(e) {
        e.preventDefault();
        
        const entryId = $('#reject-entry-id').val();
        const reason = $('#reject-reason').val();
        
        if (!entryId) {
            alert('<?php _e('Entry ID is missing.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show processing overlay
        $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
        
        // Disable submit button
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_reject_knowledge_entry',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
                entry_id: entryId,
                reason: reason
            },
            success: function(response) {
                $('#conversaai-processing-overlay').remove();
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Hide modal
                    $('#conversaai-reject-entry-modal').hide();
                    
                    // Remove the rejected row
                    $('tr[data-id="' + entryId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if no entries left
                        if ($('#conversaai-pending-entries-list tr').length === 0) {
                            $('#conversaai-pending-entries-list').html(`
                                <tr>
                                    <td colspan="6" class="conversaai-no-entries">
                                        <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                                        <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                    
                    // Update selectedEntries if needed
                    const index = selectedEntries.indexOf(entryId);
                    if (index !== -1) {
                        selectedEntries.splice(index, 1);
                        $('#conversaai-selected-count').text(selectedEntries.length);
                    }
                    
                    // Refresh stats
                    refreshStats();
                } else {
                    showNotice(response.data.message || '<?php _e('Error rejecting entry.', 'conversaai-pro-wp'); ?>', 'error');
                }
            },
            error: function(xhr, status, error) {
                $('#conversaai-processing-overlay').remove();
                console.error('Reject entry error:', error);
                showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Refresh entries
    $('#conversaai-refresh-entries').on('click', function() {
        $(this).prop('disabled', true);
        const originalText = $(this).html();
        $(this).html('<span class="spinner is-active" style="float:left;margin:0 5px 0 0;"></span> <?php _e('Refreshing...', 'conversaai-pro-wp'); ?>');
        
        refreshPendingEntries().finally(() => {
            $(this).prop('disabled', false);
            $(this).html(originalText);
        });
    });
    
    // Extract knowledge button
    $('#conversaai-extract-knowledge').on('click', function() {
        $('#conversaai-extract-knowledge-modal').show();
        $('#extraction-results').hide();
    });
    
    // Toggle specific conversation field visibility
    $('#extract-source').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#specific-conversation-row').show();
        } else {
            $('#specific-conversation-row').hide();
        }
    });
    
    // Extract knowledge form submission
    $('#conversaai-extract-knowledge-form').on('submit', function(e) {
        e.preventDefault();
        
        const source = $('#extract-source').val();
        const specificId = $('#specific-conversation-id').val();
        const forceReprocess = $('#force-reprocess').is(':checked');
        
        if (source === 'specific' && !specificId) {
            alert('<?php _e('Please enter a conversation ID.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show extraction status
        $('#extraction-status').show();
        
        // Disable submit button
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        // Prepare data for AJAX request
        const data = {
            action: 'conversaai_extract_knowledge',
            nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>',
            source: source,
            force_reprocess: forceReprocess
        };
        
        if (source === 'specific') {
            data.session_id = specificId;
        }
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                $('#extraction-status').hide();
                
                if (response.success) {
                    // Show results
                    $('#extraction-results').show();
                    $('#extraction-results-content').html(formatExtractionResults(response.data));
                    
                    // Refresh entries and stats
                    refreshPendingEntries();
                } else {
                    showNotice(response.data.message || '<?php _e('Error extracting knowledge.', 'conversaai-pro-wp'); ?>', 'error');
                }
            },
            error: function(xhr, status, error) {
                $('#extraction-status').hide();
                console.error('Extract knowledge error:', error);
                showNotice('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>', 'error');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Close extraction results
    $('#close-extraction-results').on('click', function() {
        $('#conversaai-extract-knowledge-modal').hide();
        refreshPendingEntries();
    });
    
    // Format extraction results
    function formatExtractionResults(data) {
        let html = '<div class="extraction-summary">';
        
        if (data.conversations_processed) {
            html += '<p><strong><?php _e('Conversations Processed:', 'conversaai-pro-wp'); ?></strong> ' + data.conversations_processed + '</p>';
        }
        
        html += '<p><strong><?php _e('Total Entries Extracted:', 'conversaai-pro-wp'); ?></strong> ' + data.total_extracted + '</p>';
        
        if (data.auto_approved) {
            html += '<p><strong><?php _e('Auto-Approved:', 'conversaai-pro-wp'); ?></strong> ' + data.auto_approved + '</p>';
        }
        
        if (data.skipped) {
            html += '<p><strong><?php _e('Skipped (Already Exists or Rejected):', 'conversaai-pro-wp'); ?></strong> ' + data.skipped + '</p>';
        }
        
        html += '</div>';
        
        if (data.details && data.details.length > 0) {
            html += '<h4><?php _e('Processing Details:', 'conversaai-pro-wp'); ?></h4>';
            html += '<ul class="extraction-details">';
            
            data.details.forEach(function(detail) {
                html += '<li>';
                html += '<strong><?php _e('Conversation:', 'conversaai-pro-wp'); ?></strong> ' + detail.session_id;
                
                if (detail.success_score !== undefined) {
                    html += ' <strong><?php _e('Success Score:', 'conversaai-pro-wp'); ?></strong> ' + Math.round(detail.success_score * 100) + '%';
                }
                
                html += ' <strong><?php _e('Extracted:', 'conversaai-pro-wp'); ?></strong> ' + detail.extracted;
                
                if (detail.auto_approved) {
                    html += ' <strong><?php _e('Auto-Approved:', 'conversaai-pro-wp'); ?></strong> ' + detail.auto_approved;
                }
                
                if (detail.skipped && detail.skipped > 0) {
                    html += ' <strong><?php _e('Skipped:', 'conversaai-pro-wp'); ?></strong> ' + detail.skipped;
                }
                
                html += '</li>';
            });
            
            html += '</ul>';
        }
        
        return html;
    }
    
    // Helper function to refresh pending entries
    function refreshPendingEntries() {
        // Show loading state
        $('#conversaai-pending-entries-list').html('<tr><td colspan="6"><div class="spinner is-active" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 10px;"></div></td></tr>');
        
        // Create a promise to track completion
        return new Promise((resolve, reject) => {
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_get_pending_knowledge_entries',
                    nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Update entries list
                        updatePendingEntriesList(response.data.entries);
                        // Update stats
                        updateLearningStats(response.data.stats);
                        resolve();
                    } else {
                        $('#conversaai-pending-entries-list').html('<tr><td colspan="6"><?php _e('Error loading entries.', 'conversaai-pro-wp'); ?></td></tr>');
                        reject(new Error(response.data.message || 'Error loading entries'));
                    }
                },
                error: function(xhr, status, error) {
                    $('#conversaai-pending-entries-list').html('<tr><td colspan="6"><?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?></td></tr>');
                    console.error('AJAX error while refreshing entries:', error);
                    reject(error);
                }
            });
        });
    }
    
    // Helper function to refresh only stats
    function refreshStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_pending_knowledge_entries',
                nonce: '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Update only stats
                    updateLearningStats(response.data.stats);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to refresh stats:', error);
            }
        });
    }
    
    // Helper function to update pending entries list
    function updatePendingEntriesList(entries) {
        const $list = $('#conversaai-pending-entries-list');
        
        if (!entries || entries.length === 0) {
            $list.html(`
                <tr>
                    <td colspan="6" class="conversaai-no-entries">
                        <p><?php _e('No pending knowledge entries found.', 'conversaai-pro-wp'); ?></p>
                        <p><?php _e('Start by clicking "Extract Knowledge" to analyze conversations and extract potential knowledge entries.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        
        entries.forEach(function(entry) {
            const metadata = typeof entry.metadata === 'string' ? JSON.parse(entry.metadata) : entry.metadata;
            const sessionId = metadata && metadata.session_id ? metadata.session_id : (entry.session_id || 'unknown');
            
            html += `
                <tr data-id="${entry.id}">
                    <td class="column-cb check-column">
                        <input type="checkbox" class="conversaai-select-entry" value="${entry.id}">
                    </td>
                    <td class="column-question">${escapeHtml(entry.question)}</td>
                    <td class="column-answer">${entry.answer}</td>
                    <td class="column-source">${escapeHtml(sessionId.substring(0, 10) + '...')}</td>
                    <td class="column-confidence">
                        <div class="conversaai-confidence-meter">
                            <div class="conversaai-confidence-bar">
                                <div class="conversaai-confidence-fill" style="width: ${entry.confidence * 100}%;"></div>
                            </div>
                            <span class="conversaai-confidence-value">${Math.round(entry.confidence * 100)}%</span>
                        </div>
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button conversaai-approve-entry" data-id="${entry.id}">
                            <span class="dashicons dashicons-yes"></span>
                        </button>
                        <button type="button" class="button conversaai-edit-entry" data-id="${entry.id}">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button conversaai-reject-entry" data-id="${entry.id}">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $list.html(html);
        
        // Reset selection state
        selectedEntries = [];
        $('#conversaai-selected-count').text('0');
        $('#conversaai-select-all-entries').prop('checked', false);
    }
    
    // Helper function to update learning stats
    function updateLearningStats(stats) {
        if (!stats) return;
        
        $('.conversaai-stat-box:nth-child(1) .conversaai-stat-value').text(
            typeof stats.pending_count === 'number' ? 
            stats.pending_count.toLocaleString() : 
            '<?php _e('N/A', 'conversaai-pro-wp'); ?>'
        );
        
        $('.conversaai-stat-box:nth-child(2) .conversaai-stat-value').text(
            typeof stats.approved_count === 'number' ? 
            stats.approved_count.toLocaleString() : 
            '<?php _e('N/A', 'conversaai-pro-wp'); ?>'
        );
        
        $('.conversaai-stat-box:nth-child(3) .conversaai-stat-value').text(
            typeof stats.auto_approved_count === 'number' ? 
            stats.auto_approved_count.toLocaleString() : 
            '<?php _e('N/A', 'conversaai-pro-wp'); ?>'
        );
        
        $('.conversaai-stat-box:nth-child(4) .conversaai-stat-value').text(
            typeof stats.rejected_count === 'number' ? 
            stats.rejected_count.toLocaleString() : 
            '<?php _e('N/A', 'conversaai-pro-wp'); ?>'
        );
        
        $('.conversaai-stat-box:nth-child(5) .conversaai-stat-value').text(
            typeof stats.avg_confidence === 'number' ? 
            (stats.avg_confidence * 100).toFixed(1) + '%' : 
            '<?php _e('N/A', 'conversaai-pro-wp'); ?>'
        );
        
        // If settings are included, update form
        if (stats.settings) {
            updateFormSettings(stats.settings);
        }
    }
    
    // Helper to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<style>
/* Learning tab styles */
.conversaai-learning-tab {
    margin-top: 20px;
}

.conversaai-learning-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.conversaai-learning-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.conversaai-stat-box {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    min-width: 150px;
    text-align: center;
}

.conversaai-stat-box h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #555;
}

.conversaai-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #4c66ef;
}

.conversaai-learning-actions {
    display: flex;
    gap: 10px;
}

.conversaai-settings-panel {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.conversaai-form-row {
    margin-bottom: 15px;
}

.conversaai-range-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-range-control input {
    flex: 1;
    max-width: 300px;
}

.conversaai-form-actions {
    margin-top: 20px;
}

.conversaai-pending-entries {
    margin-top: 20px;
}

.conversaai-bulk-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    background: white;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.conversaai-confidence-meter {
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-confidence-bar {
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    width: 100px;
    overflow: hidden;
}

.conversaai-confidence-fill {
    height: 100%;
    background: #4caf50;
    border-radius: 5px;
}

.conversaai-confidence-value {
    font-weight: bold;
    min-width: 40px;
}

.conversaai-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.conversaai-modal-content {
    background: white;
    border-radius: 4px;
    padding: 20px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.conversaai-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.conversaai-no-entries {
    text-align: center;
    padding: 20px;
}

#conversaai-processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100001;
    display: flex;
    justify-content: center;
    align-items: center;
}

.conversaai-processing-message {
    background: white;
    padding: 20px 30px;
    border-radius: 4px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

.extraction-summary {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.extraction-details {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.extraction-details li {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.extraction-details li:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Notice styling */
.notice {
    margin: 15px 0;
}

/* Responsive adjustments */
@media (max-width: 782px) {
    .conversaai-learning-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .conversaai-learning-stats {
        flex-direction: column;
        width: 100%;
    }
    
    .conversaai-stat-box {
        width: 100%;
    }
    
    .conversaai-learning-actions {
        width: 100%;
    }
}

.dashicons {
 line-height: 1.4;
}

.widefat .check-column { 
  padding: 10px;
}

</style>