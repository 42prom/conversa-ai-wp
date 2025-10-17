<?php
/**
 * Fired during plugin uninstallation.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes
 */

namespace ConversaAI_Pro_WP;

// If this file is called directly, abort.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Fired during plugin uninstallation.
 *
 * This class defines all code necessary to run during the plugin's uninstallation.
 *
 * @since      1.0.0
 */
class Uninstaller {

    /**
     * Uninstall the plugin.
     *
     * Remove all data created by the plugin.
     *
     * @since    1.0.0
     */
    public static function uninstall() {
        // Always delete all data on uninstall
        self::delete_options();
        self::delete_tables();
        
        // Clear any remaining transients
        delete_transient('conversaai_pro_activation_redirect');
    }

    /**
     * Delete all options created by the plugin.
     *
     * @since    1.0.0
     */
    private static function delete_options() {
        delete_option('conversaai_pro_db_version');
        delete_option('conversaai_pro_general_settings');
        delete_option('conversaai_pro_ai_settings');
        delete_option('conversaai_pro_appearance_settings');
        delete_option('conversaai_pro_delete_data_on_uninstall');
        delete_option('conversaai_pro_prompts');
        delete_option('conversaai_pro_indexing_settings');
        delete_option('conversaai_pro_last_content_index');
        delete_option('conversaai_pro_last_product_index');
    }

    /**
     * Delete all database tables created by the plugin.
     *
     * @since    1.0.0
     */
    private static function delete_tables() {
        global $wpdb;
        
        // Drop the tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_CONVERSATIONS_TABLE);
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_KNOWLEDGE_TABLE);
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_ANALYTICS_TABLE);
    }
}