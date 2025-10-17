<?php
/**
 * Settings page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Load current settings
$general_settings = get_option('conversaai_pro_general_settings', array());
$ai_settings = get_option('conversaai_pro_ai_settings', array());
$appearance_settings = get_option('conversaai_pro_appearance_settings', array());
?>

<div class="wrap conversaai-pro-settings">
    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('ConversaAI Settings', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Configure your AI assistant\'s behavior, appearance, and integration settings. Customize how ConversaAI works with your website.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-admin-generic"></span>
        </div>
    </div>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=conversaai-pro-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'conversaai-pro-wp'); ?></a>
        <a href="?page=conversaai-pro-settings&tab=ai" class="nav-tab <?php echo $active_tab == 'ai' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Providers', 'conversaai-pro-wp'); ?></a>
        <a href="?page=conversaai-pro-settings&tab=prompts" class="nav-tab <?php echo $active_tab == 'prompts' ? 'nav-tab-active' : ''; ?>"><?php _e('Prompts', 'conversaai-pro-wp'); ?></a>
        <a href="?page=conversaai-pro-settings&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>"><?php _e('Appearance', 'conversaai-pro-wp'); ?></a>
    </h2>
    
    <div class="conversaai-pro-settings-content">
        <?php if ($active_tab == 'general'): ?>
            <!-- General Settings Tab -->
            <?php require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/settings-general.php'; ?>
            
        <?php elseif ($active_tab == 'ai'): ?>
            <!-- AI Providers Tab -->
            <?php require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/settings-ai.php'; ?>
        
        <?php elseif ($active_tab == 'prompts'): ?>
            <!-- Prompts Tab -->
            <?php require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/settings-prompts.php'; ?>    
            
        <?php elseif ($active_tab == 'appearance'): ?>
            <!-- Appearance Tab -->
            <?php require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/settings-appearance.php'; ?>
            
        <?php endif; ?>
    </div>
    
    <div class="conversaai-pro-settings-footer">
        <div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible" style="display:none;">
            <p><?php _e('Settings saved successfully.', 'conversaai-pro-wp'); ?></p>
        </div>
        
        <div id="setting-error-settings_error" class="notice notice-error settings-error is-dismissible" style="display:none;">
            <p><?php _e('Error saving settings. Please try again.', 'conversaai-pro-wp'); ?></p>
        </div>
    </div>
</div>