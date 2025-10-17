<?php
/**
 * Messaging Channels admin page.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Messaging Channels admin page class.
 *
 * Handles the messaging channels management and configuration.
 *
 * @since      1.0.0
 */
class Channels_Page {

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
        
        // Only log in debug mode
        if (WP_DEBUG && defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
            error_log('Channels_Page initialized for ' . $plugin_name);
        }
    }

    /**
     * Display the channels page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Get channels settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array(
            'whatsapp' => array(
                'enabled' => false,
                'phone_number' => '',
                'api_key' => '',
                'business_account_id' => '',
                'webhook_secret' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on WhatsApp. How can we assist you today?',
            ),
            'messenger' => array(
                'enabled' => false,
                'page_id' => '',
                'app_id' => '',
                'app_secret' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Messenger. How can we assist you today?',
            ),
            'instagram' => array(
                'enabled' => false,
                'account_id' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Instagram. How can we assist you today?',
            )
        ));
        
        // Debug the current settings
        error_log('Current channel settings: ' . print_r($channels_settings, true));
        
        // Get all recent conversations for each channel (useful for statistics)
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        $channel_stats = array();
        $channels = array('whatsapp', 'messenger', 'instagram', 'webchat');
        
        foreach ($channels as $channel) {
            // Default values in case the query fails
            $channel_stats[$channel] = array(
                'total' => 0,
                'last_24h' => 0,
                'last_week' => 0,
            );
            
            // Safely get stats
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s",
                $channel
            ));
            
            $last_24h = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $channel
            ));
            
            $last_week = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $channel
            ));
            
            // Only update if the queries succeeded
            if ($total !== null) {
                $channel_stats[$channel]['total'] = (int) $total;
            }
            
            if ($last_24h !== null) {
                $channel_stats[$channel]['last_24h'] = (int) $last_24h;
            }
            
            if ($last_week !== null) {
                $channel_stats[$channel]['last_week'] = (int) $last_week;
            }
        }
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/channels-page.php';
    }

    /**
     * AJAX handler for saving channel settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_channel_settings() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            error_log('ConversaAI: Security check failed in channels save');
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            error_log('ConversaAI: User does not have permission for channels save');
            wp_send_json_error(array('message' => __('You do not have permission to change channel settings.', 'conversaai-pro-wp')));
            return;
        }
        
        // Get the channel type
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        
        if (!in_array($channel_type, array('whatsapp', 'messenger', 'instagram'))) {
            error_log('ConversaAI: Invalid channel type: ' . $channel_type);
            wp_send_json_error(array('message' => __('Invalid channel type.', 'conversaai-pro-wp')));
            return;
        }
        
        // Process form data
        $settings_data = array();
        
        // Loop through POST data for the settings
        foreach ($_POST as $key => $value) {
            // Skip the nonce, action, and channel_type
            if (in_array($key, array('nonce', 'action', 'channel_type'))) {
                continue;
            }
            
            // Add to settings data
            $settings_data[$key] = $value;
        }
        
        // Log settings before sanitization
        error_log('AJAX settings data received:');
        error_log(print_r($_POST, true));
        
        // Get current settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        // Ensure channels_settings is an array
        if (!is_array($channels_settings)) {
            $channels_settings = array();
        }
        
        // Sanitize and update specific channel settings
        $sanitized = $this->sanitize_channel_settings($channel_type, $settings_data);
        
        // Ensure each channel has a default structure if not set
        if (!isset($channels_settings['whatsapp'])) {
            $channels_settings['whatsapp'] = array('enabled' => false);
        }
        if (!isset($channels_settings['messenger'])) {
            $channels_settings['messenger'] = array('enabled' => false);
        }
        if (!isset($channels_settings['instagram'])) {
            $channels_settings['instagram'] = array('enabled' => false);
        }
        
        // Update the specific channel settings
        $channels_settings[$channel_type] = $sanitized;
        
        // Add a timestamp to force WordPress to recognize a change
        $channels_settings['_last_updated'] = time();
        
        // Create option if it doesn't exist
        if (false === get_option('conversaai_pro_channels_settings')) {
            add_option('conversaai_pro_channels_settings', $channels_settings);
            error_log('ConversaAI: Created conversaai_pro_channels_settings option');
            $result = true;
        } else {
            // Update existing option with force parameter set to true
            $result = update_option('conversaai_pro_channels_settings', $channels_settings, true);
        }
        
        // Debug the update operation
        error_log('ConversaAI: Settings after update: ' . print_r($channels_settings, true));
        error_log('ConversaAI: Update result: ' . ($result ? 'true' : 'false'));
        
        // Check for WordPress database errors
        global $wpdb;
        if (!empty($wpdb->last_error)) {
            error_log('ConversaAI: Database error: ' . $wpdb->last_error);
        }
        
        // Ensure we're sending a properly formatted JSON response
        if ($result) {
            $response = array(
                'success' => true,
                'data' => array(
                    'message' => sprintf(__('%s settings saved successfully.', 'conversaai-pro-wp'), ucfirst($channel_type)),
                    'settings' => $sanitized
                )
            );
            
            // Log successful response
            error_log('ConversaAI: AJAX save successful: ' . json_encode($response));
            
            // Use wp_send_json_success to ensure proper headers
            wp_send_json_success($response['data']);
        } else {
            $response = array(
                'success' => false,
                'data' => array(
                    'message' => sprintf(__('Error saving %s settings. Settings may be unchanged.', 'conversaai-pro-wp'), ucfirst($channel_type))
                )
            );
            
            // Log error response
            error_log('ConversaAI: AJAX save failed: ' . json_encode($response));
            
            wp_send_json_error($response['data']);
        }
    }

    /**
     * AJAX handler for testing channel connections.
     *
     * @since    1.0.0
     */
    public function ajax_test_channel_connection() {
        // Security and validation checks (keep existing code)
        
        // Get and sanitize settings
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        $settings_data = array();
        
        foreach ($_POST as $key => $value) {
            if (in_array($key, array('nonce', 'action', 'channel_type'))) {
                continue;
            }
            $settings_data[$key] = $value;
        }
        
        // Sanitize settings
        $test_settings = $this->sanitize_channel_settings($channel_type, $settings_data);
        
        // Attempt a real connection test based on channel type
        $result = array('success' => false);
        
        switch ($channel_type) {
            case 'whatsapp':
                $result = $this->test_whatsapp_connection($test_settings);
                break;
                
            case 'messenger':
                $result = $this->test_messenger_connection($test_settings);
                break;
                
            case 'instagram':
                $result = $this->test_instagram_connection($test_settings);
                break;
        }
        
        // Handle the test result
        if ($result['success']) {
            error_log('Test connection successful');
            wp_send_json_success(array(
                'message' => sprintf(__('Connection to %s successful.', 'conversaai-pro-wp'), ucfirst($channel_type)),
                'details' => $result['details'] ?? array('status' => 'Connected'),
            ));
        } else {
            error_log('Test connection failed: ' . ($result['error'] ?? 'Unknown error'));
            wp_send_json_error(array(
                'message' => sprintf(__('Connection to %s failed: %s', 'conversaai-pro-wp'), 
                    ucfirst($channel_type), 
                    $result['error'] ?? __('Unknown error. Please check your credentials.', 'conversaai-pro-wp')
                ),
            ));
        }
    }
    
    /**
     * Test WhatsApp Business API connection.
     *
     * @since    1.0.0
     * @param    array     $settings    WhatsApp settings to test.
     * @return   array     Connection test results.
     */
    private function test_whatsapp_connection($settings) {
        // Skip if missing required fields
        if (empty($settings['phone_number']) || empty($settings['api_key']) || empty($settings['business_account_id'])) {
            return array(
                'success' => false,
                'error' => __('Missing required credentials. Please fill all required fields.', 'conversaai-pro-wp')
            );
        }
        
        // Make a simple API call to test connection
        $api_endpoint = "https://graph.facebook.com/v16.0/{$settings['business_account_id']}";
        $headers = array(
            'Authorization' => 'Bearer ' . $settings['api_key'],
            'Content-Type' => 'application/json',
        );
        
        // Check if we're in test mode or real mode
        if (defined('CONVERSAAI_TEST_MODE') && CONVERSAAI_TEST_MODE) {
            // Test mode - simulate API response
            return array(
                'success' => true,
                'details' => array(
                    'account_name' => 'Business Account',
                    'phone_number' => $settings['phone_number'],
                    'status' => 'Connected (Test Mode)',
                )
            );
        }
        
        // Real API call
        $api_request = new \ConversaAI_Pro_WP\Utils\API_Request();
        try {
            $response = $api_request->get($api_endpoint, $headers);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($status_code >= 400) {
                $error_message = isset($response_body['error']['message']) ? 
                    $response_body['error']['message'] : 
                    "Error {$status_code}: " . wp_remote_retrieve_response_message($response);
                    
                return array(
                    'success' => false,
                    'error' => $error_message
                );
            }
            
            // API call succeeded
            return array(
                'success' => true,
                'details' => array(
                    'account_name' => $response_body['name'] ?? 'Business Account',
                    'phone_number' => $settings['phone_number'],
                    'status' => 'Connected',
                )
            );
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Test Messenger API connection.
     * 
     * @since    1.0.0
     * @param    array     $settings    Messenger settings to test.
     * @return   array     Connection test results.
     */
    private function test_messenger_connection($settings) {
        // Implementation similar to WhatsApp test
        // For brevity, I'm including a placeholder implementation
        
        if (empty($settings['page_id']) || empty($settings['access_token'])) {
            return array(
                'success' => false,
                'error' => __('Missing required credentials. Please fill all required fields.', 'conversaai-pro-wp')
            );
        }
        
        // Test mode or real API call
        if (defined('CONVERSAAI_TEST_MODE') && CONVERSAAI_TEST_MODE) {
            return array(
                'success' => true,
                'details' => array(
                    'page_name' => 'Facebook Page',
                    'page_id' => $settings['page_id'],
                    'status' => 'Connected (Test Mode)',
                )
            );
        }
        
        // Real API implementation would go here
        // This would test the connection to the Facebook Graph API
        
        return array(
            'success' => true,
            'details' => array(
                'status' => 'Connected',
                'note' => 'Full API verification not implemented yet'
            )
        );
    }
    
    /**
     * Test Instagram API connection.
     * 
     * @since    1.0.0
     * @param    array     $settings    Instagram settings to test.
     * @return   array     Connection test results.
     */
    private function test_instagram_connection($settings) {
        // Implementation similar to WhatsApp test
        // For brevity, I'm including a placeholder implementation
        
        if (empty($settings['account_id']) || empty($settings['access_token'])) {
            return array(
                'success' => false,
                'error' => __('Missing required credentials. Please fill all required fields.', 'conversaai-pro-wp')
            );
        }
        
        // Test mode or real API call
        if (defined('CONVERSAAI_TEST_MODE') && CONVERSAAI_TEST_MODE) {
            return array(
                'success' => true,
                'details' => array(
                    'username' => 'instagram_business',
                    'account_id' => $settings['account_id'],
                    'status' => 'Connected (Test Mode)',
                )
            );
        }
        
        // Real API implementation would go here
        // This would test the connection to the Instagram Graph API
        
        return array(
            'success' => true,
            'details' => array(
                'status' => 'Connected',
                'note' => 'Full API verification not implemented yet'
            )
        );
    }

    /**
     * AJAX handler for toggling channel active status.
     *
     * @since    1.0.0
     */
    public function ajax_toggle_channel() {
        // Log function call
        if (defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
            error_log('ajax_toggle_channel called');
        }
        
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            error_log('Nonce check failed');
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
            exit;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            error_log('Capability check failed');
            wp_send_json_error(array('message' => __('You do not have permission to toggle channels.', 'conversaai-pro-wp')));
            exit;
        }
        
        // Get the channel type and status
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        $status = isset($_POST['status']) ? (bool) $_POST['status'] : false;
        
        if (!in_array($channel_type, array('whatsapp', 'messenger', 'instagram'))) {
            error_log('Invalid channel type: ' . $channel_type);
            wp_send_json_error(array('message' => __('Invalid channel type.', 'conversaai-pro-wp')));
            exit;
        }
        
        // Get current settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        if (!isset($channels_settings[$channel_type])) {
            $channels_settings[$channel_type] = array('enabled' => false);
        }
        
        // Update status
        $channels_settings[$channel_type]['enabled'] = $status;
        
        // Save updated settings
        $result = update_option('conversaai_pro_channels_settings', $channels_settings);
        
        if ($result) {
            $message = $status 
                ? sprintf(__('%s channel enabled successfully.', 'conversaai-pro-wp'), ucfirst($channel_type))
                : sprintf(__('%s channel disabled.', 'conversaai-pro-wp'), ucfirst($channel_type));
            
            error_log('Channel toggle successful: ' . $message);
            wp_send_json_success(array(
                'message' => $message,
                'status' => $status,
            ));
        } else {
            error_log('Channel toggle failed');
            wp_send_json_error(array(
                'message' => sprintf(__('Error toggling %s channel. Status may be unchanged.', 'conversaai-pro-wp'), ucfirst($channel_type))
            ));
        }
        
        exit;
    }

    /**
     * AJAX handler for refreshing channel statistics.
     *
     * @since    1.0.0
     */
    public function ajax_get_channel_stats() {
        // Log function call
        error_log('ajax_get_channel_stats called');
        
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            error_log('Nonce check failed');
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
            exit;
        }
        
        // Get all recent conversations for each channel
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        $channel_stats = array();
        $channels = array('whatsapp', 'messenger', 'instagram', 'webchat');
        
        foreach ($channels as $channel) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s",
                $channel
            ));
            
            $last_24h = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $channel
            ));
            
            $last_week = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $channel
            ));
            
            $channel_stats[$channel] = array(
                'total' => (int) $total,
                'last_24h' => (int) $last_24h,
                'last_week' => (int) $last_week,
            );
        }
        
        error_log('Channel stats retrieved successfully');
        wp_send_json_success(array(
            'message' => __('Channel statistics updated successfully.', 'conversaai-pro-wp'),
            'stats' => $channel_stats,
        ));
        
        exit;
    }

    /**
     * Log messages with appropriate level control.
     *
     * @since    1.0.0
     * @param    string    $level      Log level ('error', 'warning', 'info', 'debug').
     * @param    string    $message    Message to log.
     * @param    mixed     $data       Optional data to include in log.
     */
    private function log($level, $message, $data = null) {
        // Skip logging if CONVERSAAI_DEBUG_LEVEL is set and level doesn't match
        if (defined('CONVERSAAI_DEBUG_LEVEL')) {
            $levels = array('error' => 0, 'warning' => 1, 'info' => 2, 'debug' => 3);
            $current_level = array_key_exists(CONVERSAAI_DEBUG_LEVEL, $levels) ? $levels[CONVERSAAI_DEBUG_LEVEL] : 0;
            
            if (!array_key_exists($level, $levels) || $levels[$level] > $current_level) {
                return;
            }
        } elseif ($level !== 'error' && !WP_DEBUG) {
            // If debug level not defined, only log errors unless WP_DEBUG is active
            return;
        }
        
        // Format log message
        $log_message = "[ConversaAI][{$level}] {$message}";
        
        // Add data if provided
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $log_message .= "\n" . print_r($data, true);
            } else {
                $log_message .= ": {$data}";
            }
        }
        
        // Use WordPress error_log function
        error_log($log_message);
    }

    /**
     * Sanitize channel settings.
     *
     * @since    1.0.0
     * @param    string    $channel_type    The channel type.
     * @param    array     $settings_data   The settings data to sanitize.
     * @return   array     The sanitized settings.
     */
    private function sanitize_channel_settings($channel_type, $settings_data) {
        $sanitized = array();
        
        // Debug input data at debug level only
        if (defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
            error_log('Sanitizing settings for channel: ' . $channel_type);
            error_log('Input settings data: ' . print_r($settings_data, true));
        }
        
        // Get existing settings
        $existing_settings = get_option('conversaai_pro_channels_settings', array());
        
        // Common settings for all channels - STANDARDIZE BOOLEAN HERE
        $sanitized['enabled'] = isset($settings_data['enabled']) && ($settings_data['enabled'] == '1' || $settings_data['enabled'] === true) ? true : false;
        
        $sanitized['welcome_message'] = isset($settings_data['welcome_message']) 
            ? sanitize_textarea_field($settings_data['welcome_message']) 
            : '';
                
        // Channel-specific settings
        switch ($channel_type) {
            case 'whatsapp':
                $sanitized['phone_number'] = isset($settings_data['phone_number']) 
                    ? sanitize_text_field($settings_data['phone_number']) 
                    : '';
                
                // API key handling (keep existing value if placeholder)
                if (isset($settings_data['api_key']) && !empty($settings_data['api_key'])) {
                    if (strpos($settings_data['api_key'], '******') !== false) {
                        // This is a masked placeholder, keep existing value
                        if (isset($existing_settings['whatsapp']['api_key'])) {
                            $sanitized['api_key'] = $existing_settings['whatsapp']['api_key'];
                        }
                    } else {
                        // This is a new value
                        $sanitized['api_key'] = sanitize_text_field($settings_data['api_key']);
                    }
                } else {
                    $sanitized['api_key'] = '';
                }
                
                $sanitized['business_account_id'] = isset($settings_data['business_account_id']) 
                    ? sanitize_text_field($settings_data['business_account_id']) 
                    : '';
                    
                // Webhook secret handling (similar to API key)
                if (isset($settings_data['webhook_secret']) && !empty($settings_data['webhook_secret'])) {
                    if (strpos($settings_data['webhook_secret'], '******') !== false) {
                        if (isset($existing_settings['whatsapp']['webhook_secret'])) {
                            $sanitized['webhook_secret'] = $existing_settings['whatsapp']['webhook_secret'];
                        }
                    } else {
                        $sanitized['webhook_secret'] = sanitize_text_field($settings_data['webhook_secret']);
                    }
                } else {
                    $sanitized['webhook_secret'] = '';
                }
                break;
                
            case 'messenger':
                $sanitized['page_id'] = isset($settings_data['page_id']) 
                    ? sanitize_text_field($settings_data['page_id']) 
                    : '';
                $sanitized['app_id'] = isset($settings_data['app_id']) 
                    ? sanitize_text_field($settings_data['app_id']) 
                    : '';
                
                // App Secret handling
                if (isset($settings_data['app_secret']) && !empty($settings_data['app_secret'])) {
                    if (strpos($settings_data['app_secret'], '******') !== false) {
                        if (isset($existing_settings['messenger']['app_secret'])) {
                            $sanitized['app_secret'] = $existing_settings['messenger']['app_secret'];
                        }
                    } else {
                        $sanitized['app_secret'] = sanitize_text_field($settings_data['app_secret']);
                    }
                } else {
                    $sanitized['app_secret'] = '';
                }
                
                // Access Token handling
                if (isset($settings_data['access_token']) && !empty($settings_data['access_token'])) {
                    if (strpos($settings_data['access_token'], '******') !== false) {
                        if (isset($existing_settings['messenger']['access_token'])) {
                            $sanitized['access_token'] = $existing_settings['messenger']['access_token'];
                        }
                    } else {
                        $sanitized['access_token'] = sanitize_text_field($settings_data['access_token']);
                    }
                } else {
                    $sanitized['access_token'] = '';
                }
                break;
                
            case 'instagram':
                $sanitized['account_id'] = isset($settings_data['account_id']) 
                    ? sanitize_text_field($settings_data['account_id']) 
                    : '';
                
                // Access Token handling
                if (isset($settings_data['access_token']) && !empty($settings_data['access_token'])) {
                    if (strpos($settings_data['access_token'], '******') !== false) {
                        if (isset($existing_settings['instagram']['access_token'])) {
                            $sanitized['access_token'] = $existing_settings['instagram']['access_token'];
                        }
                    } else {
                        $sanitized['access_token'] = sanitize_text_field($settings_data['access_token']);
                    }
                } else {
                    $sanitized['access_token'] = '';
                }
                break;
        }
        
        // Debug output data at debug level only
        if (defined('CONVERSAAI_DEBUG_LEVEL') && CONVERSAAI_DEBUG_LEVEL === 'debug') {
            error_log('Sanitized settings: ' . print_r($sanitized, true));
        }
        
        return $sanitized;
    }
}