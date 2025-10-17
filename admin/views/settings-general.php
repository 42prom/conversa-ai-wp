<?php
/**
 * General settings tab content.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<form method="post" action="options.php" id="conversaai-general-settings-form" class="conversaai-settings-form">
    <?php settings_fields('conversaai_pro_general_settings'); ?>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="enable_chat_widget"><?php _e('Enable Chat Widget', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <label class="conversaai-switch">
                    <input type="checkbox" name="conversaai_pro_general_settings[enable_chat_widget]" id="enable_chat_widget" value="1" <?php checked(isset($general_settings['enable_chat_widget']) ? $general_settings['enable_chat_widget'] : false); ?>>
                    <span class="conversaai-slider round"></span>
                </label>
                <p class="description"><?php _e('Enable or disable the chat widget on your site.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="chat_title"><?php _e('Chat Title', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_general_settings[chat_title]" id="chat_title" class="regular-text" value="<?php echo esc_attr(isset($general_settings['chat_title']) ? $general_settings['chat_title'] : ''); ?>" placeholder="<?php _e('How can we help you?', 'conversaai-pro-wp'); ?>">
                <p class="description"><?php _e('The title that appears at the top of the chat widget.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="welcome_message"><?php _e('Welcome Message', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <textarea name="conversaai_pro_general_settings[welcome_message]" id="welcome_message" class="large-text" rows="4" placeholder="<?php _e('Hello! I\'m your AI assistant. How can I help you today?', 'conversaai-pro-wp'); ?>"><?php echo esc_textarea(isset($general_settings['welcome_message']) ? $general_settings['welcome_message'] : ''); ?></textarea>
                <p class="description"><?php _e('The welcome message that appears when a user first opens the chat.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="placeholder_text"><?php _e('Input Placeholder', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_general_settings[placeholder_text]" id="placeholder_text" class="regular-text" value="<?php echo esc_attr(isset($general_settings['placeholder_text']) ? $general_settings['placeholder_text'] : ''); ?>" placeholder="<?php _e('Type your message...', 'conversaai-pro-wp'); ?>">
                <p class="description"><?php _e('The placeholder text shown in the chat input field.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="offline_message"><?php _e('Offline Message', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <textarea name="conversaai_pro_general_settings[offline_message]" id="offline_message" class="large-text" rows="4" placeholder="<?php _e('We\'re currently offline. Please leave a message and we\'ll get back to you.', 'conversaai-pro-wp'); ?>"><?php echo esc_textarea(isset($general_settings['offline_message']) ? $general_settings['offline_message'] : ''); ?></textarea>
                <p class="description"><?php _e('The message that appears when the chat is offline (not yet implemented).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="enable_logging"><?php _e('Enable Logging', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <label class="conversaai-switch">
                    <input type="checkbox" name="conversaai_pro_general_settings[enable_logging]" id="enable_logging" value="1" <?php checked(isset($general_settings['enable_logging']) ? $general_settings['enable_logging'] : false); ?>>
                    <span class="conversaai-slider round"></span>
                </label>
                <p class="description"><?php _e('Enable or disable debug logging for the plugin.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
    </table>
    
    <div class="conversaai-settings-actions">
        <button type="submit" class="button button-primary conversaai-save-button" data-settings-group="general">
            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
        </button>
        <span class="conversaai-save-status"></span>
    </div>
</form>

<style>
/* Switch toggle styling */
.conversaai-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.conversaai-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.conversaai-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.conversaai-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .conversaai-slider {
    background-color: #4c66ef;
}

input:focus + .conversaai-slider {
    box-shadow: 0 0 1px #4c66ef;
}

input:checked + .conversaai-slider:before {
    transform: translateX(26px);
}

/* Rounded sliders */
.conversaai-slider.round {
    border-radius: 34px;
}

.conversaai-slider.round:before {
    border-radius: 50%;
}

/* Settings action buttons */
.conversaai-settings-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.conversaai-save-status {
    display: inline-block;
    margin-left: 10px;
    font-style: italic;
}
</style>