<?php
/**
 * Factory for AI providers.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/ai
 */

namespace ConversaAI_Pro_WP\Integrations\AI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Factory for AI providers.
 *
 * Creates and manages AI provider instances.
 *
 * @since      1.0.0
 */
class AI_Factory {

    /**
     * Available AI providers.
     *
     * @since    1.0.0
     * @access   private
     * @var      array   
     */
    private $providers = array();

    /**
     * Initialize the factory.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->register_providers();
    }

    /**
     * Register available AI providers.
     *
     * @since    1.0.0
     */
    private function register_providers() {
        // Register OpenAI provider
        $this->providers['openai'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\AI\OpenAI_Provider',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-openai-provider.php',
            'instance' => null,
        );
        
        // Register DeepSeek provider
        $this->providers['deepseek'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\AI\DeepSeek_Provider',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-deepseek-provider.php',
            'instance' => null,
        );
        
        // Register OpenRouter provider
        $this->providers['openrouter'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\AI\OpenRouter_Provider',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-openrouter-provider.php',
            'instance' => null,
        );
        
        // Allow plugins to register additional providers
        $this->providers = apply_filters('conversaai_pro_register_ai_providers', $this->providers);
    }

    /**
     * Get an AI provider instance.
     *
     * @since    1.0.0
     * @param    string    $provider_name    The name of the provider.
     * @param    bool      $fresh_instance   Whether to create a fresh instance, ignoring cache.
     * @return   AI_Provider|null    The provider instance or null if not found.
     */
    public function get_provider($provider_name, $fresh_instance = false) {
        // Check if provider exists
        if (!isset($this->providers[$provider_name])) {
            return null;
        }
        
        // If instance already exists and we don't need a fresh one, return it
        if (!$fresh_instance && $this->providers[$provider_name]['instance'] !== null) {
            return $this->providers[$provider_name]['instance'];
        }
        
        // Otherwise, create a new instance
        $provider_file = $this->providers[$provider_name]['file'];
        $provider_class = $this->providers[$provider_name]['class'];
        
        // Check if file exists before requiring it (it should already be loaded, but just in case)
        if (file_exists($provider_file) && !class_exists($provider_class, false)) {
            require_once $provider_file;
        }
        
        // Check if class exists now
        if (class_exists($provider_class)) {
            $provider = new $provider_class();
            
            // Initialize the provider with settings
            $ai_settings = get_option('conversaai_pro_ai_settings', array());
            $provider->initialize($ai_settings);
            
            // Store the instance only if we're not requesting a fresh instance
            if (!$fresh_instance) {
                $this->providers[$provider_name]['instance'] = $provider;
            }
            
            return $provider;
        }
        
        return null;
    }

    /**
     * Reset all provider instances.
     * 
     * Call this when settings change to ensure providers use the latest settings.
     *
     * @since    1.0.0
     */
    public function reset_providers() {
        foreach ($this->providers as $name => $info) {
            $this->providers[$name]['instance'] = null;
        }
    }

    /**
     * Get all available providers.
     *
     * @since    1.0.0
     * @return   array    List of available providers.
     */
    public function get_available_providers() {
        $available = array();
        
        foreach ($this->providers as $name => $info) {
            $provider = $this->get_provider($name);
            
            if ($provider && $provider->is_available()) {
                $available[$name] = $provider->get_name();
            }
        }
        
        return $available;
    }
}