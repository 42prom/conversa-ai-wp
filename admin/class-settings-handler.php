<?php
/**
 * Handles saving of plugin settings.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Settings handler class.
 *
 * Implements consistent saving mechanism for plugin settings.
 *
 * @since      1.0.0
 */
class Settings_Handler {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     */
    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
        
        // Add AJAX action for settings save
        add_action('wp_ajax_conversaai_save_settings', array($this, 'ajax_save_settings'));
        
        // Add AJAX handlers for prompt management
        add_action('wp_ajax_conversaai_set_default_prompt', array($this, 'ajax_set_default_prompt'));
        add_action('wp_ajax_conversaai_delete_prompt', array($this, 'ajax_delete_prompt'));
        add_action('wp_ajax_conversaai_save_prompt', array($this, 'ajax_save_prompt'));
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register general settings
        register_setting(
            'conversaai_pro_general_settings',
            'conversaai_pro_general_settings',
            array($this, 'sanitize_general_settings')
        );
        
        // Register AI settings
        register_setting(
            'conversaai_pro_ai_settings',
            'conversaai_pro_ai_settings',
            array($this, 'sanitize_ai_settings')
        );
        
        // Register appearance settings
        register_setting(
            'conversaai_pro_appearance_settings',
            'conversaai_pro_appearance_settings',
            array($this, 'sanitize_appearance_settings')
        );
    }

    /**
     * Sanitize general settings.
     *
     * @since    1.0.0
     * @param    array    $input    The settings input.
     * @return   array    The sanitized settings.
     */
    public function sanitize_general_settings($input) {
        $sanitized = array();
        
        // Enable chat widget (boolean)
        $sanitized['enable_chat_widget'] = isset($input['enable_chat_widget']) ? (bool) $input['enable_chat_widget'] : false;
        
        // Chat title (text)
        $sanitized['chat_title'] = isset($input['chat_title']) ? sanitize_text_field($input['chat_title']) : '';
        
        // Welcome message (text)
        $sanitized['welcome_message'] = isset($input['welcome_message']) ? sanitize_textarea_field($input['welcome_message']) : '';
        
        // Placeholder text (text)
        $sanitized['placeholder_text'] = isset($input['placeholder_text']) ? sanitize_text_field($input['placeholder_text']) : '';
        
        // Offline message (text)
        $sanitized['offline_message'] = isset($input['offline_message']) ? sanitize_textarea_field($input['offline_message']) : '';
        
        return $sanitized;
    }

    /**
     * Sanitize AI settings.
     *
     * @since    1.0.0
     * @param    array    $input    The settings input.
     * @return   array    The sanitized settings.
     */
    public function sanitize_ai_settings($input) {
        $sanitized = array();
        
        // Default provider (text)
        $sanitized['default_provider'] = isset($input['default_provider']) ? sanitize_text_field($input['default_provider']) : CONVERSAAI_PRO_DEFAULT_AI_PROVIDER;
        
        // Default model (text)
        $sanitized['default_model'] = isset($input['default_model']) ? sanitize_text_field($input['default_model']) : CONVERSAAI_PRO_DEFAULT_MODEL;
        
        // OpenAI API key (obscured text)
        if (isset($input['openai_api_key']) && !empty($input['openai_api_key'])) {
            // If the key starts with "sk-" and has proper length, it's likely a real key
            if (substr($input['openai_api_key'], 0, 3) === 'sk-' && strlen($input['openai_api_key']) > 20) {
                $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
            } else {
                // Otherwise, keep the existing key (might be a placeholder)
                $existing_settings = get_option('conversaai_pro_ai_settings', array());
                $sanitized['openai_api_key'] = isset($existing_settings['openai_api_key']) ? $existing_settings['openai_api_key'] : '';
            }
        } else {
            $sanitized['openai_api_key'] = '';
        }
        
        // DeepSeek API key (obscured text)
        if (isset($input['deepseek_api_key']) && !empty($input['deepseek_api_key'])) {
            if (strlen($input['deepseek_api_key']) > 20) {
                $sanitized['deepseek_api_key'] = sanitize_text_field($input['deepseek_api_key']);
            } else {
                $existing_settings = get_option('conversaai_pro_ai_settings', array());
                $sanitized['deepseek_api_key'] = isset($existing_settings['deepseek_api_key']) ? $existing_settings['deepseek_api_key'] : '';
            }
        } else {
            $sanitized['deepseek_api_key'] = '';
        }
        
        // DeepSeek model
        $sanitized['deepseek_model'] = isset($input['deepseek_model']) ? sanitize_text_field($input['deepseek_model']) : 'deepseek-chat';
        
        // OpenRouter API key (obscured text)
        if (isset($input['openrouter_api_key']) && !empty($input['openrouter_api_key'])) {
            if (strlen($input['openrouter_api_key']) > 20) {
                $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key']);
            } else {
                $existing_settings = get_option('conversaai_pro_ai_settings', array());
                $sanitized['openrouter_api_key'] = isset($existing_settings['openrouter_api_key']) ? $existing_settings['openrouter_api_key'] : '';
            }
        } else {
            $sanitized['openrouter_api_key'] = '';
        }
        
        // OpenRouter model
        $sanitized['openrouter_model'] = isset($input['openrouter_model']) ? sanitize_text_field($input['openrouter_model']) : 'openai/gpt-3.5-turbo';
        
        // Confidence threshold (float between 0 and 1)
        $sanitized['confidence_threshold'] = isset($input['confidence_threshold']) 
            ? min(1, max(0, (float) $input['confidence_threshold'])) 
            : CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD;
        
        // Temperature (float between 0 and 2)
        $sanitized['temperature'] = isset($input['temperature']) 
            ? min(2, max(0, (float) $input['temperature'])) 
            : CONVERSAAI_PRO_DEFAULT_TEMPERATURE;
        
        // Max tokens (integer between 50 and 4096)
        $sanitized['max_tokens'] = isset($input['max_tokens']) 
            ? min(4096, max(50, (int) $input['max_tokens'])) 
            : CONVERSAAI_PRO_DEFAULT_MAX_TOKENS;
        
        // Prioritize local KB (boolean)
        $sanitized['prioritize_local_kb'] = isset($input['prioritize_local_kb']) ? (bool) $input['prioritize_local_kb'] : true;
        
        return $sanitized;
    }

    /**
     * Sanitize appearance settings.
     *
     * @since    1.0.0
     * @param    array    $input    The settings input.
     * @return   array    The sanitized settings.
     */
    public function sanitize_appearance_settings($input) {
        $sanitized = array();
        
        // Primary color (hex color)
        $sanitized['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : '#4c66ef';

        // Title color (hex color) 
        $sanitized['title_color'] = isset($input['title_color']) ? sanitize_hex_color($input['title_color']) : '#ffffff';
        
        // Text color (hex color)
        $sanitized['text_color'] = isset($input['text_color']) ? sanitize_hex_color($input['text_color']) : '#333333';

        // Toggle button icon (URL) 
        $sanitized['toggle_button_icon'] = isset($input['toggle_button_icon']) ? esc_url_raw($input['toggle_button_icon']) : '';
        
        // Bot message background (hex color)
        $sanitized['bot_message_bg'] = isset($input['bot_message_bg']) ? sanitize_hex_color($input['bot_message_bg']) : '#f0f4ff';
        
        // User message background (hex color)
        $sanitized['user_message_bg'] = isset($input['user_message_bg']) ? sanitize_hex_color($input['user_message_bg']) : '#e1ebff';
        
        // Font family (text)
        $sanitized['font_family'] = isset($input['font_family']) ? sanitize_text_field($input['font_family']) : 'inherit';
        
        // Border radius (text with px/rem/em)
        $sanitized['border_radius'] = isset($input['border_radius']) ? sanitize_text_field($input['border_radius']) : '8px';
        
        // Position (select: left, right)
        $sanitized['position'] = isset($input['position']) && in_array($input['position'], array('left', 'right')) 
            ? $input['position'] 
            : 'right';
        
        // Logo URL (URL)
        $sanitized['logo_url'] = isset($input['logo_url']) ? esc_url_raw($input['logo_url']) : '';
        
        // Bot avatar (URL)
        $sanitized['bot_avatar'] = isset($input['bot_avatar']) ? esc_url_raw($input['bot_avatar']) : '';
        
        // User avatar (URL)
        $sanitized['user_avatar'] = isset($input['user_avatar']) ? esc_url_raw($input['user_avatar']) : '';

        // Logo size (numeric)
        $sanitized['logo_size'] = isset($input['logo_size']) 
        ? intval($input['logo_size']) 
        : 28;

        // Toggle icon size (numeric)
        $sanitized['toggle_icon_size'] = isset($input['toggle_icon_size']) 
        ? intval($input['toggle_icon_size']) 
        : 60;

        // Widget border radius (text with px/rem/em)
        $sanitized['widget_border_radius'] = isset($input['widget_border_radius']) 
        ? sanitize_text_field($input['widget_border_radius']) 
        : '16px';

        // Input border radius (text with px/rem/em)
        $sanitized['input_border_radius'] = isset($input['input_border_radius']) 
        ? sanitize_text_field($input['input_border_radius']) 
        : '8px';

        // Send button border radius (text with px/rem/em)
        $sanitized['send_button_border_radius'] = isset($input['send_button_border_radius']) 
        ? sanitize_text_field($input['send_button_border_radius']) 
        : '8px';

        // Auto-open delay (numeric)
        $sanitized['auto_open_delay'] = isset($input['auto_open_delay']) 
        ? intval($input['auto_open_delay']) 
        : 0;

        // Desktop settings
        $sanitized['desktop_position'] = isset($input['desktop_position']) && in_array($input['desktop_position'], ['bottom-right', 'bottom-left', 'top-right', 'top-left']) 
        ? $input['desktop_position'] 
        : 'bottom-right';
        $sanitized['desktop_distance_x'] = isset($input['desktop_distance_x']) 
        ? intval($input['desktop_distance_x']) 
        : 20;
        $sanitized['desktop_distance_y'] = isset($input['desktop_distance_y']) 
        ? intval($input['desktop_distance_y']) 
        : 20;
        $sanitized['desktop_width'] = isset($input['desktop_width']) 
        ? intval($input['desktop_width']) 
        : 380;
        $sanitized['desktop_height'] = isset($input['desktop_height']) 
        ? intval($input['desktop_height']) 
        : 500;

        // Tablet settings
        $sanitized['tablet_position'] = isset($input['tablet_position']) && in_array($input['tablet_position'], ['bottom-right', 'bottom-left', 'top-right', 'top-left']) 
        ? $input['tablet_position'] 
        : 'bottom-right';
        $sanitized['tablet_distance_x'] = isset($input['tablet_distance_x']) 
        ? intval($input['tablet_distance_x']) 
        : 15;
        $sanitized['tablet_distance_y'] = isset($input['tablet_distance_y']) 
        ? intval($input['tablet_distance_y']) 
        : 15;
        $sanitized['tablet_width'] = isset($input['tablet_width']) 
        ? intval($input['tablet_width']) 
        : 340;
        $sanitized['tablet_height'] = isset($input['tablet_height']) 
        ? intval($input['tablet_height']) 
        : 450;

        // Mobile settings
        $sanitized['mobile_position'] = isset($input['mobile_position']) && in_array($input['mobile_position'], ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'bottom-center']) 
        ? $input['mobile_position'] 
        : 'bottom-right';
        $sanitized['mobile_distance_x'] = isset($input['mobile_distance_x']) 
        ? intval($input['mobile_distance_x']) 
        : 10;
        $sanitized['mobile_distance_y'] = isset($input['mobile_distance_y']) 
        ? intval($input['mobile_distance_y']) 
        : 10;
        $sanitized['mobile_width'] = isset($input['mobile_width']) && in_array($input['mobile_width'], ['full', 'custom']) 
        ? $input['mobile_width'] 
        : 'full';
        $sanitized['mobile_width_custom'] = isset($input['mobile_width_custom']) 
        ? intval($input['mobile_width_custom']) 
        : 300;
        $sanitized['mobile_height'] = isset($input['mobile_height']) && in_array($input['mobile_height'], ['full', 'custom']) 
        ? $input['mobile_height'] 
        : 'full';
        $sanitized['mobile_height_custom'] = isset($input['mobile_height_custom']) 
        ? intval($input['mobile_height_custom']) 
        : 400;
        
        return $sanitized;
    }

    /**
     * Handle AJAX settings save.
     *
     * @since    1.0.0
     */
    public function ajax_save_settings() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_pro_settings_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to change settings.', 'conversaai-pro-wp')));
        }
        
        // Get the settings data
        $settings_group = isset($_POST['settings_group']) ? sanitize_text_field($_POST['settings_group']) : '';
        $settings_data = isset($_POST['settings_data']) ? $_POST['settings_data'] : array();
        
        // Validate settings group
        $valid_groups = array('general', 'ai', 'appearance');
        if (!in_array($settings_group, $valid_groups)) {
            wp_send_json_error(array('message' => __('Invalid settings group.', 'conversaai-pro-wp')));
        }
        
        // Process based on settings group
        switch ($settings_group) {
            case 'general':
                $sanitized = $this->sanitize_general_settings($settings_data);
                update_option('conversaai_pro_general_settings', $sanitized);
                break;
                
            case 'ai':
                $sanitized = $this->sanitize_ai_settings($settings_data);
                update_option('conversaai_pro_ai_settings', $sanitized);
                
                // Reset AI provider instances when AI settings change
                $ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
                $ai_factory->reset_providers();
                break;
                
            case 'appearance':
                $sanitized = $this->sanitize_appearance_settings($settings_data);
                update_option('conversaai_pro_appearance_settings', $sanitized);
                break;
        }
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully.', 'conversaai-pro-wp'),
            'settings' => $sanitized,
        ));
    }

    /**
     * Handle setting a default prompt.
     *
     * @since    1.0.0
     */
    public function ajax_set_default_prompt() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_prompt_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to change prompts.', 'conversaai-pro-wp')));
        }
        
        // Get prompt ID
        $prompt_id = isset($_POST['prompt_id']) ? sanitize_text_field($_POST['prompt_id']) : '';
        
        if (empty($prompt_id)) {
            wp_send_json_error(array('message' => __('No prompt ID provided.', 'conversaai-pro-wp')));
        }
        
        // Get current prompts
        $prompts = get_option('conversaai_pro_prompts', array());
        
        // Check if prompt exists
        if (!isset($prompts[$prompt_id])) {
            wp_send_json_error(array('message' => __('Prompt not found.', 'conversaai-pro-wp')));
        }
        
        // Update default status
        foreach ($prompts as $id => $prompt) {
            if ($id === $prompt_id) {
                $prompts[$id]['is_default'] = true;
            } else {
                $prompts[$id]['is_default'] = false;
            }
        }
        
        // Save updated prompts
        update_option('conversaai_pro_prompts', $prompts);
        
        // Reset AI provider instances to ensure they use the new default prompt
        $ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
        $ai_factory->reset_providers();
        
        wp_send_json_success(array('message' => __('Default prompt updated successfully.', 'conversaai-pro-wp')));
    }

    /**
     * Handle deleting a prompt.
     *
     * @since    1.0.0
     */
    public function ajax_delete_prompt() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_prompt_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete prompts.', 'conversaai-pro-wp')));
        }
        
        // Get prompt ID
        $prompt_id = isset($_POST['prompt_id']) ? sanitize_text_field($_POST['prompt_id']) : '';
        
        if (empty($prompt_id)) {
            wp_send_json_error(array('message' => __('No prompt ID provided.', 'conversaai-pro-wp')));
        }
        
        // Get current prompts
        $prompts = get_option('conversaai_pro_prompts', array());
        
        // Check if prompt exists
        if (!isset($prompts[$prompt_id])) {
            wp_send_json_error(array('message' => __('Prompt not found.', 'conversaai-pro-wp')));
        }
        
        // Check if it's the default prompt
        if (isset($prompts[$prompt_id]['is_default']) && $prompts[$prompt_id]['is_default']) {
            wp_send_json_error(array('message' => __('Cannot delete the default prompt.', 'conversaai-pro-wp')));
        }
        
        // Delete the prompt
        unset($prompts[$prompt_id]);
        
        // Save updated prompts
        update_option('conversaai_pro_prompts', $prompts);
        
        wp_send_json_success(array('message' => __('Prompt deleted successfully.', 'conversaai-pro-wp')));
    }

    /**
     * Handle saving a prompt.
     *
     * @since    1.0.0
     */
    public function ajax_save_prompt() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_prompt_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to save prompts.', 'conversaai-pro-wp')));
        }
        
        // Get prompt data
        $prompt_data = isset($_POST['prompt_data']) ? $_POST['prompt_data'] : array();
        
        if (empty($prompt_data) || !isset($prompt_data['name']) || !isset($prompt_data['content'])) {
            wp_send_json_error(array('message' => __('Invalid prompt data.', 'conversaai-pro-wp')));
        }
        
        // Sanitize input
        $prompt_id = isset($prompt_data['id']) && !empty($prompt_data['id']) ? 
            sanitize_text_field($prompt_data['id']) : 
            'prompt_' . uniqid();
        
        $prompt = array(
            'name' => sanitize_text_field($prompt_data['name']),
            'content' => sanitize_textarea_field($prompt_data['content']),
            'description' => isset($prompt_data['description']) ? sanitize_text_field($prompt_data['description']) : '',
            'provider' => isset($prompt_data['provider']) ? sanitize_text_field($prompt_data['provider']) : 'all',
        );
        
        // Get current prompts
        $prompts = get_option('conversaai_pro_prompts', array());
        
        // If this is an edit, preserve the default status
        if (isset($prompts[$prompt_id]) && isset($prompts[$prompt_id]['is_default'])) {
            $prompt['is_default'] = $prompts[$prompt_id]['is_default'];
        }
        
        // Save the prompt
        $prompts[$prompt_id] = $prompt;
        
        // If there's only one prompt, make it the default
        if (count($prompts) === 1) {
            $prompts[$prompt_id]['is_default'] = true;
        }
        
        // Save updated prompts
        update_option('conversaai_pro_prompts', $prompts);
        
        // Reset AI provider instances to ensure they use the updated prompts
        $ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
        $ai_factory->reset_providers();
        
        wp_send_json_success(array(
            'message' => __('Prompt saved successfully.', 'conversaai-pro-wp'),
            'prompt_id' => $prompt_id
        ));
    }
}