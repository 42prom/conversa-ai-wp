<?php
/**
 * Fired during plugin deactivation.
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 */
class Deactivation {

    /**
     * Deactivate the plugin.
     *
     * Perform necessary cleanup when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('conversaai_pro_daily_maintenance');
        
        // Clear any transients
        delete_transient('conversaai_pro_activation_redirect');
        
        // Log deactivation
        error_log('ConversaAI Pro for WP deactivated');
    }
}