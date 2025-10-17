<?php
/**
 * Initialize the plugin.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes
 */

namespace ConversaAI_Pro_WP;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Prevent multiple initialization
if (defined('CONVERSAAI_PRO_INITIALIZED')) {
    if (WP_DEBUG) {
        error_log('ConversaAI Pro: Attempted re-initialization blocked. Call stack: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true));
    }
    return;
}
define('CONVERSAAI_PRO_INITIALIZED', true);

/**
 * Initialize the plugin.
 *
 * This function is run during plugin startup and initializes
 * the main plugin functionality.
 */
function initialize_plugin() {
    // Only log detailed initialization in development with CONVERSAAI_DEBUG_LEVEL
    $debug_level = defined('CONVERSAAI_DEBUG_LEVEL') ? CONVERSAAI_DEBUG_LEVEL : 'error';
    
    // Only log detailed initialization for 'debug' level
    if (WP_DEBUG && $debug_level === 'debug') {
        error_log('ConversaAI Pro: Initializing plugin. Call stack: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true));
        error_log('ConversaAI Pro: Current action: ' . current_action());
        error_log('ConversaAI Pro: Is admin: ' . (is_admin() ? 'yes' : 'no'));
        // Add request identifier to track separate requests
        error_log('ConversaAI Pro: Request ID: ' . uniqid('req_'));
    }

    // Start the plugin
    $plugin = new Plugin();
    $plugin->initialize(); // Call initialize() first
    $plugin->run();

    // Initialize content indexer
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/wp/class-content-indexer.php';
    $content_indexer = new \ConversaAI_Pro_WP\Integrations\WP\Content_Indexer();

    // Initialize WooCommerce indexer if WooCommerce is active
    if (class_exists('WooCommerce')) {
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/wp/class-woocommerce-indexer.php';
        $woocommerce_indexer = new \ConversaAI_Pro_WP\Integrations\WP\WooCommerce_Indexer();
    }
    
    // Always initialize messaging channels manager
    // This ensures the admin UI works even if the channels are disabled
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/interface-messaging-channel.php';
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-channel-manager.php';
    $channel_manager = \ConversaAI_Pro_WP\Integrations\Messaging\Channel_Manager::get_instance();
    
    // Log initialization status only at debug level
    if (WP_DEBUG && defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
        error_log('ConversaAI Pro plugin initialized successfully. Request ID: ' . uniqid('req_'));
    } elseif (WP_DEBUG && !defined('CONVERSAAI_DEBUG_LEVEL')) {
        // If debug level is not defined, log only once per session using a transient
        $logged_init = get_transient('conversaai_pro_init_logged');
        if (!$logged_init) {
            error_log('ConversaAI Pro plugin initialized successfully.');
            set_transient('conversaai_pro_init_logged', true, 60); // Set for 1 minute
        }
    }

    return $plugin;
}

// Run the initialization only if not already initialized
if (!isset($GLOBALS['conversaai_pro'])) {
    $GLOBALS['conversaai_pro'] = initialize_plugin();
} else {
    if (WP_DEBUG) {
        error_log('ConversaAI Pro: Plugin already initialized in GLOBALS. Skipping re-initialization.');
    }
}