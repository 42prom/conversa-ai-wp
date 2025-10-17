<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/public
 */

namespace ConversaAI_Pro_WP\Public_Site;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the hooks for the public side of the site.
 *
 * @since      1.0.0
 */
class PublicSite {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Only enqueue if the widget is enabled
        $general_settings = get_option('conversaai_pro_general_settings', array());
        if (!isset($general_settings['enable_chat_widget']) || !$general_settings['enable_chat_widget']) {
            return;
        }
        
        wp_enqueue_style($this->plugin_name, CONVERSAAI_PRO_PLUGIN_URL . 'public/assets/css/widget.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue if the widget is enabled
        $general_settings = get_option('conversaai_pro_general_settings', array());
        if (!isset($general_settings['enable_chat_widget']) || !$general_settings['enable_chat_widget']) {
            return;
        }
        
        wp_enqueue_script($this->plugin_name, CONVERSAAI_PRO_PLUGIN_URL . 'public/assets/js/widget.js', array('jquery'), $this->version, true);
        
        // Add localization for the script
        wp_localize_script($this->plugin_name, 'conversaai_pro', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('conversaai_pro_chat_nonce'),
            'placeholder_text' => isset($general_settings['placeholder_text']) ? $general_settings['placeholder_text'] : __('Type your message...', 'conversaai-pro-wp'),
            'sending_text' => __('Sending...', 'conversaai-pro-wp'),
            'connecting_text' => __('Connecting...', 'conversaai-pro-wp'),
            'chat_title' => isset($general_settings['chat_title']) ? $general_settings['chat_title'] : __('How can we help you?', 'conversaai-pro-wp'),
            'reset_text' => __('Conversation has been reset.', 'conversaai-pro-wp'),
            'welcome_message' => isset($general_settings['welcome_message']) ? $general_settings['welcome_message'] : __('Hello! I\'m your AI assistant. How can I help you today?', 'conversaai-pro-wp'),
        ));
    }
}