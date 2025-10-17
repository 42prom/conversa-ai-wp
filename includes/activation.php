<?php
/**
 * Fired during plugin activation.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes
 */

namespace ConversaAI_Pro_WP;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Activation {

    /**
     * Activate the plugin.
     *
     * Create necessary database tables and set up initial plugin options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
        
        // Set a transient to redirect to the settings page on first activation
        set_transient('conversaai_pro_activation_redirect', true, 30);
    }

    /**
     * Create the database tables required for the plugin.
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/db/class-schema.php';
        \ConversaAI_Pro_WP\DB\Schema::create_tables();
        
        add_option('conversaai_pro_db_version', CONVERSAAI_PRO_DB_VERSION);
    }

    /**
     * Set up the default options for the plugin.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // General settings
        $default_general = array(
            'enable_chat_widget' => true,
            'chat_title' => __('How can we help you?', 'conversaai-pro-wp'),
            'welcome_message' => __('Hello! I\'m your AI assistant. How can I help you today?', 'conversaai-pro-wp'),
            'placeholder_text' => __('Type your message...', 'conversaai-pro-wp'),
            'offline_message' => __('We\'re currently offline. Please leave a message and we\'ll get back to you.', 'conversaai-pro-wp'),
        );
        
        // AI settings
        $default_ai = array(
            'default_provider' => CONVERSAAI_PRO_DEFAULT_AI_PROVIDER,
            'default_model' => CONVERSAAI_PRO_DEFAULT_MODEL,
            'openai_api_key' => '',
            'confidence_threshold' => CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD,
            'temperature' => CONVERSAAI_PRO_DEFAULT_TEMPERATURE,
            'max_tokens' => CONVERSAAI_PRO_DEFAULT_MAX_TOKENS,
            'prioritize_local_kb' => true,
        );
        
        // Appearance settings
        $default_appearance = array(
            'primary_color' => '#4c66ef',
            'text_color' => '#333333',
            'bot_message_bg' => '#f0f4ff',
            'user_message_bg' => '#e1ebff',
            'font_family' => 'inherit',
            'border_radius' => '8px',
            'position' => 'right',
            'logo_url' => '',
            'bot_avatar' => '',
            'user_avatar' => '',
        );

        // Initialize channels settings with default structure
        $default_channels = array(
            'whatsapp' => array(
                'enabled' => false,
                'phone_number' => '',
                'api_key' => '',
                'business_account_id' => '',
                'webhook_secret' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on WhatsApp. How can we assist you today?',
            ),
            'messenger' => array(
                'enabled' => false,
                'page_id' => '',
                'app_id' => '',
                'app_secret' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Messenger. How can we assist you today?',
            ),
            'instagram' => array(
                'enabled' => false,
                'account_id' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Instagram. How can we assist you today?',
            )
        );

        
        
        // Add the options if they don't exist
        add_option('conversaai_pro_general_settings', $default_general);
        add_option('conversaai_pro_ai_settings', $default_ai);
        add_option('conversaai_pro_appearance_settings', $default_appearance);
        add_option('conversaai_pro_channels_settings', $default_channels);
    }
}