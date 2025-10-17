<?php
/**
 * The core plugin class.
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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks. It also serves as the main orchestrator for the plugin.
 *
 * @since      1.0.0
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @sincesthe 1.0.0
     * @access   protected
     * @var      \ConversaAI_Pro_WP\Loader    $loader   Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    public function __construct() {
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/class-loader.php';
        $this->loader = new \ConversaAI_Pro_WP\Loader();
        
        if (defined('CONVERSAAI_PRO_VERSION')) {
            $this->version = CONVERSAAI_PRO_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'conversaai-pro-wp';

        // Check if database tables exist
        add_action('admin_init', array($this, 'check_database_tables'));
    }

    /**
     * Initialize the plugin and define the core functionality.
     *
     * @since    1.0.0
     */
    public function initialize() {
        if (defined('CONVERSAAI_PRO_VERSION')) {
            $this->version = CONVERSAAI_PRO_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'conversaai-pro-wp';
    
        $this->load_dependencies();
        
        // Add new settings for WooCommerce product display
        add_action('admin_init', function() {
            register_setting('conversaai_pro_indexing_settings', 'conversaai_pro_woocommerce_display', array(
                'type' => 'array',
                'default' => array(
                    'product_intro' => '"%s" is a WooCommerce product.',
                    'product_price' => 'Priced at %s.',
                    'product_link' => 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.',
                    'link_color' => '#4c66ef',
                    'product_question' => 'What is the product "%s"?',
                    'product_detail_question' => 'Can you describe the product "%s" in detail?',
                    'show_categories' => true,
                    'categories_format' => 'Product categories: %s.'
                )
            ));
        });
        
        // Add new setting for link color in responses
        add_action('admin_init', function() {
            register_setting('conversaai_pro_appearance_settings', 'conversaai_pro_response_link_color', array(
                'type' => 'string',
                'default' => '#4c66ef',
                'sanitize_callback' => 'sanitize_hex_color',
            ));
        });
        
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_core_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - \ConversaAI_Pro_WP\Loader. Orchestrates the hooks of the plugin.
     * - \ConversaAI_Pro_WP\Admin. Defines all hooks for the admin area.
     * - \ConversaAI_Pro_WP\Public. Defines all hooks for the public side of the site.
     * - All core functionality classes that power the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Admin classes
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-admin.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-settings-page.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-settings-handler.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-analytics-page.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-dialogue-manager-page.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-kb-admin-page.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-channels-page.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-learning-engine.php';
    
        // Public classes
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'public/class-public.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'public/class-chat-widget.php';
    
        // Core functionality
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-conversation-manager.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-router.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-knowledge-base.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-analytics-manager.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-trigger-word-processor.php';
        
        // Database classes
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/db/class-schema.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/constants.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/db/class-kb-import-export.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-knowledge-sources-page.php';
        
        // AI Integrations
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/interface-ai-provider.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-ai-factory.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/ai/class-openai-provider.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/wp/class-woocommerce-indexer.php';
        
        // Messaging Channels
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/interface-messaging-channel.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/abstract-messaging-channel.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-channel-manager.php';
        
        // Utilities
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/utils/class-api-request.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/utils/class-logger.php';
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/utils/class-webhook-debug.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new \ConversaAI_Pro_WP\Admin\Admin($this->get_plugin_name(), $this->get_version());
        $settings_handler = new \ConversaAI_Pro_WP\Admin\Settings_Handler($this->get_plugin_name());
        $kb_admin = new \ConversaAI_Pro_WP\Admin\KB_Admin_Page($this->get_plugin_name(), $this->get_version());
        $knowledge_sources_admin = new \ConversaAI_Pro_WP\Admin\Knowledge_Sources_Page($this->get_plugin_name(), $this->get_version());

    
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        
        $this->loader->add_action('admin_init', $settings_handler, 'register_settings');
        $this->loader->add_action('wp_ajax_conversaai_save_settings', $settings_handler, 'ajax_save_settings');
        
        // Knowledge Base hooks
        $this->loader->add_action('wp_ajax_conversaai_get_kb_entries', $kb_admin, 'ajax_get_kb_entries');
        $this->loader->add_action('wp_ajax_conversaai_save_kb_entry', $kb_admin, 'ajax_save_kb_entry');
        $this->loader->add_action('wp_ajax_conversaai_get_kb_entry', $kb_admin, 'ajax_get_kb_entry');
        $this->loader->add_action('wp_ajax_conversaai_delete_kb_entry', $kb_admin, 'ajax_delete_kb_entry');
        $this->loader->add_action('wp_ajax_conversaai_import_kb', $kb_admin, 'ajax_import_kb');
        $this->loader->add_action('wp_ajax_conversaai_export_kb', $kb_admin, 'ajax_export_kb');
        $this->loader->add_action('wp_ajax_conversaai_generate_answer', $kb_admin, 'ajax_generate_answer');
        $this->loader->add_action('wp_ajax_conversaai_bulk_kb_action', $kb_admin, 'ajax_bulk_kb_action');
        
        // Knowledge Sources hooks
        $this->loader->add_action('wp_ajax_conversaai_index_content', $knowledge_sources_admin, 'ajax_index_content');
        $this->loader->add_action('wp_ajax_conversaai_index_products', $knowledge_sources_admin, 'ajax_index_products');
        $this->loader->add_action('wp_ajax_conversaai_save_indexing_settings', $knowledge_sources_admin, 'ajax_save_indexing_settings');
        $this->loader->add_action('wp_ajax_conversaai_get_indexing_stats', $knowledge_sources_admin, 'ajax_get_indexing_stats');

        // Dialogue Manager hooks
        $dialogue_manager = new \ConversaAI_Pro_WP\Admin\Dialogue_Manager_Page($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_ajax_conversaai_get_dialogues', $dialogue_manager, 'ajax_get_dialogues');
        $this->loader->add_action('wp_ajax_conversaai_get_dialogue_details', $dialogue_manager, 'ajax_get_dialogue_details');
        $this->loader->add_action('wp_ajax_conversaai_bulk_action_dialogues', $dialogue_manager, 'ajax_bulk_action_dialogues');

        // Channel Page hooks for AJAX
        $this->loader->add_action('admin_init', $this, 'register_channel_ajax_handlers');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new \ConversaAI_Pro_WP\Public_Site\PublicSite($this->get_plugin_name(), $this->get_version());
        $chat_widget = new \ConversaAI_Pro_WP\Public_Site\Chat_Widget($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_footer', $chat_widget, 'render_chat_widget');
        
        // AJAX handlers for the chat
        $this->loader->add_action('wp_ajax_conversaai_send_message', $chat_widget, 'process_message');
        $this->loader->add_action('wp_ajax_nopriv_conversaai_send_message', $chat_widget, 'process_message');
    }

    /**
     * Register core functionality hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_core_hooks() {
        // Initialize core functionality classes here
        // For now, we'll just ensure they're loaded but not yet used
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    \ConversaAI_Pro_WP\Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Check if required database tables exist, create if not.
     */
    public function check_database_tables() {
        global $wpdb;
        
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/db/class-schema.php';
        $table_recreated = \ConversaAI_Pro_WP\DB\Schema::verify_and_fix_tables();
        
        if ($table_recreated) {
            error_log('ConversaAI Pro: Database tables have been recreated');
        } else {
            // Check if tables exist but need fixing
            $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$table_exists) {
                \ConversaAI_Pro_WP\DB\Schema::create_tables();
                error_log('ConversaAI Pro: Created missing database tables');
            }
        }
    }

    /**
     * Register AJAX handlers for the messaging channels functionality.
     *
     * @since    1.0.0
     */
    public function register_channel_ajax_handlers() {
        static $handlers_registered = false;
    
        if ($handlers_registered) {
            return;
        }
    
        // Create an instance of Channels_Page just for AJAX
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/class-channels-page.php';
        $channels_page = new \ConversaAI_Pro_WP\Admin\Channels_Page($this->get_plugin_name(), $this->get_version());
        
        // Register the hooks directly
        add_action('wp_ajax_conversaai_save_channel_settings', array($channels_page, 'ajax_save_channel_settings'));
        add_action('wp_ajax_conversaai_test_channel_connection', array($channels_page, 'ajax_test_channel_connection'));
        add_action('wp_ajax_conversaai_toggle_channel', array($channels_page, 'ajax_toggle_channel'));
        add_action('wp_ajax_conversaai_get_channel_stats', array($channels_page, 'ajax_get_channel_stats'));
        
        // Make sure the option exists with default values
        if (false === get_option('conversaai_pro_channels_settings')) {
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
            add_option('conversaai_pro_channels_settings', $default_channels);
            
            if (WP_DEBUG && defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
                error_log('ConversaAI Pro: Created channels settings with defaults');
            }
        }
        
        $handlers_registered = true;
        
        // Only log in debug mode
        if (WP_DEBUG && defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
            error_log('ConversaAI Pro: Registered channel AJAX handlers');
        }
    }
}