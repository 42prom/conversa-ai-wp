<?php
/**
 * Prompts management tab content.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get the saved prompts
$prompts = get_option('conversaai_pro_prompts', array());

// Default prompts if none exist
if (empty($prompts)) {
    $prompts = array(
        'default' => array(
            'name' => 'Default System Prompt',
            'content' => 'You are a helpful assistant for a WordPress website. Provide concise, accurate information to the user\'s queries.',
            'description' => 'Default prompt used when no specific prompt is selected.',
            'is_default' => true,
            'provider' => 'all'
        ),
        'customer_service' => array(
            'name' => 'Customer Service',
            'content' => 'You are a customer service representative for a WordPress website. Be polite, helpful, and focus on resolving customer issues efficiently.',
            'description' => 'Prompt optimized for customer service interactions.',
            'is_default' => false,
            'provider' => 'all'
        ),
        'product_expert' => array(
            'name' => 'Product Expert',
            'content' => 'You are a product expert who helps customers find the right products based on their needs. Recommend products thoughtfully and explain their benefits clearly.',
            'description' => 'Prompt for product recommendations and information.',
            'is_default' => false,
            'provider' => 'all'
        )
    );
    update_option('conversaai_pro_prompts', $prompts);
}

// Get the available AI providers
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-ai-factory.php';
$ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
$available_providers = $ai_factory->get_available_providers();
?>

<form method="post" action="options.php" id="conversaai-prompts-settings-form" class="conversaai-settings-form">
    <div class="conversaai-prompts-header">
        <h2><?php _e('Prompt Management', 'conversaai-pro-wp'); ?></h2>
        <button type="button" id="add-new-prompt" class="button button-primary"><?php _e('Add New Prompt', 'conversaai-pro-wp'); ?></button>
    </div>
    
    <p class="description"><?php _e('Manage system prompts that control how AI providers respond to user queries. These prompts set the context and personality for your AI assistant.', 'conversaai-pro-wp'); ?></p>
    
    <div class="conversaai-prompts-list">
        <?php foreach ($prompts as $id => $prompt): ?>
            <div class="conversaai-prompt-item" data-id="<?php echo esc_attr($id); ?>">
                <div class="conversaai-prompt-header">
                    <h3 class="conversaai-prompt-name">
                        <?php echo esc_html($prompt['name']); ?>
                        <?php if (isset($prompt['is_default']) && $prompt['is_default']): ?>
                            <span class="conversaai-default-badge"><?php _e('Default', 'conversaai-pro-wp'); ?></span>
                        <?php endif; ?>
                    </h3>
                    <div class="conversaai-prompt-actions">
                        <button type="button" class="button edit-prompt"><?php _e('Edit', 'conversaai-pro-wp'); ?></button>
                        <?php if (!isset($prompt['is_default']) || !$prompt['is_default']): ?>
                            <button type="button" class="button set-default-prompt"><?php _e('Set as Default', 'conversaai-pro-wp'); ?></button>
                            <button type="button" class="button button-link-delete delete-prompt"><?php _e('Delete', 'conversaai-pro-wp'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="conversaai-prompt-details">
                    <div class="conversaai-prompt-description"><?php echo esc_html($prompt['description']); ?></div>
                    <div class="conversaai-prompt-provider">
                        <?php 
                        $provider = isset($prompt['provider']) ? $prompt['provider'] : 'all';
                        echo $provider === 'all' 
                            ? esc_html__('All Providers', 'conversaai-pro-wp') 
                            : esc_html(isset($available_providers[$provider]) ? $available_providers[$provider] : $provider); 
                        ?>
                    </div>
                </div>
                <div class="conversaai-prompt-content">
                    <pre><?php echo esc_html($prompt['content']); ?></pre>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="conversaai-prompt-modal" style="display: none;">
        <div class="conversaai-modal-content">
            <span class="conversaai-modal-close">&times;</span>
            <h2 id="prompt-modal-title"><?php _e('Add New Prompt', 'conversaai-pro-wp'); ?></h2>
            
            <div class="conversaai-modal-form">
                <input type="hidden" id="prompt-id" value="">
                
                <div class="conversaai-form-row">
                    <label for="prompt-name"><?php _e('Prompt Name:', 'conversaai-pro-wp'); ?></label>
                    <input type="text" id="prompt-name" class="regular-text" required>
                    <p class="description"><?php _e('A descriptive name for this prompt.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="prompt-description"><?php _e('Description:', 'conversaai-pro-wp'); ?></label>
                    <input type="text" id="prompt-description" class="regular-text">
                    <p class="description"><?php _e('Optional description of when to use this prompt.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="prompt-provider"><?php _e('AI Provider:', 'conversaai-pro-wp'); ?></label>
                    <select id="prompt-provider">
                        <option value="all"><?php _e('All Providers', 'conversaai-pro-wp'); ?></option>
                        <?php foreach ($available_providers as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Which AI provider this prompt is designed for.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-row">
                    <label for="prompt-content"><?php _e('Prompt Content:', 'conversaai-pro-wp'); ?></label>
                    <textarea id="prompt-content" rows="10" class="large-text" required></textarea>
                    <p class="description"><?php _e('The system prompt that will be sent to the AI. This sets the context and behavior of the assistant.', 'conversaai-pro-wp'); ?></p>
                </div>
                
                <div class="conversaai-form-actions">
                    <button type="button" id="save-prompt" class="button button-primary"><?php _e('Save Prompt', 'conversaai-pro-wp'); ?></button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="conversaai-settings-actions">
        <button type="button" class="button button-primary conversaai-save-button" data-settings-group="prompts">
            <?php _e('Save All Changes', 'conversaai-pro-wp'); ?>
        </button>
        <span class="conversaai-save-status"></span>
    </div>
</form>

<style>
/* Prompt management styles */
.conversaai-prompts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.conversaai-prompts-list {
    margin: 20px 0;
}

