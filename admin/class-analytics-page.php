<?php
/**
 * Analytics page functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Analytics_Manager;

/**
 * Analytics page class.
 *
 * Handles the analytics dashboard functionality.
 *
 * @since      1.0.0
 */
class Analytics_Page {

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
     * Analytics manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Core\Analytics_Manager    $analytics_manager    The analytics manager instance.
     */
    private $analytics_manager;

    /**
     * Date presets for quick selection.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $date_presets    The available date presets.
     */
    protected $date_presets;

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
        $this->analytics_manager = new Analytics_Manager();
        
        // Set up date presets
        $this->date_presets = [
            'today' => [
                'label' => __('Today', 'conversaai-pro-wp'),
                'start' => date('Y-m-d'),
                'end' => date('Y-m-d'),
            ],
            'yesterday' => [
                'label' => __('Yesterday', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-1 day')),
                'end' => date('Y-m-d', strtotime('-1 day')),
            ],
            'last7days' => [
                'label' => __('Last 7 Days', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-7 days')),
                'end' => date('Y-m-d'),
            ],
            'last30days' => [
                'label' => __('Last 30 Days', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-30 days')),
                'end' => date('Y-m-d'),
            ],
            'thismonth' => [
                'label' => __('This Month', 'conversaai-pro-wp'),
                'start' => date('Y-m-01'),
                'end' => date('Y-m-d'),
            ],
            'lastmonth' => [
                'label' => __('Last Month', 'conversaai-pro-wp'),
                'start' => date('Y-m-01', strtotime('first day of last month')),
                'end' => date('Y-m-t', strtotime('last day of last month')),
            ],
        ];
        
        // Register the export handler
        add_action('admin_init', array($this, 'handle_export_request'));
    }

    /**
     * Display the analytics page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Get default date range (last 30 days)
        $default_start_date = date('Y-m-d', strtotime('-30 days'));
        $default_end_date = date('Y-m-d');
        
        // Get filter values from GET parameters or use defaults
        $start_date = isset($_GET['start_date']) && $this->validate_date($_GET['start_date']) 
            ? sanitize_text_field($_GET['start_date']) 
            : $default_start_date;
            
        $end_date = isset($_GET['end_date']) && $this->validate_date($_GET['end_date']) 
            ? sanitize_text_field($_GET['end_date']) 
            : $default_end_date;
            
        $channel = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : '';
        
        // Store current filters for display
        $current_filters = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'channel' => $channel
        ];
        
        $error_message = '';
        
        // Load analytics data - wrapped in try/catch to handle errors gracefully
        try {
            // Get analytics data for the selected period
            $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date, $channel);
            
            // Get conversation success metrics
            $success_metrics = $this->analytics_manager->get_conversation_success_metrics();
            
            // Prepare summary metrics for display
            $summary_metrics = [
                'conversation_count' => $analytics_data['totals']['conversation_count'] ?? 0,
                'message_count' => $analytics_data['totals']['message_count'] ?? 0,
                'success_rate' => $analytics_data['success_rate'] ?? 0,
                'kb_usage_rate' => $analytics_data['kb_usage_rate'] ?? 0,
            ];
            
            // Prepare data for each chart
            $chart_data = [
                'conversations_chart' => $this->prepare_conversations_chart_data($analytics_data),
                'sources_chart' => $this->prepare_sources_chart_data($analytics_data),
                'success_distribution' => $this->prepare_success_distribution_data($success_metrics),
                'channels_chart' => $this->prepare_channels_chart_data($analytics_data),
                'trending_queries' => $analytics_data['trending_queries'] ?? [],
            ];
            
            // Add sample data if there's no real data (for testing/preview)
            if (empty($analytics_data['totals']['conversation_count']) && WP_DEBUG) {
                $chart_data = $this->add_sample_data($chart_data);
            }
            
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro: Error loading analytics data: ' . $e->getMessage());
            
            // Set error message for display
            $error_message = sprintf(
                __('Error loading analytics data: %s', 'conversaai-pro-wp'), 
                $e->getMessage()
            );
            
            // Create empty data structures for the view
            $summary_metrics = [
                'conversation_count' => 0,
                'message_count' => 0,
                'success_rate' => 0,
                'kb_usage_rate' => 0,
            ];
            
            $chart_data = [
                'conversations_chart' => ['labels' => [], 'datasets' => [['data' => []]]],
                'sources_chart' => ['labels' => [], 'datasets' => [['data' => []]]],
                'success_distribution' => ['labels' => [], 'datasets' => [['data' => []]]],
                'channels_chart' => ['labels' => [], 'datasets' => [['data' => []]]],
                'trending_queries' => [],
            ];
        }
        
        // Enqueue required scripts with our data
        $this->enqueue_analytics_assets($chart_data);
        
        // Include the view template
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/analytics-page.php';
    }

    /**
     * Enqueue assets required for the analytics page.
     *
     * @since    1.0.0
     * @param    array    $chart_data    The data for the charts.
     */
    private function enqueue_analytics_assets($chart_data) {
        // Enqueue Chart.js from CDN
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue data tables for table functionality
        wp_enqueue_style(
            'conversaai-analytics-css',
            CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/css/analytics.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'datatables-js',
            'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
            array('jquery'),
            '1.13.4',
            true
        );
        
        // Register and enqueue analytics page JavaScript
        wp_register_script(
            'conversaai-analytics-js',
            CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/js/analytics.js',
            array('jquery', 'chartjs', 'datatables-js'),
            $this->version,
            true
        );
        
        // Localize the script with our chart data
        wp_localize_script(
            'conversaai-analytics-js',
            'conversaaiAnalytics',
            array(
                'chartData' => $chart_data,
                'i18n' => array(
                    'noData' => __('No data available for the selected period.', 'conversaai-pro-wp'),
                    'error' => __('Error loading data. Please try again.', 'conversaai-pro-wp'),
                    'conversations' => __('Conversations', 'conversaai-pro-wp'),
                    'messages' => __('Messages', 'conversaai-pro-wp'),
                    'aiResponses' => __('AI Responses', 'conversaai-pro-wp'),
                    'kbResponses' => __('KB Responses', 'conversaai-pro-wp'),
                )
            )
        );
        
        wp_enqueue_script('conversaai-analytics-js');
    }

    /**
     * Handle export request.
     *
     * @since    1.0.0
     */
    public function handle_export_request() {
        // Only process export requests
        if (!isset($_POST['action']) || $_POST['action'] !== 'export_analytics') {
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export analytics.', 'conversaai-pro-wp'));
        }
        
        // Get and validate input
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        
        // Validate dates
        if (!$this->validate_date($start_date) || !$this->validate_date($end_date)) {
            wp_die(__('Invalid date format.', 'conversaai-pro-wp'));
        }
        
        try {
            // Get analytics data
            $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date, $channel);
            
            // Generate export data based on format
            $export_data = $this->generate_export_data($analytics_data, $format);
            
            // Generate filename
            $date_range = $start_date . '_to_' . $end_date;
            $channel_str = !empty($channel) ? '_' . $channel : '';
            $filename = 'conversaai_analytics_' . $date_range . $channel_str . '.' . $format;
            
            // Set headers for download
            header('Content-Type: ' . ($format === 'csv' ? 'text/csv' : 'application/json'));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output file contents
            echo $export_data;
            exit;
        } catch (\Exception $e) {
            wp_die(__('Error exporting analytics data: ', 'conversaai-pro-wp') . $e->getMessage());
        }
    }

    /**
     * Generate export data in the specified format.
     *
     * @since    1.0.0
     * @param    array     $analytics_data   The analytics data.
     * @param    string    $format           The export format (csv or json).
     * @return   string    The formatted export data.
     */
    private function generate_export_data($analytics_data, $format = 'csv') {
        // For JSON format, simply encode the data
        if ($format === 'json') {
            return json_encode($analytics_data);
        }
        
        // For CSV format, we need to build a structured CSV
        $csv_data = array();
        
        // Add headers
        $csv_data[] = array(
            'Date',
            'Channel',
            'Conversations',
            'Messages',
            'AI Responses',
            'KB Responses',
            'Successful Conversations'
        );
        
        // Add data by date and channel
        foreach ($analytics_data['by_date'] as $date => $date_data) {
            foreach ($analytics_data['by_channel'] as $channel => $channel_data) {
                $csv_data[] = array(
                    $date,
                    $channel,
                    $date_data['conversation_count'] ?? 0,
                    $date_data['message_count'] ?? 0,
                    $date_data['ai_request_count'] ?? 0,
                    $date_data['kb_answer_count'] ?? 0,
                    $date_data['successful_conversation_count'] ?? 0
                );
            }
        }
        
        // Convert to CSV string
        $csv_string = '';
        foreach ($csv_data as $row) {
            $csv_string .= implode(',', $row) . "\n";
        }
        
        return $csv_string;
    }

    /**
     * Validate a date string.
     *
     * @since    1.0.0
     * @param    string    $date    The date string to validate.
     * @return   bool      Whether the date is valid.
     */
    private function validate_date($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Add sample data for testing when no real data exists.
     *
     * @since    1.0.0
     * @param    array    $chart_data    The existing chart data.
     * @return   array    Chart data with samples added.
     */
    private function add_sample_data($chart_data) {
        // Sample data for conversations chart
        $chart_data['conversations_chart'] = [
            'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            'datasets' => [
                [
                    'label' => __('Conversations', 'conversaai-pro-wp'),
                    'data' => [5, 8, 12, 10, 15, 20, 18],
                ],
                [
                    'label' => __('Messages', 'conversaai-pro-wp'),
                    'data' => [15, 24, 36, 30, 45, 60, 54],
                ],
            ],
        ];
        
        // Sample data for sources chart
        $chart_data['sources_chart'] = [
            'labels' => [__('Knowledge Base', 'conversaai-pro-wp'), __('AI Responses', 'conversaai-pro-wp')],
            'datasets' => [
                [
                    'data' => [35, 65],
                ],
            ],
        ];
        
        // Sample data for success distribution chart
        $chart_data['success_distribution'] = [
            'labels' => [
                __('Excellent', 'conversaai-pro-wp'),
                __('Good', 'conversaai-pro-wp'),
                __('Average', 'conversaai-pro-wp'),
                __('Poor', 'conversaai-pro-wp'),
                __('Very Poor', 'conversaai-pro-wp'),
            ],
            'datasets' => [
                [
                    'data' => [45, 30, 15, 7, 3],
                ],
            ],
        ];
        
        // Sample data for channels chart
        $chart_data['channels_chart'] = [
            'labels' => ['Website Chat', 'WhatsApp', 'Messenger', 'Instagram'],
            'datasets' => [
                [
                    'label' => __('Conversations', 'conversaai-pro-wp'),
                    'data' => [65, 25, 15, 5],
                ],
            ],
        ];
        
        // Sample trending queries
        $chart_data['trending_queries'] = [
            ['query' => 'How do I reset my password?', 'count' => 25],
            ['query' => 'What are your business hours?', 'count' => 18],
            ['query' => 'Do you offer free shipping?', 'count' => 15],
            ['query' => 'How do I return a product?', 'count' => 12],
            ['query' => 'Where is my order?', 'count' => 10],
        ];
        
        return $chart_data;
    }

    /**
     * Prepare data for conversations chart
     *
     * @since    1.0.0
     * @param    array    $analytics_data    The analytics data.
     * @return   array    Formatted data for the chart.
     */
    private function prepare_conversations_chart_data($analytics_data) {
        $dates = array_keys($analytics_data['by_date'] ?? []);
        sort($dates); // Ensure dates are in chronological order
        
        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => __('Conversations', 'conversaai-pro-wp'),
                    'data' => array_map(function($date) use ($analytics_data) {
                        return $analytics_data['by_date'][$date]['conversation_count'] ?? 0;
                    }, $dates),
                ],
                [
                    'label' => __('Messages', 'conversaai-pro-wp'),
                    'data' => array_map(function($date) use ($analytics_data) {
                        return $analytics_data['by_date'][$date]['message_count'] ?? 0;
                    }, $dates),
                ],
            ],
        ];
    }

    /**
     * Prepare data for response sources chart
     *
     * @since    1.0.0
     * @param    array    $analytics_data    The analytics data.
     * @return   array    Formatted data for the chart.
     */
    private function prepare_sources_chart_data($analytics_data) {
        return [
            'labels' => [
                __('Knowledge Base', 'conversaai-pro-wp'),
                __('AI Responses', 'conversaai-pro-wp'),
            ],
            'datasets' => [
                [
                    'data' => [
                        $analytics_data['totals']['kb_answer_count'] ?? 0,
                        $analytics_data['totals']['ai_request_count'] ?? 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * Prepare data for success distribution chart
     *
     * @since    1.0.0
     * @param    array    $success_metrics    The success metrics data.
     * @return   array    Formatted data for the chart.
     */
    private function prepare_success_distribution_data($success_metrics) {
        $distribution = $success_metrics['distribution'] ?? [];
        
        return [
            'labels' => [
                __('Excellent', 'conversaai-pro-wp'),
                __('Good', 'conversaai-pro-wp'),
                __('Average', 'conversaai-pro-wp'),
                __('Poor', 'conversaai-pro-wp'),
                __('Very Poor', 'conversaai-pro-wp'),
            ],
            'datasets' => [
                [
                    'data' => [
                        $distribution['excellent'] ?? 0,
                        $distribution['good'] ?? 0,
                        $distribution['average'] ?? 0,
                        $distribution['poor'] ?? 0,
                        $distribution['very_poor'] ?? 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * Prepare data for channels chart
     *
     * @since    1.0.0
     * @param    array    $analytics_data    The analytics data.
     * @return   array    Formatted data for the chart.
     */
    private function prepare_channels_chart_data($analytics_data) {
        $channels = array_keys($analytics_data['by_channel'] ?? []);
        
        return [
            'labels' => array_map(function($channel) {
                return ucfirst($channel);
            }, $channels),
            'datasets' => [
                [
                    'label' => __('Conversations', 'conversaai-pro-wp'),
                    'data' => array_map(function($channel) use ($analytics_data) {
                        return $analytics_data['by_channel'][$channel]['conversation_count'] ?? 0;
                    }, $channels),
                ],
            ],
        ];
    }
}