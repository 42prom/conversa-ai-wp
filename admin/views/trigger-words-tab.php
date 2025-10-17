<?php
/**
 * Trigger Words tab content.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-trigger-words conversaai-trigger-words-container">
    <h1 class="conversaai-page-header"><?php _e('Trigger Words Management', 'conversaai-pro-wp'); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Trigger Words Configuration', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Manage words or phrases that trigger specific responses. Create custom replies for frequently asked questions and common conversational patterns.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-controls-repeat"></span>
        </div>
    </div>
    
    <div class="conversaai-trigger-words-header">
        <div class="conversaai-trigger-words-actions">
            <button id="add-trigger-word" class="button button-primary">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add Trigger Word', 'conversaai-pro-wp'); ?>
            </button>
            
            <select id="category-filter" class="conversaai-category-filter">
                <option value=""><?php _e('All Categories', 'conversaai-pro-wp'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                <?php endforeach; ?>
            </select>
            
            <button id="refresh-trigger-words" class="button">
                <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'conversaai-pro-wp'); ?>
            </button>
            
            <div class="conversaai-import-export-actions">
                <button id="import-trigger-words" class="button">
                    <span class="dashicons dashicons-upload"></span> <?php _e('Import', 'conversaai-pro-wp'); ?>
                </button>
                <button id="export-trigger-words" class="button">
                    <span class="dashicons dashicons-download"></span> <?php _e('Export', 'conversaai-pro-wp'); ?>
                </button>
            </div>
        </div>
        
        <div class="conversaai-trigger-words-stats">
            <span class="conversaai-trigger-words-count"><?php echo count($trigger_words); ?></span> <?php _e('trigger words configured', 'conversaai-pro-wp'); ?>
        </div>
    </div>
    
    <div class="conversaai-trigger-words-list-container">
        <table class="wp-list-table widefat fixed striped conversaai-trigger-words-table">
            <thead>
                <tr>
                    <th class="column-trigger"><?php _e('Trigger Word/Phrase', 'conversaai-pro-wp'); ?></th>
                    <th class="column-category"><?php _e('Category', 'conversaai-pro-wp'); ?></th>
                    <th class="column-match-type"><?php _e('Match Type', 'conversaai-pro-wp'); ?></th>
                    <th class="column-responses"><?php _e('Responses', 'conversaai-pro-wp'); ?></th>
                    <th class="column-follow-ups"><?php _e('Follow-ups', 'conversaai-pro-wp'); ?></th>
                    <th class="column-status"><?php _e('Status', 'conversaai-pro-wp'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'conversaai-pro-wp'); ?></th>
                </tr>
            </thead>
            <tbody id="trigger-words-list">
                <?php if (empty($trigger_words)): ?>
                    <tr class="no-items">
                        <td colspan="7"><?php _e('No trigger words found. Click "Add Trigger Word" to create your first one.', 'conversaai-pro-wp'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trigger_words as $id => $trigger): ?>
                        <tr>
                            <td class="column-trigger">
                                <strong><?php echo esc_html($trigger['word']); ?></strong>
                                <?php if (!empty($trigger['description'])): ?>
                                    <div class="row-description"><?php echo esc_html($trigger['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="column-category"><?php echo esc_html($trigger['category'] ?? ''); ?></td>
                            <td class="column-match-type">
                                <?php 
                                $match_type = isset($trigger['match_type']) ? $trigger['match_type'] : 'exact';
                                $match_labels = [
                                    'exact' => __('Exact', 'conversaai-pro-wp'),
                                    'contains' => __('Contains', 'conversaai-pro-wp'),
                                    'starts_with' => __('Starts with', 'conversaai-pro-wp'),
                                    'ends_with' => __('Ends with', 'conversaai-pro-wp'),
                                    'regex' => __('Regex', 'conversaai-pro-wp')
                                ];
                                echo esc_html($match_labels[$match_type] ?? $match_type); 
                                ?>
                            </td>
                            <td class="column-responses">
                                <?php 
                                $responses = isset($trigger['responses']) ? $trigger['responses'] : array();
                                echo count($responses) . ' ' . _n('response', 'responses', count($responses), 'conversaai-pro-wp');
                                ?>
                            </td>
                            <td class="column-follow-ups">
                                <?php 
                                $follow_ups = isset($trigger['follow_ups']) ? $trigger['follow_ups'] : array();
                                echo count($follow_ups) . ' ' . _n('follow-up', 'follow-ups', count($follow_ups), 'conversaai-pro-wp');
                                ?>
                            </td>
                            <td class="column-status">
                                <?php if (isset($trigger['active']) && $trigger['active']): ?>
                                    <span class="status-active"><?php _e('Active', 'conversaai-pro-wp'); ?></span>
                                <?php else: ?>
                                    <span class="status-inactive"><?php _e('Inactive', 'conversaai-pro-wp'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button class="button edit-trigger-word" data-id="<?php echo esc_attr($id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button class="button delete-trigger-word" data-id="<?php echo esc_attr($id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="conversaai-modal" style="display: none;">
        <div class="conversaai-modal-content">
            <span class="conversaai-modal-close">&times;</span>
            <h2><?php _e('Import Trigger Words', 'conversaai-pro-wp'); ?></h2>
            
            <form id="import-form">
                <div class="conversaai-form-row">
                    <label for="import-file"><?php _e('Select File:', 'conversaai-pro-wp'); ?></label>
                    <input type="file" id="import-file" accept=".csv,.json">
                    <p class="description"><?php _e('Select a CSV or JSON file containing trigger words data.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="import-format"><?php _e('File Format:', 'conversaai-pro-wp'); ?></label>
                    <select id="import-format">
                        <option value="csv"><?php _e('CSV', 'conversaai-pro-wp'); ?></option>
                        <option value="json"><?php _e('JSON', 'conversaai-pro-wp'); ?></option>
                    </select>
                </div>
                
                <div class="conversaai-form-row csv-options">
                    <label for="csv-has-header">
                        <input type="checkbox" id="csv-has-header" checked>
                        <?php _e('CSV file has header row', 'conversaai-pro-wp'); ?>
                    </label>
                    <p class="description"><?php _e('First row of CSV contains column names.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="import-behavior"><?php _e('Import Behavior:', 'conversaai-pro-wp'); ?></label>
                    <select id="import-behavior">
                        <option value="merge"><?php _e('Merge (update existing, add new)', 'conversaai-pro-wp'); ?></option>
                        <option value="replace"><?php _e('Replace (delete all existing, add new)', 'conversaai-pro-wp'); ?></option>
                        <option value="add"><?php _e('Add Only (skip if trigger word exists)', 'conversaai-pro-wp'); ?></option>
                    </select>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="default-status">
                        <input type="checkbox" id="default-status" checked>
                        <?php _e('Set imported words as active by default', 'conversaai-pro-wp'); ?>
                    </label>
                </div>
                
                <div class="conversaai-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Import', 'conversaai-pro-wp'); ?></button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </div>
            </form>
            
            <div class="conversaai-import-template">
                <h3><?php _e('CSV Template Format', 'conversaai-pro-wp'); ?></h3>
                <p><?php _e('Your CSV should have these columns:', 'conversaai-pro-wp'); ?></p>
                <pre>word,category,match_type,description,priority,active,responses,follow_ups</pre>
                <p><?php _e('Example:', 'conversaai-pro-wp'); ?></p>
                <pre>hello,Greetings,exact,Greeting trigger,10,1,"Hi there!|Hello, how can I help?","Can I help you with something?|What brings you here today?"</pre>
                <p><?php _e('For responses and follow-ups, separate multiple items with a pipe (|) character.', 'conversaai-pro-wp'); ?></p>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="export-modal" class="conversaai-modal" style="display: none;">
        <div class="conversaai-modal-content">
            <span class="conversaai-modal-close">&times;</span>
            <h2><?php _e('Export Trigger Words', 'conversaai-pro-wp'); ?></h2>
            
            <form id="export-form">
                <div class="conversaai-form-row">
                    <label for="export-format"><?php _e('Export Format:', 'conversaai-pro-wp'); ?></label>
                    <select id="export-format">
                        <option value="csv"><?php _e('CSV', 'conversaai-pro-wp'); ?></option>
                        <option value="json"><?php _e('JSON', 'conversaai-pro-wp'); ?></option>
                    </select>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="export-category"><?php _e('Category Filter:', 'conversaai-pro-wp'); ?></label>
                    <select id="export-category">
                        <option value=""><?php _e('All Categories', 'conversaai-pro-wp'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="export-active-only">
                        <input type="checkbox" id="export-active-only">
                        <?php _e('Export active trigger words only', 'conversaai-pro-wp'); ?>
                    </label>
                </div>
                
                <div class="conversaai-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Export', 'conversaai-pro-wp'); ?></button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Trigger Word Edit Modal -->
<div id="trigger-word-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2 id="trigger-word-modal-title"><?php _e('Add Trigger Word', 'conversaai-pro-wp'); ?></h2>
        
        <form id="trigger-word-form">
            <input type="hidden" id="trigger-word-id" value="">
            
            <div class="conversaai-form-row">
                <label for="trigger-word-text"><?php _e('Trigger Word or Phrase:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="trigger-word-text" class="widefat" required>
                <p class="description"><?php _e('The word or phrase that will trigger a specific response.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <div class="conversaai-form-row-split">
                    <div>
                        <label for="trigger-word-category"><?php _e('Category:', 'conversaai-pro-wp'); ?></label>
                        <input type="text" id="trigger-word-category" class="regular-text" list="trigger-word-categories">
                        <datalist id="trigger-word-categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label for="trigger-word-match-type"><?php _e('Match Type:', 'conversaai-pro-wp'); ?></label>
                        <select id="trigger-word-match-type">
                            <option value="exact"><?php _e('Exact Match', 'conversaai-pro-wp'); ?></option>
                            <option value="contains"><?php _e('Contains', 'conversaai-pro-wp'); ?></option>
                            <option value="starts_with"><?php _e('Starts With', 'conversaai-pro-wp'); ?></option>
                            <option value="ends_with"><?php _e('Ends With', 'conversaai-pro-wp'); ?></option>
                            <option value="regex"><?php _e('Regular Expression', 'conversaai-pro-wp'); ?></option>
                        </select>
                    </div>
                </div>
                <p class="description"><?php _e('Categorize and define how this trigger should match user input.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="trigger-word-description"><?php _e('Description:', 'conversaai-pro-wp'); ?></label>
                <textarea id="trigger-word-description" class="widefat" rows="2"></textarea>
                <p class="description"><?php _e('Optional description for your reference.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <div class="conversaai-form-row-split">
                    <div>
                        <label for="trigger-word-priority"><?php _e('Priority:', 'conversaai-pro-wp'); ?></label>
                        <input type="number" id="trigger-word-priority" min="1" max="100" value="10">
                    </div>
                    <div>
                        <label for="trigger-word-active"><?php _e('Status:', 'conversaai-pro-wp'); ?></label>
                        <select id="trigger-word-active">
                            <option value="1"><?php _e('Active', 'conversaai-pro-wp'); ?></option>
                            <option value="0"><?php _e('Inactive', 'conversaai-pro-wp'); ?></option>
                        </select>
                    </div>
                </div>
                <p class="description"><?php _e('Higher priority triggers will be checked first. Inactive triggers won\'t be used.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-tabs">
                <div class="conversaai-tab active" data-tab="responses"><?php _e('Responses', 'conversaai-pro-wp'); ?></div>
                <div class="conversaai-tab" data-tab="follow-ups"><?php _e('Follow-up Questions', 'conversaai-pro-wp'); ?></div>
            </div>
            
            <div class="conversaai-tab-content active" data-tab-content="responses">
                <div class="conversaai-form-row">
                    <div class="conversaai-list-header">
                        <label><?php _e('Response Options:', 'conversaai-pro-wp'); ?></label>
                        <button type="button" class="button add-response">
                            <span class="dashicons dashicons-plus"></span> <?php _e('Add Response', 'conversaai-pro-wp'); ?>
                        </button>
                    </div>
                    <div id="responses-container" class="conversaai-dynamic-list">
                        <!-- Responses will be added here dynamically -->
                        <div class="conversaai-empty-state">
                            <?php _e('No responses added yet. Click "Add Response" to add your first response.', 'conversaai-pro-wp'); ?>
                        </div>
                    </div>
                    <p class="description"><?php _e('Add multiple response options. One will be randomly selected when the trigger is matched.', 'conversaai-pro-wp'); ?></p>
                </div>
            </div>
            
            <div class="conversaai-tab-content" data-tab-content="follow-ups">
                <div class="conversaai-form-row">
                    <div class="conversaai-list-header">
                        <label><?php _e('Follow-up Questions:', 'conversaai-pro-wp'); ?></label>
                        <button type="button" class="button add-follow-up">
                            <span class="dashicons dashicons-plus"></span> <?php _e('Add Follow-up', 'conversaai-pro-wp'); ?>
                        </button>
                    </div>
                    <div id="follow-ups-container" class="conversaai-dynamic-list">
                        <!-- Follow-ups will be added here dynamically -->
                        <div class="conversaai-empty-state">
                            <?php _e('No follow-up questions added yet. Click "Add Follow-up" to add your first question.', 'conversaai-pro-wp'); ?>
                        </div>
                    </div>
                    <p class="description"><?php _e('Add follow-up questions that the AI might ask after using this response.', 'conversaai-pro-wp'); ?></p>
                </div>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Trigger Word', 'conversaai-pro-wp'); ?></button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </div>
        </form>
    </div>
</div>

<!-- Dynamic Template for Response Item -->
<template id="response-template">
    <div class="conversaai-dynamic-item">
        <textarea class="widefat response-text" rows="2" placeholder="<?php esc_attr_e('Enter response text here...', 'conversaai-pro-wp'); ?>"></textarea>
        <button type="button" class="button remove-item">
            <span class="dashicons dashicons-no"></span>
        </button>
    </div>
</template>

<!-- Dynamic Template for Follow-up Item -->
<template id="follow-up-template">
    <div class="conversaai-dynamic-item">
        <textarea class="widefat follow-up-text" rows="2" placeholder="<?php esc_attr_e('Enter follow-up question here...', 'conversaai-pro-wp'); ?>"></textarea>
        <button type="button" class="button remove-item">
            <span class="dashicons dashicons-no"></span>
        </button>
    </div>
</template>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('conversaai_dialogue_manager_nonce'); ?>';
    let triggerWords = <?php echo json_encode($trigger_words); ?>;
    
    // ========== UI Interactions ==========
    
    // Tab switching
    $('#trigger-word-modal .conversaai-tab').on('click', function() {
        $('#trigger-word-modal .conversaai-tab').removeClass('active');
        $(this).addClass('active');
        
        const tab = $(this).data('tab');
        $('#trigger-word-modal .conversaai-tab-content').removeClass('active');
        $(`#trigger-word-modal .conversaai-tab-content[data-tab-content="${tab}"]`).addClass('active');
    });
    
    // Modal close button
    $('.conversaai-modal-close').on('click', function() {
        $(this).closest('.conversaai-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('conversaai-modal')) {
            $('.conversaai-modal').hide();
        }
    });
    
    // ========== Trigger Words Management ==========
    
    // Add new trigger word
    $('#add-trigger-word').on('click', function() {
        // Reset form
        $('#trigger-word-form')[0].reset();
        $('#trigger-word-id').val('');
        $('#trigger-word-modal-title').text('<?php _e('Add Trigger Word', 'conversaai-pro-wp'); ?>');
        
        // Clear dynamic items
        $('#responses-container').html('<div class="conversaai-empty-state"><?php _e('No responses added yet. Click "Add Response" to add your first response.', 'conversaai-pro-wp'); ?></div>');
        $('#follow-ups-container').html('<div class="conversaai-empty-state"><?php _e('No follow-up questions added yet. Click "Add Follow-up" to add your first question.', 'conversaai-pro-wp'); ?></div>');
        
        // Show the first tab
        $('.conversaai-tab').removeClass('active');
        $('.conversaai-tab[data-tab="responses"]').addClass('active');
        $('.conversaai-tab-content').removeClass('active');
        $('.conversaai-tab-content[data-tab-content="responses"]').addClass('active');
        
        // Show modal
        $('#trigger-word-modal').show();
    });
    
    // Edit trigger word
    $(document).on('click', '.edit-trigger-word', function() {
        const id = $(this).data('id');
        const trigger = triggerWords[id];
        
        if (!trigger) {
            alert('<?php _e('Trigger word not found.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Populate form
        $('#trigger-word-id').val(id);
        $('#trigger-word-text').val(trigger.word);
        $('#trigger-word-category').val(trigger.category || '');
        $('#trigger-word-description').val(trigger.description || '');
        $('#trigger-word-match-type').val(trigger.match_type || 'exact');
        $('#trigger-word-priority').val(trigger.priority || 10);
        $('#trigger-word-active').val(trigger.active ? '1' : '0');
        
        // Update modal title
        $('#trigger-word-modal-title').text('<?php _e('Edit Trigger Word', 'conversaai-pro-wp'); ?>');
        
        // Populate responses
        $('#responses-container').empty();
        if (trigger.responses && trigger.responses.length > 0) {
            trigger.responses.forEach(function(response) {
                addDynamicItem('response', response);
            });
        } else {
            $('#responses-container').html('<div class="conversaai-empty-state"><?php _e('No responses added yet. Click "Add Response" to add your first response.', 'conversaai-pro-wp'); ?></div>');
        }
        
        // Populate follow-ups
        $('#follow-ups-container').empty();
        if (trigger.follow_ups && trigger.follow_ups.length > 0) {
            trigger.follow_ups.forEach(function(followUp) {
                addDynamicItem('follow-up', followUp);
            });
        } else {
            $('#follow-ups-container').html('<div class="conversaai-empty-state"><?php _e('No follow-up questions added yet. Click "Add Follow-up" to add your first question.', 'conversaai-pro-wp'); ?></div>');
        }
        
        // Show the first tab
        $('.conversaai-tab').removeClass('active');
        $('.conversaai-tab[data-tab="responses"]').addClass('active');
        $('.conversaai-tab-content').removeClass('active');
        $('.conversaai-tab-content[data-tab-content="responses"]').addClass('active');
        
        // Show modal
        $('#trigger-word-modal').show();
    });
    
    // Delete trigger word
    $(document).off('click', '.delete-trigger-word').on('click', '.delete-trigger-word', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling
        
        const $button = $(this);
        const id = $button.data('id');
        
        console.log('Delete button clicked for ID:', id);
        
        if (!id) {
            console.error('No ID found on delete button');
            alert('<?php _e('Error: Could not identify the trigger word to delete.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        if (!confirm('<?php _e('Are you sure you want to delete this trigger word? This cannot be undone.', 'conversaai-pro-wp'); ?>')) {
            return;
        }
        
        // Disable the button and show loading state
        $button.prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin:0;"></span>');
        const $row = $button.closest('tr');
        $row.css('opacity', '0.5');
        
        // Log the data we're sending
        console.log('Sending delete request with data:', {
            action: 'conversaai_delete_trigger_word',
            nonce: nonce,
            id: id
        });
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'conversaai_delete_trigger_word',
                nonce: nonce,
                id: id
            },
            success: function(response) {
                console.log('Delete response received:', response);
                
                if (response.success) {
                    // Remove from local data
                    if (triggerWords[id]) {
                        delete triggerWords[id];
                        console.log('Removed ID from local data:', id);
                    }
                    
                    // Remove the row with animation
                    $row.fadeOut(400, function() {
                        $(this).remove();
                        // Update stats
                        $('.conversaai-trigger-words-count').text(Object.keys(triggerWords).length);
                        
                        // If no items left, show empty state
                        if (Object.keys(triggerWords).length === 0) {
                            const emptyMessage = '<?php _e('No trigger words found. Click "Add Trigger Word" to create your first one.', 'conversaai-pro-wp'); ?>';
                            $('#trigger-words-list').html(`<tr class="no-items"><td colspan="7">${emptyMessage}</td></tr>`);
                        }
                    });
                    
                    // Show success message
                    alert(response.data.message);
                } else {
                    console.error('Delete failed:', response.data);
                    $row.css('opacity', '1');
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
                    alert(response.data.message || '<?php _e('Error deleting trigger word.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error on delete:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    console.log('Parsed error response:', errorData);
                } catch (e) {
                    console.log('Raw error response:', xhr.responseText);
                }
                
                $row.css('opacity', '1');
                $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
                alert('<?php _e('Connection error when trying to delete. Please check the console for details and try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    });
    
    // Save trigger word form
    $('#trigger-word-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $(this).find('button[type="submit"]');
        const $spinner = $(this).find('.spinner');
        
        // Gather form data
        const id = $('#trigger-word-id').val() || 'trigger_' + Date.now();
        const word = $('#trigger-word-text').val();
        const category = $('#trigger-word-category').val();
        const description = $('#trigger-word-description').val();
        const matchType = $('#trigger-word-match-type').val();
        const priority = $('#trigger-word-priority').val();
        const active = $('#trigger-word-active').val() === '1'; // Make sure this is correctly capturing the value
        
        console.log('Form submission data:', {
            id, word, category, description, matchType, priority, 
            active, active_value: $('#trigger-word-active').val() // Debug output
        });
        
        // Collect responses
        const responses = [];
        $('#responses-container .response-text').each(function() {
            const text = $(this).val().trim();
            if (text) {
                responses.push(text);
            }
        });
        
        // Collect follow-ups
        const followUps = [];
        $('#follow-ups-container .follow-up-text').each(function() {
            const text = $(this).val().trim();
            if (text) {
                followUps.push(text);
            }
        });
        
        // Validate
        if (!word) {
            alert('<?php _e('Please enter a trigger word or phrase.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        if (responses.length === 0) {
            alert('<?php _e('Please add at least one response.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_save_trigger_word',
                nonce: nonce,
                id: id,
                word: word,
                category: category,
                description: description,
                match_type: matchType,
                priority: priority,
                active: active ? 1 : 0, // Make sure we're sending a number, not boolean
                responses: responses,
                follow_ups: followUps
            },
            success: function(response) {
                console.log('Save response:', response); // Add debugging
                
                if (response.success) {
                    // Update local data
                    triggerWords[response.data.trigger_word.id] = response.data.trigger_word;
                    
                    // Update UI
                    refreshTriggerWordsList();
                    
                    // Hide modal
                    $('#trigger-word-modal').hide();
                    
                    // Show success message
                    alert(response.data.message);
                } else {
                    alert(response.data.message || '<?php _e('Error saving trigger word.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save AJAX error:', status, error, xhr.responseText);
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // ========== Dynamic Items Management ==========
    
    // Add response
    $('.add-response').on('click', function() {
        addDynamicItem('response');
    });
    
    // Add follow-up
    $('.add-follow-up').on('click', function() {
        addDynamicItem('follow-up');
    });
    
    // Remove dynamic item
    $(document).on('click', '.remove-item', function() {
        const $container = $(this).closest('.conversaai-dynamic-list');
        $(this).closest('.conversaai-dynamic-item').remove();
        
        // Show empty state if no items left
        if ($container.children('.conversaai-dynamic-item').length === 0) {
            if ($container.attr('id') === 'responses-container') {
                $container.html('<div class="conversaai-empty-state"><?php _e('No responses added yet. Click "Add Response" to add your first response.', 'conversaai-pro-wp'); ?></div>');
            } else {
                $container.html('<div class="conversaai-empty-state"><?php _e('No follow-up questions added yet. Click "Add Follow-up" to add your first question.', 'conversaai-pro-wp'); ?></div>');
            }
        }
    });
    
    // Category filter change
    $('#category-filter').on('change', function() {
        const category = $(this).val();
        refreshTriggerWordsList(category);
    });
    
    // Refresh button
    $('#refresh-trigger-words').on('click', function(e) {
        e.preventDefault();
        console.log('Refresh button clicked'); // Debug logging
        
        const $button = $(this);
        $button.prop('disabled', true);
        
        // Show spinner inside button
        const originalText = $button.html();
        $button.html('<span class="spinner is-active" style="float:left;margin:0 5px 0 0;"></span> <?php _e('Refreshing...', 'conversaai-pro-wp'); ?>');
        
        // Direct AJAX call without using a separate function
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_trigger_words',
                nonce: nonce,
                category: $('#category-filter').val()
            },
            success: function(response) {
                console.log('Refresh response:', response); // Debug logging
                
                if (response.success) {
                    // Update our local data
                    triggerWords = response.data.trigger_words;
                    
                    // Update categories list
                    const $categoryFilter = $('#category-filter');
                    const currentCategory = $categoryFilter.val();
                    
                    // Clear existing categories except first one
                    $categoryFilter.find('option:not(:first)').remove();
                    
                    // Add categories from response
                    if (response.data.categories && response.data.categories.length > 0) {
                        response.data.categories.forEach(function(cat) {
                            $categoryFilter.append(`<option value="${cat}">${cat}</option>`);
                        });
                    }
                    
                    // Restore selected category if still available
                    if (currentCategory) {
                        $categoryFilter.val(currentCategory);
                    }
                    
                    // Update the UI
                    refreshTriggerWordsList();
                    
                    // Show success message
                    alert('<?php _e('Trigger words refreshed successfully.', 'conversaai-pro-wp'); ?>');
                } else {
                    alert(response.data.message || '<?php _e('Error refreshing trigger words.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Refresh AJAX error:', status, error);
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false);
                $button.html(originalText);
            }
        });
    });
    // ========== Helper Functions ==========
    
    // Add dynamic item (response or follow-up)
    function addDynamicItem(type, value = '') {
        const containerId = type === 'response' ? 'responses-container' : 'follow-ups-container';
        const templateId = type === 'response' ? 'response-template' : 'follow-up-template';
        
        // Clear empty state if present
        const $container = $(`#${containerId}`);
        if ($container.find('.conversaai-empty-state').length > 0) {
            $container.empty();
        }
        
        // Clone template
        const template = document.getElementById(templateId);
        const clone = document.importNode(template.content, true);
        
        // Set value if provided
        if (value) {
            $(clone).find(type === 'response' ? '.response-text' : '.follow-up-text').val(value);
        }
        
        // Append to container
        $container.append(clone);
    }
    
    // Load trigger words from server
    function loadTriggerWords() {
        const category = $('#category-filter').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_trigger_words',
                nonce: nonce,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    triggerWords = response.data.trigger_words;
                    
                    // Update categories in filter
                    const $categoryFilter = $('#category-filter');
                    const currentCategory = $categoryFilter.val();
                    
                    $categoryFilter.find('option:not(:first)').remove();
                    response.data.categories.forEach(function(cat) {
                        $categoryFilter.append(`<option value="${cat}">${cat}</option>`);
                    });
                    
                    // Restore selected category if still available
                    if (currentCategory) {
                        $categoryFilter.val(currentCategory);
                    }
                    
                    // Update trigger words list
                    refreshTriggerWordsList();
                } else {
                    alert(response.data.message || '<?php _e('Error loading trigger words.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    }
    
    // Refresh trigger words list UI
    function refreshTriggerWordsList(category = '') {
        const $list = $('#trigger-words-list');
        const filteredWords = {};
        
        // Filter by category if specified
        if (category) {
            Object.keys(triggerWords).forEach(function(id) {
                if (triggerWords[id].category === category) {
                    filteredWords[id] = triggerWords[id];
                }
            });
        } else {
            Object.assign(filteredWords, triggerWords);
        }
        
        // Update count
        $('.conversaai-trigger-words-count').text(Object.keys(filteredWords).length);
        
        // Clear list
        $list.empty();
        
        // Show empty state if no items
        if (Object.keys(filteredWords).length === 0) {
            $list.html(`<tr class="no-items"><td colspan="7">${category ? '<?php _e('No trigger words found in this category.', 'conversaai-pro-wp'); ?>' : '<?php _e('No trigger words found. Click "Add Trigger Word" to create your first one.', 'conversaai-pro-wp'); ?>'}</td></tr>`);
            return;
        }
        
        // Sort by priority and word
        const sortedIds = Object.keys(filteredWords).sort(function(a, b) {
            const priorityA = parseInt(filteredWords[a].priority || 10);
            const priorityB = parseInt(filteredWords[b].priority || 10);
            
            if (priorityA !== priorityB) {
                return priorityB - priorityA; // Higher priority first
            }
            
            return filteredWords[a].word.localeCompare(filteredWords[b].word);
        });
        
        // Build list HTML
        sortedIds.forEach(function(id) {
            const trigger = filteredWords[id];
            const matchLabels = {
                'exact': '<?php _e('Exact', 'conversaai-pro-wp'); ?>',
                'contains': '<?php _e('Contains', 'conversaai-pro-wp'); ?>',
                'starts_with': '<?php _e('Starts with', 'conversaai-pro-wp'); ?>',
                'ends_with': '<?php _e('Ends with', 'conversaai-pro-wp'); ?>',
                'regex': '<?php _e('Regex', 'conversaai-pro-wp'); ?>'
            };
            
            const html = `
                <tr data-id="${id}">
                    <td class="column-trigger">
                        <strong>${escapeHtml(trigger.word)}</strong>
                        ${trigger.description ? `<div class="row-description">${escapeHtml(trigger.description)}</div>` : ''}
                    </td>
                    <td class="column-category">${escapeHtml(trigger.category || '')}</td>
                    <td class="column-match-type">
                        ${escapeHtml(matchLabels[trigger.match_type] || trigger.match_type || '<?php _e('Exact', 'conversaai-pro-wp'); ?>')}
                    </td>
                    <td class="column-responses">
                        ${(trigger.responses ? trigger.responses.length : 0)} <?php _e('response(s)', 'conversaai-pro-wp'); ?>
                    </td>
                    <td class="column-follow-ups">
                        ${(trigger.follow_ups ? trigger.follow_ups.length : 0)} <?php _e('follow-up(s)', 'conversaai-pro-wp'); ?>
                    </td>
                    <td class="column-status">
                        ${trigger.active ? 
                            '<span class="status-active"><?php _e('Active', 'conversaai-pro-wp'); ?></span>' : 
                            '<span class="status-inactive"><?php _e('Inactive', 'conversaai-pro-wp'); ?></span>'}
                    </td>
                    <td class="column-actions">
                        <button class="button edit-trigger-word" data-id="${id}">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="button delete-trigger-word" data-id="${id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;
            
            $list.append(html);
        });
        
        // No need to rebind events here since we're using event delegation
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
    
    // Initial refresh
    refreshTriggerWordsList();

    // Import/Export handlers
    $('#import-trigger-words').on('click', function() {
        $('#import-modal').show();
    });

    $('#export-trigger-words').on('click', function() {
        $('#export-modal').show();
    });

    // Change import format handler
    $('#import-format').on('change', function() {
        if ($(this).val() === 'csv') {
            $('.csv-options').show();
        } else {
            $('.csv-options').hide();
        }
    });

    // Import form handler
    $('#import-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $(this).find('button[type="submit"]');
        const $spinner = $(this).find('.spinner');
        
        // Validate file
        const fileInput = document.getElementById('import-file');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('<?php _e('Please select a file to import.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        const file = fileInput.files[0];
        const format = $('#import-format').val();
        const hasHeader = $('#csv-has-header').is(':checked');
        const importBehavior = $('#import-behavior').val();
        const defaultActive = $('#default-status').is(':checked');
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Read file
        const reader = new FileReader();
        reader.onload = function(e) {
            const fileContent = e.target.result;
            
            // Send to server
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_import_trigger_words',
                    nonce: nonce,
                    file_content: fileContent,
                    format: format,
                    has_header: hasHeader,
                    import_behavior: importBehavior,
                    default_active: defaultActive
                },
                success: function(response) {
                    if (response.success) {
                        // If replace was selected, clear the local data
                        if (importBehavior === 'replace') {
                            triggerWords = {};
                        }
                        
                        // Update local data with imported trigger words
                        if (response.data.imported_words) {
                            $.each(response.data.imported_words, function(id, word) {
                                triggerWords[id] = word;
                            });
                        }
                        
                        // Update UI
                        refreshTriggerWordsList();
                        
                        // Close modal
                        $('#import-modal').hide();
                        
                        // Show success message
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '<?php _e('Error importing trigger words.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Import AJAX error:', status, error);
                    alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        };
        
        if (format === 'csv') {
            reader.readAsText(file);
        } else {
            reader.readAsText(file);
        }
    });

    // Export form handler
    $('#export-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $(this).find('button[type="submit"]');
        const $spinner = $(this).find('.spinner');
        
        const format = $('#export-format').val();
        const category = $('#export-category').val();
        const activeOnly = $('#export-active-only').is(':checked');
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Send request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_export_trigger_words',
                nonce: nonce,
                format: format,
                category: category,
                active_only: activeOnly
            },
            success: function(response) {
                if (response.success) {
                    // Create download
                    const data = response.data.content;
                    const filename = response.data.filename;
                    const mimeType = format === 'csv' ? 'text/csv' : 'application/json';
                    
                    // Create download link
                    const blob = new Blob([data], { type: mimeType });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    
                    // Cleanup
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    
                    // Close modal
                    $('#export-modal').hide();
                } else {
                    alert(response.data.message || '<?php _e('Error exporting trigger words.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Export AJAX error:', status, error);
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});


</script>

<style>
/* Trigger Words tab styles */
.conversaai-trigger-words-container {
    margin-top: 20px;
}