.conversaai-prompt-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
    overflow: hidden;
}

.conversaai-prompt-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
}

.conversaai-prompt-name {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-default-badge {
    background: #4c66ef;
    color: white;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: normal;
}

.conversaai-prompt-actions {
    display: flex;
    gap: 5px;
}

.conversaai-prompt-details {
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    background: #fafafa;
    font-size: 13px;
    color: #666;
}

.conversaai-prompt-content {
    padding: 0 15px 15px;
    max-height: 100px;
    overflow-y: auto;
}

.conversaai-prompt-content pre {
    margin: 0;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 13px;
    background: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
}

/* Modal styles */
.conversaai-prompt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.conversaai-modal-content {
    background: white;
    padding: 20px;
    border-radius: 4px;
    width: 80%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.conversaai-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
}

.conversaai-form-row {
    margin-bottom: 15px;
}

.conversaai-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.conversaai-form-actions {
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add new prompt button
    $('#add-new-prompt').on('click', function() {
        // Reset form
        $('#prompt-modal-title').text('<?php _e('Add New Prompt', 'conversaai-pro-wp'); ?>');
        $('#prompt-id').val('');
        $('#prompt-name').val('');
        $('#prompt-description').val('');
        $('#prompt-provider').val('all');
        $('#prompt-content').val('');
        
        // Show modal
        $('.conversaai-prompt-modal').show();
    });
    
    // Close modal
    $('.conversaai-modal-close').on('click', function() {
        $('.conversaai-prompt-modal').hide();
    });
    
    // Click outside modal to close
    $('.conversaai-prompt-modal').on('click', function(e) {
        if ($(e.target).hasClass('conversaai-prompt-modal')) {
            $(this).hide();
        }
    });
    
    // Edit prompt button
    $('.edit-prompt').on('click', function() {
        const $promptItem = $(this).closest('.conversaai-prompt-item');
        const promptId = $promptItem.data('id');
        const promptName = $promptItem.find('.conversaai-prompt-name').text().trim().replace('Default', '').trim();
        const promptDescription = $promptItem.find('.conversaai-prompt-description').text().trim();
        const promptProvider = $promptItem.find('.conversaai-prompt-provider').text().trim() === '<?php _e('All Providers', 'conversaai-pro-wp'); ?>' ? 'all' : $promptItem.find('.conversaai-prompt-provider').text().trim();
        const promptContent = $promptItem.find('.conversaai-prompt-content pre').text().trim();
        
        // Populate form
        $('#prompt-modal-title').text('<?php _e('Edit Prompt', 'conversaai-pro-wp'); ?>');
        $('#prompt-id').val(promptId);
        $('#prompt-name').val(promptName);
        $('#prompt-description').val(promptDescription);
        
        // Find the correct provider value
        let providerValue = 'all';
        <?php foreach ($available_providers as $id => $name): ?>
            if ('<?php echo esc_js($name); ?>' === promptProvider) {
                providerValue = '<?php echo esc_js($id); ?>';
            }
        <?php endforeach; ?>
        
        $('#prompt-provider').val(providerValue);
        $('#prompt-content').val(promptContent);
        
        // Show modal
        $('.conversaai-prompt-modal').show();
    });
    
    // Set as default button
    $('.set-default-prompt').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to set this as the default prompt? The current default will be unset.', 'conversaai-pro-wp'); ?>')) {
            const promptId = $(this).closest('.conversaai-prompt-item').data('id');
            
            // Show loading spinner
            const $status = $('.conversaai-save-status');
            $status.text('<?php _e('Setting default...', 'conversaai-pro-wp'); ?>').show();
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_set_default_prompt',
                    nonce: '<?php echo wp_create_nonce('conversaai_prompt_nonce'); ?>',
                    prompt_id: promptId
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('<?php _e('Default prompt updated successfully.', 'conversaai-pro-wp'); ?>');
                        // Reload the page to show updated default status
                        location.reload();
                    } else {
                        $status.text(response.data.message || '<?php _e('Error updating default prompt.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function() {
                    $status.text('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                },
                complete: function() {
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 3000);
                }
            });
        }
    });
    
    // Delete prompt button
    $('.delete-prompt').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to delete this prompt? This cannot be undone.', 'conversaai-pro-wp'); ?>')) {
            const promptId = $(this).closest('.conversaai-prompt-item').data('id');
            
            // Show loading spinner
            const $status = $('.conversaai-save-status');
            $status.text('<?php _e('Deleting...', 'conversaai-pro-wp'); ?>').show();
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_delete_prompt',
                    nonce: '<?php echo wp_create_nonce('conversaai_prompt_nonce'); ?>',
                    prompt_id: promptId
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('<?php _e('Prompt deleted successfully.', 'conversaai-pro-wp'); ?>');
                        // Remove the prompt from the UI
                        $('.conversaai-prompt-item[data-id="' + promptId + '"]').slideUp(function() {
                            $(this).remove();
                        });
                    } else {
                        $status.text(response.data.message || '<?php _e('Error deleting prompt.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function() {
                    $status.text('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                },
                complete: function() {
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 3000);
                }
            });
        }
    });
    
    // Save prompt button
    $('#save-prompt').on('click', function() {
        const $button = $(this);
        const $spinner = $button.next('.spinner');
        const $form = $button.closest('.conversaai-modal-form');
        
        // Validate form
        const promptName = $('#prompt-name').val();
        const promptContent = $('#prompt-content').val();
        
        if (!promptName || !promptContent) {
            alert('<?php _e('Name and content are required.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Collect form data
        const promptData = {
            id: $('#prompt-id').val(),
            name: promptName,
            description: $('#prompt-description').val(),
            provider: $('#prompt-provider').val(),
            content: promptContent
        };
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_save_prompt',
                nonce: '<?php echo wp_create_nonce('conversaai_prompt_nonce'); ?>',
                prompt_data: promptData
            },
            success: function(response) {
                if (response.success) {
                    $('.conversaai-prompt-modal').hide();
                    
                    // Show success message
                    const $status = $('.conversaai-save-status');
                    $status.text('<?php _e('Prompt saved successfully. Reloading...', 'conversaai-pro-wp'); ?>').show();
                    
                    // Reload the page to show the updated prompt
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message || '<?php _e('Error saving prompt.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
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