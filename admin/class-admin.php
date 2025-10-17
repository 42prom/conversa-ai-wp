<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Admin\Settings_Page;
use ConversaAI_Pro_WP\Admin\Training_Page;
use ConversaAI_Pro_WP\Admin\Analytics_Page;
use ConversaAI_Pro_WP\Admin\Dialogue_Manager_Page;
use ConversaAI_Pro_WP\Admin\KB_Admin_Page;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @since      1.0.0
 */
class Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Check for activation redirect
        add_action('admin_init', array($this, 'activation_redirect'));

    }

    /**
     * Redirect to the settings page on first activation.
     *
     * @since    1.0.0
     */
    public function activation_redirect() {
        if (get_transient('conversaai_pro_activation_redirect')) {
            delete_transient('conversaai_pro_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=conversaai-pro-settings'));
                exit;
            }
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Get current screen
        $screen = get_current_screen();
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        
        // Main admin CSS
        wp_enqueue_style($this->plugin_name, CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/css/admin.css', array(), $this->version, 'all');
        
        // Add channels-specific styles if on the channels page
        if ($page === 'conversaai-pro-channels') {
            wp_enqueue_style($this->plugin_name . '-channels', CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/css/channels.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Get current screen
        $screen = get_current_screen();
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        
        // Main admin JS
        wp_enqueue_script($this->plugin_name, CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), $this->version, false);
        
        // Add the AJAX save handler
        wp_enqueue_script($this->plugin_name . '-save-handler', CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/js/admin-save-handler.js', array('jquery'), $this->version, false);
        
        // Add localization for the AJAX handler
        wp_localize_script($this->plugin_name . '-save-handler', 'conversaai_pro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('conversaai_pro_settings_nonce'),
            'saving_text' => __('Saving...', 'conversaai-pro-wp'),
            'saved_text' => __('Settings Saved', 'conversaai-pro-wp'),
            'error_text' => __('Error Saving Settings', 'conversaai-pro-wp'),
        ));
        
        // channels page screepts
        if ($page === 'conversaai-pro-channels') {
            wp_enqueue_script($this->plugin_name . '-channels', CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/js/channels.js', array('jquery'), $this->version, false);
            
            // Add localization for the channels script
            wp_localize_script($this->plugin_name . '-channels', 'conversaai_channels', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('conversaai_channels_nonce'),
                'copy_success' => __('URL copied to clipboard!', 'conversaai-pro-wp'),
                'copy_error' => __('Failed to copy URL. Please try manually.', 'conversaai-pro-wp'),
                'toggle_error' => __('Error toggling channel.', 'conversaai-pro-wp'),
                'ajax_error' => __('Connection error. Please try again.', 'conversaai-pro-wp'),
                'test_error' => __('Connection test failed.', 'conversaai-pro-wp'),
                'save_error' => __('Error saving settings.', 'conversaai-pro-wp'),
                'save_success' => __('Settings saved successfully.', 'conversaai-pro-wp'),
                'stats_error' => __('Error fetching channel statistics.', 'conversaai-pro-wp'),
                'stats_updated' => __('Channel statistics updated.', 'conversaai-pro-wp'),
                'active_text' => __('Active', 'conversaai-pro-wp'),
                'inactive_text' => __('Inactive', 'conversaai-pro-wp'),
            ));
            
            // Debug log for channel page script loading
            if (WP_DEBUG) {
                error_log('Channels scripts and localization loaded');
            }
        }
    }

    /**
     * Add the admin menu items.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('ConversaAI Pro', 'conversaai-pro-wp'),
            __('ConversaAI Pro', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro',
            array($this, 'display_dashboard_page'),
            'dashicons-format-chat',
            30
        );
        
        // Dashboard submenu (to match the main menu item)
        add_submenu_page(
            'conversaai-pro',
            __('Dashboard', 'conversaai-pro-wp'),
            __('Dashboard', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro',
            array($this, 'display_dashboard_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'conversaai-pro',
            __('Settings', 'conversaai-pro-wp'),
            __('Settings', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-settings',
            array($this, 'display_settings_page')
        );

        // Knowledge Sources submenu
        add_submenu_page(
            'conversaai-pro',
            __('Knowledge Sources', 'conversaai-pro-wp'),
            __('Knowledge Sources', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-knowledge-sources',
            array($this, 'display_knowledge_sources_page')
        );
        
        // Knowledge Base submenu
        add_submenu_page(
            'conversaai-pro',
            __('Knowledge Base', 'conversaai-pro-wp'),
            __('Knowledge Base', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-knowledge-base',
            array($this, 'display_knowledge_base_page')
        );

        // Dialogue Manager submenu
        add_submenu_page(
            'conversaai-pro',
            __('Dialogue Manager', 'conversaai-pro-wp'),
            __('Dialogue Manager', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-dialogue-manager',
            array($this, 'display_dialogue_manager_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'conversaai-pro',
            __('Analytics', 'conversaai-pro-wp'),
            __('Analytics', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-analytics',
            array($this, 'display_analytics_page')
        );
        
        // Channels submenu (for WhatsApp, Messenger, Instagram integration)
        add_submenu_page(
            'conversaai-pro',
            __('Channels', 'conversaai-pro-wp'),
            __('Channels', 'conversaai-pro-wp'),
            'manage_options',
            'conversaai-pro-channels',
            array($this, 'display_channels_page')
        );
    }
    
    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        // For now, just include a basic dashboard template
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }
    
    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Initialize settings page class and display
        $settings_page = new Settings_Page($this->plugin_name, $this->version);
        $settings_page->display();
    }

    /**
     * Display the knowledge sources page.
     *
     * @since    1.0.0
     */
    public function display_knowledge_sources_page() {
        // Initialize knowledge sources page class and display
        $knowledge_sources_page = new Knowledge_Sources_Page($this->plugin_name, $this->version);
        $knowledge_sources_page->display();
    }
    
    /**
     * Display the knowledge base page.
     *
     * @since    1.0.0
     */
    public function display_knowledge_base_page() {
        // Initialize KB admin page class and display
        $kb_admin_page = new KB_Admin_Page($this->plugin_name, $this->version);
        $kb_admin_page->display();
    }
    
    /**
     * Display the analytics page.
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        // Initialize analytics page class and display
        $analytics_page = new Analytics_Page($this->plugin_name, $this->version);
        $analytics_page->display();
    }

    /**
     * Display the dialogue manager page.
     *
     * @since    1.0.0
     */
    public function display_dialogue_manager_page() {
        // Initialize dialogue manager page class and display
        $dialogue_manager_page = new Dialogue_Manager_Page($this->plugin_name, $this->version);
        $dialogue_manager_page->display();
    }

    /**
     * Display the channels page.
     *
     * @since    1.0.0
     */
    public function display_channels_page() {
        // Initialize messaging interface if needed
        if (!interface_exists('\ConversaAI_Pro_WP\Integrations\Messaging\Messaging_Channel')) {
            require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/interface-messaging-channel.php';
        }
        
        // Ensure Channel Manager is loaded
        if (!class_exists('\ConversaAI_Pro_WP\Integrations\Messaging\Channel_Manager')) {
            require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-channel-manager.php';
        }
        
        // Log debug info
        if (WP_DEBUG) {
            error_log('Initializing channels page');
        }
        
        // Initialize channels page class and display
        $channels_page = new Channels_Page($this->plugin_name, $this->version);
        $channels_page->display();
    }
}