<?php
/**
 * Analytics page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-analytics">
    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Analytics Dashboard', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('View insights and statistics about customer interactions with your AI assistant. Track conversation performance and identify trends.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-chart-bar"></span>
        </div>
    </div>
    
    <!-- Form for page submission -->
    <form method="get" action="" id="conversaai-analytics-form">
        <input type="hidden" name="page" value="conversaai-pro-analytics">
        <!-- Hidden fields for form submission -->
    </form>
    
    <!-- Filter Controls -->
    <div class="conversaai-analytics-controls">
        <div class="conversaai-analytics-filters">
            <div class="conversaai-date-filter-container">
                <div class="conversaai-date-presets">
                    <label><?php _e('Quick Select:', 'conversaai-pro-wp'); ?></label>
                    <div class="conversaai-date-preset-buttons">
                        <?php foreach ($this->date_presets as $key => $preset): ?>
                            <button type="button" class="button conversaai-date-preset" 
                                    data-start="<?php echo esc_attr($preset['start']); ?>" 
                                    data-end="<?php echo esc_attr($preset['end']); ?>">
                                <?php echo esc_html($preset['label']); ?>
                            </button>
                        <?php endforeach; ?>
                        <button type="button" class="button conversaai-date-preset-custom">
                            <?php _e('Custom Range', 'conversaai-pro-wp'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="conversaai-custom-date-range">
                    <div class="conversaai-date-fields">
                        <div class="conversaai-date-field">
                            <label for="conversaai-date-start"><?php _e('From:', 'conversaai-pro-wp'); ?></label>
                            <input type="date" id="conversaai-date-start" name="start_date" 
                                  value="<?php echo esc_attr($current_filters['start_date']); ?>" class="conversaai-date-input">
                        </div>
                        <div class="conversaai-date-field">
                            <label for="conversaai-date-end"><?php _e('To:', 'conversaai-pro-wp'); ?></label>
                            <input type="date" id="conversaai-date-end" name="end_date" 
                                  value="<?php echo esc_attr($current_filters['end_date']); ?>" class="conversaai-date-input">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="conversaai-filter-actions">
                <div class="conversaai-channel-filter">
                    <label for="conversaai-channel"><?php _e('Channel:', 'conversaai-pro-wp'); ?></label>
                    <select id="conversaai-channel" class="conversaai-select">
                        <option value=""><?php _e('All Channels', 'conversaai-pro-wp'); ?></option>
                        <option value="webchat" <?php selected($current_filters['channel'], 'webchat'); ?>><?php _e('Website Chat', 'conversaai-pro-wp'); ?></option>
                        <option value="whatsapp" <?php selected($current_filters['channel'], 'whatsapp'); ?>><?php _e('WhatsApp', 'conversaai-pro-wp'); ?></option>
                        <option value="messenger" <?php selected($current_filters['channel'], 'messenger'); ?>><?php _e('Facebook Messenger', 'conversaai-pro-wp'); ?></option>
                        <option value="instagram" <?php selected($current_filters['channel'], 'instagram'); ?>><?php _e('Instagram', 'conversaai-pro-wp'); ?></option>
                    </select>
                </div>
                
                <button type="submit" form="conversaai-analytics-form" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Apply Filters', 'conversaai-pro-wp'); ?>
                </button>
                
                <div class="conversaai-export-dropdown">
                    <button type="button" class="button conversaai-export-button">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'conversaai-pro-wp'); ?>
                    </button>
                    <div class="conversaai-export-options">
                        <a href="#" class="conversaai-export-option" data-format="csv">
                            <?php _e('Export as CSV', 'conversaai-pro-wp'); ?>
                        </a>
                        <a href="#" class="conversaai-export-option" data-format="json">
                            <?php _e('Export as JSON', 'conversaai-pro-wp'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Error/Notice Container -->
    <?php if (!empty($error_message)): ?>
    <div class="conversaai-analytics-notices">
        <div class="conversaai-notice conversaai-notice-error">
            <span class="conversaai-notice-icon dashicons dashicons-dismiss"></span>
            <span class="conversaai-notice-message"><?php echo esc_html($error_message); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Analytics Dashboard -->
    <div class="conversaai-analytics-dashboard">
        <!-- Summary Cards -->
        <div class="conversaai-analytics-summary">
            <div class="conversaai-analytics-card conversaai-conversations-card">
                <div class="conversaai-card-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div class="conversaai-card-content">
                    <h3><?php _e('Conversations', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-analytics-value" id="conversation-count">
                        <?php echo number_format($summary_metrics['conversation_count']); ?>
                    </div>
                    <div class="conversaai-analytics-label"><?php _e('Total Conversations', 'conversaai-pro-wp'); ?></div>
                </div>
            </div>
            
            <div class="conversaai-analytics-card conversaai-messages-card">
                <div class="conversaai-card-icon">
                    <span class="dashicons dashicons-admin-comments"></span>
                </div>
                <div class="conversaai-card-content">
                    <h3><?php _e('Messages', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-analytics-value" id="message-count">
                        <?php echo number_format($summary_metrics['message_count']); ?>
                    </div>
                    <div class="conversaai-analytics-label"><?php _e('Total Messages', 'conversaai-pro-wp'); ?></div>
                </div>
            </div>
            
            <div class="conversaai-analytics-card conversaai-success-card">
                <div class="conversaai-card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="conversaai-card-content">
                    <h3><?php _e('Success Rate', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-analytics-value" id="success-rate">
                        <?php echo number_format($summary_metrics['success_rate'] * 100, 1); ?>%
                    </div>
                    <div class="conversaai-analytics-label"><?php _e('Conversation Success Rate', 'conversaai-pro-wp'); ?></div>
                </div>
            </div>
            
            <div class="conversaai-analytics-card conversaai-kb-card">
                <div class="conversaai-card-icon">
                    <span class="dashicons dashicons-book"></span>
                </div>
                <div class="conversaai-card-content">
                    <h3><?php _e('Knowledge Base', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-analytics-value" id="kb-usage">
                        <?php echo number_format($summary_metrics['kb_usage_rate'] * 100, 1); ?>%
                    </div>
                    <div class="conversaai-analytics-label"><?php _e('KB Usage Rate', 'conversaai-pro-wp'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Chart Widgets -->
        <div class="conversaai-widget-row">
            <div class="conversaai-widget conversaai-widget-large" id="conversations-chart-widget">
                <div class="conversaai-widget-header">
                    <h3><?php _e('Conversations Over Time', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-widget-actions">
                        <button type="button" class="conversaai-widget-refresh" data-widget="conversations_chart">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="conversaai-widget-content">
                    <div class="conversaai-chart-container">
                        <?php if (!empty($chart_data['conversations_chart']['labels'])): ?>
                            <canvas id="conversaai-conversations-chart"></canvas>
                        <?php else: ?>
                            <div class="conversaai-no-data"><?php _e('No data available for the selected period.', 'conversaai-pro-wp'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="conversaai-widget-row">
            <div class="conversaai-widget conversaai-widget-medium" id="sources-chart-widget">
                <div class="conversaai-widget-header">
                    <h3><?php _e('Response Sources', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-widget-actions">
                        <button type="button" class="conversaai-widget-refresh" data-widget="sources_chart">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="conversaai-widget-content">
                    <div class="conversaai-chart-container">
                        <?php if (!empty($chart_data['sources_chart']['datasets'][0]['data']) && array_sum($chart_data['sources_chart']['datasets'][0]['data']) > 0): ?>
                            <canvas id="conversaai-sources-chart"></canvas>
                        <?php else: ?>
                            <div class="conversaai-no-data"><?php _e('No data available for the selected period.', 'conversaai-pro-wp'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="conversaai-widget conversaai-widget-medium" id="success-distribution-widget">
                <div class="conversaai-widget-header">
                    <h3><?php _e('Success Score Distribution', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-widget-actions">
                        <button type="button" class="conversaai-widget-refresh" data-widget="success_distribution">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="conversaai-widget-content">
                    <div class="conversaai-chart-container">
                        <?php if (!empty($chart_data['success_distribution']['datasets'][0]['data']) && array_sum($chart_data['success_distribution']['datasets'][0]['data']) > 0): ?>
                            <canvas id="conversaai-success-distribution-chart"></canvas>
                        <?php else: ?>
                            <div class="conversaai-no-data"><?php _e('No data available for the selected period.', 'conversaai-pro-wp'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="conversaai-widget-row">
            <div class="conversaai-widget conversaai-widget-medium" id="channels-chart-widget">
                <div class="conversaai-widget-header">
                    <h3><?php _e('Conversations by Channel', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-widget-actions">
                        <button type="button" class="conversaai-widget-refresh" data-widget="channels_chart">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="conversaai-widget-content">
                    <div class="conversaai-chart-container">
                        <?php if (!empty($chart_data['channels_chart']['labels'])): ?>
                            <canvas id="conversaai-channels-chart"></canvas>
                        <?php else: ?>
                            <div class="conversaai-no-data"><?php _e('No data available for the selected period.', 'conversaai-pro-wp'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="conversaai-widget conversaai-widget-medium" id="trending-queries-widget">
                <div class="conversaai-widget-header">
                    <h3><?php _e('Trending Queries', 'conversaai-pro-wp'); ?></h3>
                    <div class="conversaai-widget-actions">
                        <button type="button" class="conversaai-widget-refresh" data-widget="trending_queries">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="conversaai-widget-content">
                    <div class="conversaai-trending-table-container">
                        <table class="conversaai-trending-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Query', 'conversaai-pro-wp'); ?></th>
                                    <th><?php _e('Count', 'conversaai-pro-wp'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="conversaai-trending-queries">
                                <?php if (!empty($chart_data['trending_queries'])): ?>
                                    <?php foreach ($chart_data['trending_queries'] as $query): ?>
                                    <tr>
                                        <td><?php echo esc_html($query['query']); ?></td>
                                        <td><?php echo esc_html($query['count']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="conversaai-no-data">
                                            <?php _e('No trending queries data available.', 'conversaai-pro-wp'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Analytics Dashboard Styling */
