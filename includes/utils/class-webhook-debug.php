<?php
/**
 * Webhook debugging utility.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/utils
 */

namespace ConversaAI_Pro_WP\Utils;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Webhook debugging utility class.
 *
 * Provides helper methods for debugging webhook issues.
 *
 * @since      1.0.0
 */
class Webhook_Debug {

    /**
     * Log webhook verification request details.
     *
     * @since    1.0.0
     * @param    string    $channel_id    The channel identifier.
     * @param    array     $params        The webhook parameters.
     * @param    array     $headers       The request headers.
     */
    public static function log_verification_request($channel_id, $params, $headers = array()) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/conversaai-pro-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/webhook-' . date('Y-m-d') . '.log';
        
        $log_data = date('Y-m-d H:i:s') . " - Webhook verification request for channel: $channel_id\n";
        $log_data .= "Parameters: " . print_r($params, true) . "\n";
        $log_data .= "Headers: " . print_r($headers, true) . "\n";
        $log_data .= "--------\n";
        
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }

    /**
     * Test webhook configuration.
     *
     * @since    1.0.0
     * @param    string    $channel_id    The channel identifier.
     * @param    string    $verify_token  The verify token to test.
     * @return   string    Test result message.
     */
    public static function test_webhook_config($channel_id, $verify_token) {
        $site_url = site_url();
        $webhook_url = rest_url("conversaai/v1/webhook/$channel_id");
        
        $test_result = "Webhook Configuration Test\n";
        $test_result .= "------------------------\n";
        $test_result .= "Channel: $channel_id\n";
        $test_result .= "Webhook URL: $webhook_url\n";
        $test_result .= "Verify Token: $verify_token\n\n";
        
        // Check if site is using HTTPS
        $test_result .= "HTTPS: " . (strpos($site_url, 'https://') === 0 ? "YES ✓" : "NO ✗ (Facebook requires HTTPS)") . "\n";
        
        // Check REST API accessibility
        $response = wp_remote_get($webhook_url);
        $test_result .= "REST API Accessible: " . (!is_wp_error($response) ? "YES ✓" : "NO ✗") . "\n";
        
        // Suggest verification URL
        $test_url = $webhook_url . "?hub_mode=subscribe&hub_verify_token=" . urlencode($verify_token) . "&hub_challenge=test123";
        $test_result .= "\nTo test manually, visit:\n$test_url\n";
        $test_result .= "You should see 'test123' as the response if webhook verification is working.\n";
        
        return $test_result;
    }
}