<?php
/**
 * AI Providers settings tab content.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Load the AI Factory to get available providers
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-ai-factory.php';
$ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
$available_providers = $ai_factory->get_available_providers();

// Get OpenAI provider for model options
$openai_provider = $ai_factory->get_provider('openai');
$openai_models = $openai_provider ? $openai_provider->get_available_models() : array();
?>

<form method="post" action="options.php" id="conversaai-ai-settings-form" class="conversaai-settings-form">
    <?php settings_fields('conversaai_pro_ai_settings'); ?>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="default_provider"><?php _e('Default AI Provider', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <select name="conversaai_pro_ai_settings[default_provider]" id="default_provider">
                    <?php foreach (array('openai' => 'OpenAI', 'deepseek' => 'DeepSeek', 'openrouter' => 'OpenRouter') as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected(isset($ai_settings['default_provider']) ? $ai_settings['default_provider'] : CONVERSAAI_PRO_DEFAULT_AI_PROVIDER, $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the AI provider to use by default. Only OpenAI is fully implemented in this version.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr id="openai_settings" class="provider-settings">
            <th scope="row">
                <label for="openai_api_key"><?php _e('OpenAI API Key', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="password" name="conversaai_pro_ai_settings[openai_api_key]" id="openai_api_key" class="regular-text" value="<?php 
                    $key = isset($ai_settings['openai_api_key']) ? $ai_settings['openai_api_key'] : '';
                    echo !empty($key) ? '****************************************' : '';
                ?>">
                <p class="description"><?php _e('Your OpenAI API key. This is required to use the OpenAI provider.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr id="openai_model_settings" class="provider-settings">
            <th scope="row">
                <label for="default_model"><?php _e('Default Model', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <select name="conversaai_pro_ai_settings[default_model]" id="default_model">
                    <?php foreach ($openai_models as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected(isset($ai_settings['default_model']) ? $ai_settings['default_model'] : CONVERSAAI_PRO_DEFAULT_MODEL, $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the model to use. More advanced models may provide better responses but cost more.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr id="deepseek_settings" class="provider-settings">
            <th scope="row">
                <label for="deepseek_api_key"><?php _e('DeepSeek API Key', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="password" name="conversaai_pro_ai_settings[deepseek_api_key]" id="deepseek_api_key" class="regular-text" value="<?php 
                    $key = isset($ai_settings['deepseek_api_key']) ? $ai_settings['deepseek_api_key'] : '';
                    echo !empty($key) ? '****************************************' : '';
                ?>">
                <p class="description"><?php _e('Your DeepSeek API key. This is required to use the DeepSeek provider.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr id="deepseek_model_settings" class="provider-settings">
            <th scope="row">
                <label for="deepseek_model"><?php _e('DeepSeek Model', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <select name="conversaai_pro_ai_settings[deepseek_model]" id="deepseek_model">
                    <?php 
                    $deepseek_provider = new \ConversaAI_Pro_WP\Integrations\AI\DeepSeek_Provider();
                    $deepseek_models = $deepseek_provider->get_available_models();
                    foreach ($deepseek_models as $id => $name): 
                    ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected(isset($ai_settings['deepseek_model']) ? $ai_settings['deepseek_model'] : 'deepseek-chat', $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the DeepSeek model to use.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr id="openrouter_settings" class="provider-settings">
            <th scope="row">
                <label for="openrouter_api_key"><?php _e('OpenRouter API Key', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="password" name="conversaai_pro_ai_settings[openrouter_api_key]" id="openrouter_api_key" class="regular-text" value="<?php 
                    $key = isset($ai_settings['openrouter_api_key']) ? $ai_settings['openrouter_api_key'] : '';
                    echo !empty($key) ? '****************************************' : '';
                ?>">
                <p class="description"><?php _e('Your OpenRouter API key. This is required to use the OpenRouter provider.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr id="openrouter_model_settings" class="provider-settings">
            <th scope="row">
                <label for="openrouter_model"><?php _e('OpenRouter Model', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <select name="conversaai_pro_ai_settings[openrouter_model]" id="openrouter_model">
                    <?php 
                    $openrouter_provider = new \ConversaAI_Pro_WP\Integrations\AI\OpenRouter_Provider();
                    $openrouter_models = $openrouter_provider->get_available_models();
                    foreach ($openrouter_models as $id => $name): 
                    ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected(isset($ai_settings['openrouter_model']) ? $ai_settings['openrouter_model'] : 'openai/gpt-3.5-turbo', $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the OpenRouter model to use.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="confidence_threshold"><?php _e('Confidence Threshold', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="range" name="conversaai_pro_ai_settings[confidence_threshold]" id="confidence_threshold" min="0" max="1" step="0.05" value="<?php echo esc_attr(isset($ai_settings['confidence_threshold']) ? $ai_settings['confidence_threshold'] : CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD); ?>">
                <span id="confidence_threshold_value"><?php echo esc_html(isset($ai_settings['confidence_threshold']) ? $ai_settings['confidence_threshold'] : CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD); ?></span>
                <p class="description"><?php _e('The minimum confidence level required to use a local knowledge base answer instead of AI. Higher values favor AI responses.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="temperature"><?php _e('Temperature', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="range" name="conversaai_pro_ai_settings[temperature]" id="temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr(isset($ai_settings['temperature']) ? $ai_settings['temperature'] : CONVERSAAI_PRO_DEFAULT_TEMPERATURE); ?>">
                <span id="temperature_value"><?php echo esc_html(isset($ai_settings['temperature']) ? $ai_settings['temperature'] : CONVERSAAI_PRO_DEFAULT_TEMPERATURE); ?></span>
                <p class="description"><?php _e('Controls randomness in AI responses. Lower values make responses more deterministic, higher values make them more creative.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="max_tokens"><?php _e('Max Tokens', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="number" name="conversaai_pro_ai_settings[max_tokens]" id="max_tokens" min="50" max="4096" step="1" class="small-text" value="<?php echo esc_attr(isset($ai_settings['max_tokens']) ? $ai_settings['max_tokens'] : CONVERSAAI_PRO_DEFAULT_MAX_TOKENS); ?>">
                <p class="description"><?php _e('Maximum number of tokens to generate in AI responses. Higher values allow longer responses but cost more.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="prioritize_local_kb"><?php _e('Prioritize Local Knowledge', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <label class="conversaai-switch">
                    <input type="checkbox" name="conversaai_pro_ai_settings[prioritize_local_kb]" id="prioritize_local_kb" value="1" <?php checked(isset($ai_settings['prioritize_local_kb']) ? $ai_settings['prioritize_local_kb'] : true); ?>>
                    <span class="conversaai-slider round"></span>
                </label>
                <p class="description"><?php _e('When enabled, local knowledge base matches that meet the confidence threshold will be used instead of querying the AI.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
    </table>
    
    <div class="conversaai-settings-actions">
        <button type="submit" class="button button-primary conversaai-save-button" data-settings-group="ai">
            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
        </button>
        <span class="conversaai-save-status"></span>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    // Update range input displays
    $('#confidence_threshold').on('input', function() {
        $('#confidence_threshold_value').text($(this).val());
    });
    
    $('#temperature').on('input', function() {
        $('#temperature_value').text($(this).val());
    });
    
    // Toggle provider settings based on selection
    $('#default_provider').on('change', function() {
        const provider = $(this).val();
        
        // Hide all provider settings
        $('.provider-settings').hide();
        
        // Show settings for selected provider
        if (provider === 'openai') {
            $('#openai_settings, #openai_model_settings').show();
        } else if (provider === 'deepseek') {
            $('#deepseek_settings, #deepseek_model_settings').show();
        } else if (provider === 'openrouter') {
            $('#openrouter_settings, #openrouter_model_settings').show();
        }
    }).trigger('change');
});
</script>

<style>
/* Range slider styling */
input[type="range"] {
    width: 300px;
    vertical-align: middle;
}

/* Number input styling */
input[type="number"].small-text {
    width: 80px;
}

/* Provider settings sections */
.provider-settings {
    display: none;
}
</style>