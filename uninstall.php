<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ConversaAI_Pro_WP
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define the table names directly to ensure they're available
// This avoids potential issues with missing constants
define('CONVERSAAI_PRO_CONVERSATIONS_TABLE', 'conversaai_pro_conversations');
define('CONVERSAAI_PRO_KNOWLEDGE_TABLE', 'conversaai_pro_knowledge');
define('CONVERSAAI_PRO_ANALYTICS_TABLE', 'conversaai_pro_analytics');

/**
 * Perform a complete uninstallation of the plugin
 */
function conversaai_pro_uninstall() {
    global $wpdb;
    
    // Delete database tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_CONVERSATIONS_TABLE);
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_KNOWLEDGE_TABLE);
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . CONVERSAAI_PRO_ANALYTICS_TABLE);
    
    // Delete options
    delete_option('conversaai_pro_db_version');
    delete_option('conversaai_pro_general_settings');
    delete_option('conversaai_pro_ai_settings');
    delete_option('conversaai_pro_appearance_settings');
    delete_option('conversaai_pro_delete_data_on_uninstall');
    delete_option('conversaai_pro_prompts');
    delete_option('conversaai_pro_indexing_settings');
    delete_option('conversaai_pro_last_content_index');
    delete_option('conversaai_pro_last_product_index');
    
    // Delete transients
    delete_transient('conversaai_pro_activation_redirect');
    
    // Remove post meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_conversaai_%'");
    
    // Log successful uninstallation
    if (WP_DEBUG) {
        error_log('ConversaAI Pro has been completely uninstalled.');
    }
}

// Run the uninstallation
conversaai_pro_uninstall();