.conversaai-trigger-words-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.conversaai-trigger-words-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.conversaai-trigger-words-stats {
    font-style: italic;
    color: #666;
}

.conversaai-trigger-words-count {
    font-weight: bold;
    color: #4c66ef;
}

.conversaai-trigger-words-table {
    width: 100%;
    border-collapse: collapse;
}

.conversaai-trigger-words-table .column-trigger {
    width: 20%;
}

.conversaai-trigger-words-table .column-category,
.conversaai-trigger-words-table .column-match-type,
.conversaai-trigger-words-table .column-status {
    width: 10%;
}

.conversaai-trigger-words-table .column-responses,
.conversaai-trigger-words-table .column-follow-ups {
    width: 12%;
    text-align: center;
}

.conversaai-trigger-words-table .column-actions {
    width: 12%;
    text-align: right;
}

.conversaai-trigger-words-table .row-description {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.status-active {
    display: inline-block;
    background-color: #d4edda;
    color: #155724;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.status-inactive {
    display: inline-block;
    background-color: #f8d7da;
    color: #721c24;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Modal styles */
.conversaai-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 50px;
    overflow-y: auto;
}

.conversaai-modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 800px;
    position: relative;
    margin-bottom: 50px;
}

.conversaai-modal-close {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.conversaai-modal-close:hover {
    color: #000;
}

/* Form styles */
.conversaai-form-row {
    margin-bottom: 20px;
}

.conversaai-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.conversaai-form-row-split {
    display: flex;
    gap: 20px;
}

.conversaai-form-row-split > div {
    flex: 1;
}

.conversaai-form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Tabs */
.conversaai-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.conversaai-tab {
    padding: 10px 20px;
    cursor: pointer;
    border: 1px solid transparent;
    border-bottom: none;
    margin-right: 5px;
    background: #f5f5f5;
}

.conversaai-tab.active {
    background: white;
    border-color: #ddd;
    border-bottom-color: white;
    margin-bottom: -1px;
}

.conversaai-tab-content {
    display: none;
}

.conversaai-tab-content.active {
    display: block;
}

/* Dynamic lists */
.conversaai-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.conversaai-dynamic-list {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background-color: #f9f9f9;
    max-height: 300px;
    overflow-y: auto;
}

.conversaai-dynamic-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    background-color: white;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #eee;
}

.conversaai-dynamic-item textarea {
    flex: 1;
}

.conversaai-dynamic-item .button {
    padding: 0;
    width: 30px;
    height: 30px;
    flex-shrink: 0;
}

.conversaai-dynamic-item .dashicons {
    margin-top: 3px;
}

.conversaai-empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
    font-style: italic;
}

/* Spinner */
.spinner.is-active {
    visibility: visible;
}

/* Responsive adjustments */
@media (max-width: 782px) {
    .conversaai-form-row-split {
        flex-direction: column;
        gap: 15px;
    }
    
    .conversaai-trigger-words-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .conversaai-trigger-words-stats {
        margin-top: 10px;
    }
    
    .conversaai-trigger-words-actions {
        flex-wrap: wrap;
    }
}

/* Import/Export styles */
.conversaai-import-export-actions {
    display: flex;
    gap: 10px;
}

.conversaai-import-template {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.conversaai-import-template pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}

.csv-options {
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    margin-bottom: 15px;
}

.dashicons {
 line-height: 1.4;
}
</style>