.conversaai-pro-analytics {
    max-width: 1600px;
    margin: 20px auto;
    position: relative;
}

/* Filters Controls */
.conversaai-analytics-controls {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    position: relative;
}

.conversaai-analytics-filters {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.conversaai-date-filter-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.conversaai-date-presets {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.conversaai-date-preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.conversaai-date-preset {
    padding: 5px 10px;
    background: #f0f0f0;
    border-radius: 4px;
    font-size: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.conversaai-date-preset:hover,
.conversaai-date-preset.active {
    background: #4c66ef;
    color: white;
}

.conversaai-custom-date-range {
    display: flex;
    align-items: center;
    gap: 15px;
}

.conversaai-date-fields {
    display: flex;
    align-items: center;
    gap: 20px;
}

.conversaai-date-field {
    display: flex;
    align-items: center;
    gap: 8px;
}

.conversaai-date-input {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.conversaai-filter-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-top: 10px;
}

.conversaai-channel-filter {
    display: flex;
    align-items: center;
    gap: 8px;
}

.conversaai-select {
    min-width: 180px;
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Export dropdown */
.conversaai-export-dropdown {
    position: relative;
}

.conversaai-export-options {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 100;
    width: 180px;
    display: none;
}

.conversaai-export-dropdown:hover .conversaai-export-options {
    display: block;
}

.conversaai-export-option {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    transition: background 0.2s ease;
}

.conversaai-export-option:hover {
    background: #f5f5f5;
    color: #4c66ef;
}

/* Loading overlay */
.conversaai-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 10;
    border-radius: 8px;
}

.conversaai-loading-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #4c66ef;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: conversaai-spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes conversaai-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.conversaai-loading-text {
    font-size: 14px;
    color: #666;
}

/* Analytics Dashboard */
.conversaai-analytics-dashboard {
    margin-top: 20px;
}

/* Summary Cards */
.conversaai-analytics-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.conversaai-analytics-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
    display: flex;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.conversaai-analytics-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.conversaai-card-icon {
    background: #f0f4ff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 15px;
}

.conversaai-card-icon .dashicons {
    color: #4c66ef;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.conversaai-card-content {
    flex: 1;
}

.conversaai-card-content h3 {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.conversaai-analytics-value {
    font-size: 28px;
    font-weight: bold;
    margin: 5px 0;
    color: #333;
}

.conversaai-analytics-label {
    font-size: 12px;
    color: #777;
}

/* Card variations */
.conversaai-conversations-card .conversaai-card-icon {
    background-color: #e1f5fe;
}

.conversaai-conversations-card .conversaai-card-icon .dashicons {
    color: #039be5;
}

.conversaai-messages-card .conversaai-card-icon {
    background-color: #e8f5e9;
}

.conversaai-messages-card .conversaai-card-icon .dashicons {
    color: #43a047;
}

.conversaai-success-card .conversaai-card-icon {
    background-color: #e3f2fd;
}

.conversaai-success-card .conversaai-card-icon .dashicons {
    color: #1976d2;
}

.conversaai-kb-card .conversaai-card-icon {
    background-color: #fff3e0;
}

.conversaai-kb-card .conversaai-card-icon .dashicons {
    color: #ff9800;
}

/* Widgets */
.conversaai-widget-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.conversaai-widget {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
}

.conversaai-widget-large {
    flex: 1 1 100%;
    min-height: 400px;
}

.conversaai-widget-medium {
    flex: 1 1 calc(50% - 10px);
    min-width: 300px;
    min-height: 350px;
}

.conversaai-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.conversaai-widget-header h3 {
    margin: 0;
    font-size: 16px;
}

.conversaai-widget-actions {
    display: flex;
    gap: 10px;
}

.conversaai-widget-refresh {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #999;
    padding: 0;
    transition: color 0.2s ease;
}

.conversaai-widget-refresh:hover {
    color: #4c66ef;
}

.conversaai-widget-content {
    padding: 20px;
    position: relative;
    height: calc(100% - 60px); 
}

.conversaai-widget-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
}

.conversaai-chart-container {
    width: 100%;
    height: 100%;
    min-height: 250px;
}

/* Trending Queries Table */
.conversaai-trending-table-container {
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
}

.conversaai-trending-table {
    width: 100%;
    border-collapse: collapse;
}

.conversaai-trending-table th,
.conversaai-trending-table td {
    padding: 10px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.conversaai-trending-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #555;
}

.conversaai-trending-table tr:hover td {
    background-color: #f5f8ff;
}

.conversaai-no-data {
    text-align: center;
    padding: 30px;
    color: #999;
    font-style: italic;
}

/* Notices */
.conversaai-analytics-notices {
    margin-bottom: 20px;
}

.conversaai-notice {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.conversaai-notice-icon {
    margin-right: 10px;
}

.conversaai-notice-success {
    background-color: #e8f5e9;
    color: #388e3c;
    border-left: 4px solid #4caf50;
}

.conversaai-notice-error {
    background-color: #ffebee;
    color: #d32f2f;
    border-left: 4px solid #f44336;
}

.conversaai-notice-warning {
    background-color: #fff8e1;
    color: #f57c00;
    border-left: 4px solid #ffc107;
}

.conversaai-notice-info {
    background-color: #e3f2fd;
    color: #1976d2;
    border-left: 4px solid #2196f3;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .conversaai-analytics-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .conversaai-widget-medium {
        flex: 1 1 100%;
    }
}

@media (max-width: 782px) {
    .conversaai-date-fields {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .conversaai-filter-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .conversaai-channel-filter {
        width: 100%;
    }
    
    .conversaai-select {
        width: 100%;
    }
    
    .conversaai-analytics-summary {
        grid-template-columns: 1fr;
    }
    
    .conversaai-widget-medium,
    .conversaai-widget-large {
        min-height: 300px;
    }
}

/* refinements to match plugin style */
.conversaai-analytics-filters {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.conversaai-date-preset {
    border: 1px solid #ddd;
    background: #f8f9fa;
}

.conversaai-date-preset.active {
    background: #4c66ef;
    color: white;
    border-color: #3a51c6;
}

.conversaai-filter-actions {
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.conversaai-date-field label {
    font-weight: 500;
    color: #555;
}

.conversaai-date-input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 10px;
}

.conversaai-channel-filter label {
    font-weight: 500;
    color: #555;
}

.conversaai-select {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 10px;
}

#conversaai-apply-filters {
    background: #4c66ef;
    border-color: #3a51c6;
}

.conversaai-export-button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.conversaai-export-button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.conversaai-widget-header {
    background: #f8f9fa;
}

/* dashicons in buttons */
.button .dashicons {
    vertical-align: middle;
    margin-top: -3px;
}

/* responsiveness for mobile */
@media (max-width: 500px) {
    .conversaai-filter-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .conversaai-channel-filter, 
    button[form="conversaai-analytics-form"], 
    .conversaai-export-dropdown {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .conversaai-channel-filter {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .conversaai-select {
        width: 100%;
    }
}
</style>