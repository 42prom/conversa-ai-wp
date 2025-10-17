<?php
/**
 * Dashboard page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get analytics data for dashboard
global $wpdb;
$table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
$table_kb = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
$table_conv = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;

// Get counts for various metrics
$total_conversations = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_conv"));
$total_kb_entries = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_kb"));
$kb_approved = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_kb WHERE approved = 1"));

// Get today's conversations count
$today = date('Y-m-d');
$todays_conversations = intval($wpdb->get_var(
    $wpdb->prepare("SELECT conversation_count FROM $table_name WHERE date = %s", $today)
)) ?: 0;

// Get general plugin status
$general_settings = get_option('conversaai_pro_general_settings', array());
$ai_settings = get_option('conversaai_pro_ai_settings', array());
$widget_enabled = isset($general_settings['enable_chat_widget']) ? $general_settings['enable_chat_widget'] : false;

// Check if any API provider is configured
$has_api_key = !empty($ai_settings['openai_api_key']) || 
               !empty($ai_settings['deepseek_api_key']) || 
               !empty($ai_settings['openrouter_api_key']);

// Get the active provider name
$active_provider = isset($ai_settings['default_provider']) ? $ai_settings['default_provider'] : 'openai';
$active_provider_key = $active_provider . '_api_key';
$active_provider_configured = !empty($ai_settings[$active_provider_key]);
?>

<div class="wrap conversaai-pro-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Welcome Banner -->
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Welcome to ConversaAI Pro', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Empower your website with AI-driven conversations. Configure your settings, train your AI assistant, and start providing exceptional customer experiences.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-admin-comments"></span>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="conversaai-stats-grid">
        <div class="conversaai-stat-card conversaai-stat-blue">
            <div class="conversaai-stat-icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="conversaai-stat-content">
                <h3><?php echo number_format($total_conversations); ?></h3>
                <p><?php _e('Total Conversations', 'conversaai-pro-wp'); ?></p>
            </div>
        </div>
        
        <div class="conversaai-stat-card conversaai-stat-green">
            <div class="conversaai-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="conversaai-stat-content">
                <h3><?php echo number_format($todays_conversations); ?></h3>
                <p><?php _e('Today\'s Conversations', 'conversaai-pro-wp'); ?></p>
            </div>
        </div>
        
        <div class="conversaai-stat-card conversaai-stat-purple">
            <div class="conversaai-stat-icon">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="conversaai-stat-content">
                <h3><?php echo number_format($total_kb_entries); ?></h3>
                <p><?php _e('Knowledge Base Entries', 'conversaai-pro-wp'); ?></p>
            </div>
        </div>
        
        <div class="conversaai-stat-card conversaai-stat-orange">
            <div class="conversaai-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="conversaai-stat-content">
                <h3><?php echo number_format($kb_approved); ?></h3>
                <p><?php _e('Approved KB Entries', 'conversaai-pro-wp'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Main Features Grid -->
    <div class="conversaai-features-grid">
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-admin-generic"></span>
                <h2><?php _e('Settings', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('Configure your AI provider, appearance, and general settings for the chat widget.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-status-indicator">
                    <?php if ($has_api_key): ?>
                        <span class="conversaai-status-dot active"></span>
                        <span class="conversaai-status-text">
                            <?php 
                            if ($active_provider_configured) {
                                echo sprintf(__('%s API Configured', 'conversaai-pro-wp'), ucfirst($active_provider));
                            } else {
                                echo sprintf(__('%s API Needed', 'conversaai-pro-wp'), ucfirst($active_provider));
                            }
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="conversaai-status-dot inactive"></span>
                        <span class="conversaai-status-text"><?php _e('API Not Configured', 'conversaai-pro-wp'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-settings'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('Configure Settings', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
        
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-book"></span>
                <h2><?php _e('Knowledge Base', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('Manage your proprietary knowledge base to provide accurate answers to customer inquiries.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-status-indicator">
                    <span class="conversaai-status-dot <?php echo $total_kb_entries > 0 ? 'active' : 'inactive'; ?>"></span>
                    <span class="conversaai-status-text">
                        <?php echo $total_kb_entries > 0 
                            ? sprintf(_n('%s Entry', '%s Entries', $total_kb_entries, 'conversaai-pro-wp'), number_format($total_kb_entries))
                            : __('No Entries', 'conversaai-pro-wp'); ?>
                    </span>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-knowledge-base'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('Manage Knowledge', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
        
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-admin-comments"></span>
                <h2><?php _e('Dialogue Manager', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('Review and analyze conversations between your AI assistant and users to improve performance.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-status-indicator">
                    <span class="conversaai-status-dot <?php echo $total_conversations > 0 ? 'active' : 'inactive'; ?>"></span>
                    <span class="conversaai-status-text">
                        <?php echo $total_conversations > 0 
                            ? sprintf(_n('%s Conversation', '%s Conversations', $total_conversations, 'conversaai-pro-wp'), number_format($total_conversations))
                            : __('No Conversations', 'conversaai-pro-wp'); ?>
                    </span>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-dialogue-manager'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('Manage Dialogues', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
        
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-chart-bar"></span>
                <h2><?php _e('Analytics', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('View insights and statistics about customer interactions with your AI assistant.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-mini-chart">
                    <div class="conversaai-chart-bar" style="height: 15px; width: 20%;"></div>
                    <div class="conversaai-chart-bar" style="height: 25px; width: 20%;"></div>
                    <div class="conversaai-chart-bar" style="height: 40px; width: 20%;"></div>
                    <div class="conversaai-chart-bar" style="height: 30px; width: 20%;"></div>
                    <div class="conversaai-chart-bar" style="height: 35px; width: 20%;"></div>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-analytics'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('View Analytics', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
        
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-share"></span>
                <h2><?php _e('Channels', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('Connect with customers across multiple platforms including WhatsApp, Facebook Messenger, and Instagram.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-channel-icons">
                    <span class="conversaai-channel-icon" title="Website"><span class="dashicons dashicons-admin-site"></span></span>
                    <span class="conversaai-channel-icon inactive" title="WhatsApp"><span class="dashicons dashicons-smartphone"></span></span>
                    <span class="conversaai-channel-icon inactive" title="Facebook"><span class="dashicons dashicons-facebook"></span></span>
                    <span class="conversaai-channel-icon inactive" title="Instagram"><span class="dashicons dashicons-instagram"></span></span>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-channels'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('Manage Channels', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
        
        <div class="conversaai-card conversaai-feature-card">
            <div class="conversaai-card-header">
                <span class="dashicons dashicons-database"></span>
                <h2><?php _e('Knowledge Sources', 'conversaai-pro-wp'); ?></h2>
            </div>
            <div class="conversaai-card-content">
                <p><?php _e('Configure content sources from WordPress and WooCommerce to enhance your AI\'s knowledge base.', 'conversaai-pro-wp'); ?></p>
                <div class="conversaai-source-indicators">
                    <?php 
                    $has_wp_content = $wpdb->get_var("SELECT COUNT(*) FROM $table_kb WHERE metadata LIKE '%wp_content%'") > 0;
                    $has_woo_products = $wpdb->get_var("SELECT COUNT(*) FROM $table_kb WHERE metadata LIKE '%woocommerce_product%'") > 0;
                    ?>
                    <div class="conversaai-source-indicator <?php echo $has_wp_content ? 'active' : 'inactive'; ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <span><?php _e('WordPress Content', 'conversaai-pro-wp'); ?></span>
                    </div>
                    <div class="conversaai-source-indicator <?php echo $has_woo_products ? 'active' : 'inactive'; ?>">
                        <span class="dashicons dashicons-cart"></span>
                        <span><?php _e('WooCommerce Products', 'conversaai-pro-wp'); ?></span>
                    </div>
                </div>
            </div>
            <div class="conversaai-card-actions">
                <a href="<?php echo admin_url('admin.php?page=conversaai-pro-knowledge-sources'); ?>" class="button button-primary conversaai-card-button">
                    <?php _e('Configure Sources', 'conversaai-pro-wp'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- System Status Section -->
    <div class="conversaai-system-status-section">
        <h2><?php _e('System Status', 'conversaai-pro-wp'); ?></h2>
        
        <div class="conversaai-card conversaai-status-card">
            <table class="widefat conversaai-status-table">
                <thead>
                    <tr>
                        <th><?php _e('Component', 'conversaai-pro-wp'); ?></th>
                        <th><?php _e('Status', 'conversaai-pro-wp'); ?></th>
                        <th><?php _e('Details', 'conversaai-pro-wp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Chat Widget', 'conversaai-pro-wp'); ?></strong></td>
                        <td>
                            <?php if ($widget_enabled): ?>
                                <span class="conversaai-pro-status-active"><?php _e('Active', 'conversaai-pro-wp'); ?></span>
                            <?php else: ?>
                                <span class="conversaai-pro-status-inactive"><?php _e('Inactive', 'conversaai-pro-wp'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($widget_enabled) {
                                _e('The chat widget is active on your website.', 'conversaai-pro-wp');
                            } else {
                                _e('The chat widget is currently disabled. Enable it in the settings.', 'conversaai-pro-wp');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('AI Provider', 'conversaai-pro-wp'); ?></strong></td>
                        <td>
                            <?php if ($has_api_key): ?>
                                <?php if ($active_provider_configured): ?>
                                    <span class="conversaai-pro-status-active"><?php _e('Configured', 'conversaai-pro-wp'); ?></span>
                                <?php else: ?>
                                    <span class="conversaai-pro-status-warning"><?php _e('Partially Configured', 'conversaai-pro-wp'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="conversaai-pro-status-warning"><?php _e('Not Configured', 'conversaai-pro-wp'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($has_api_key) {
                                $provider = isset($ai_settings['default_provider']) ? $ai_settings['default_provider'] : 'openai';
                                
                                // Get the appropriate model name based on provider
                                if ($provider === 'openai') {
                                    $model = isset($ai_settings['default_model']) ? $ai_settings['default_model'] : 'gpt-3.5-turbo';
                                } elseif ($provider === 'deepseek') {
                                    $model = isset($ai_settings['deepseek_model']) ? $ai_settings['deepseek_model'] : 'deepseek-chat';
                                } elseif ($provider === 'openrouter') {
                                    $model = isset($ai_settings['openrouter_model']) ? $ai_settings['openrouter_model'] : 'openai/gpt-3.5-turbo';
                                } else {
                                    $model = 'unknown model';
                                }
                                
                                if ($active_provider_configured) {
                                    printf(__('Using %s with model %s.', 'conversaai-pro-wp'), ucfirst($provider), $model);
                                } else {
                                    printf(__('%s is selected but not configured. Please add API key in settings.', 'conversaai-pro-wp'), ucfirst($provider));
                                }
                            } else {
                                _e('No API keys configured. Please set up an AI provider in the settings.', 'conversaai-pro-wp');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Knowledge Base', 'conversaai-pro-wp'); ?></strong></td>
                        <td>
                            <?php if ($total_kb_entries > 0): ?>
                                <span class="conversaai-pro-status-active"><?php _e('Active', 'conversaai-pro-wp'); ?></span>
                            <?php else: ?>
                                <span class="conversaai-pro-status-inactive"><?php _e('Empty', 'conversaai-pro-wp'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($total_kb_entries > 0) {
                                printf(_n('%d entry in your knowledge base.', '%d entries in your knowledge base.', $total_kb_entries, 'conversaai-pro-wp'), $total_kb_entries);
                            } else {
                                _e('Your knowledge base is empty. Start adding entries or training your assistant.', 'conversaai-pro-wp');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Database Tables', 'conversaai-pro-wp'); ?></strong></td>
                        <td>
                            <?php 
                            $tables_ok = true;
                            $check_tables = array(
                                $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE,
                                $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE,
                                $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE
                            );
                            
                            foreach ($check_tables as $table) {
                                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                                    $tables_ok = false;
                                    break;
                                }
                            }
                            
                            if ($tables_ok):
                            ?>
                                <span class="conversaai-pro-status-active"><?php _e('Healthy', 'conversaai-pro-wp'); ?></span>
                            <?php else: ?>
                                <span class="conversaai-pro-status-error"><?php _e('Issue Detected', 'conversaai-pro-wp'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($tables_ok) {
                                _e('All required database tables are present and properly structured.', 'conversaai-pro-wp');
                            } else {
                                _e('Some database tables are missing. Please deactivate and reactivate the plugin.', 'conversaai-pro-wp');
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>