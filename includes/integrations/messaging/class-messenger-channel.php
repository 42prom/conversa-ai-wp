<?php
/**
 * Facebook Messenger channel implementation.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/messaging
 */

namespace ConversaAI_Pro_WP\Integrations\Messaging;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Utils\API_Request;
use ConversaAI_Pro_WP\Utils\Logger;

/**
 * Messenger channel class.
 *
 * Implements Facebook Messenger API messaging.
 *
 * @since      1.0.0
 */
class Messenger_Channel implements Messaging_Channel {

    /**
     * Messenger API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The Messenger API endpoint.
     */
    private $api_endpoint = 'https://graph.facebook.com/v16.0/me/messages';

    /**
     * Channel settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Channel settings.
     */
    private $settings = array();

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Utils\Logger    $logger    Logger instance.
     */
    private $logger;

    /**
     * Whether the channel is enabled.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $enabled    Whether the channel is enabled.
     */
    private $enabled = false;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->logger = new Logger();
    }

    /**
     * Initialize the channel with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Channel-specific settings.
     * @return   bool     Whether initialization was successful.
     */
    public function initialize($settings) {
        $this->settings = $settings;
        $this->enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        
        // Log initialization
        $this->logger->info('Initialized Messenger channel', array(
            'enabled' => $this->enabled,
            'has_page_id' => !empty($settings['page_id']),
            'has_app_id' => !empty($settings['app_id'])
        ));
        
        return true;
    }

    /**
     * Refresh the Facebook access token before it expires.
     *
     * @since    1.0.0
     * @return   bool     Whether the token was refreshed successfully.
     */
    private function refresh_access_token() {
        if (empty($this->settings['app_id']) || empty($this->settings['app_secret']) || empty($this->settings['access_token'])) {
            $this->logger->error('Cannot refresh token - missing credentials');
            return false;
        }
        
        $endpoint = "https://graph.facebook.com/v16.0/oauth/access_token";
        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->settings['app_id'],
            'client_secret' => $this->settings['app_secret'],
            'fb_exchange_token' => $this->settings['access_token']
        ];
        
        $api_request = new \ConversaAI_Pro_WP\Utils\API_Request();
        $url = add_query_arg($params, $endpoint);
        
        $response = $api_request->get($url);
        
        if (is_wp_error($response)) {
            $this->logger->error('Token refresh failed', ['error' => $response->get_error_message()]);
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['access_token'])) {
            $this->logger->error('Invalid response for token refresh', ['response' => $body]);
            return false;
        }
        
        // Update the token in settings
        $this->settings['access_token'] = $body['access_token'];
        $this->settings['token_refreshed_at'] = time();
        
        // Save to database
        $all_channels_settings = get_option('conversaai_pro_channels_settings', array());
        $all_channels_settings['messenger'] = $this->settings;
        update_option('conversaai_pro_channels_settings', $all_channels_settings);
        
        $this->logger->info('Facebook access token refreshed successfully');
        return true;
    }

    /**
     * Send a message to a Messenger recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient PSID.
     * @param    string    $message         The message to send.
     * @param    array     $options         Additional options for the message.
     * @return   array     Response data with message ID and other info.
     */
    public function send_message($recipient_id, $message, $options = array()) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not available or properly configured'
            );
        }
        
        // Build the API request
        $endpoint = $this->api_endpoint . '?access_token=' . urlencode($this->settings['access_token']);
        
        // Determine message type (text, template, etc.)
        $message_type = isset($options['type']) ? $options['type'] : 'text';
        
        // Build message payload
        $payload = array(
            'recipient' => array(
                'id' => $recipient_id
            ),
            'messaging_type' => 'RESPONSE'
        );
        
        // Add message content based on type
        if ($message_type === 'template') {
            // Template message
            $template_type = isset($options['template_type']) ? $options['template_type'] : 'generic';
            $template_data = isset($options['template_data']) ? $options['template_data'] : array();
            
            $payload['message'] = array(
                'attachment' => array(
                    'type' => 'template',
                    'payload' => array(
                        'template_type' => $template_type,
                        'elements' => $template_data
                    )
                )
            );
        } elseif ($message_type === 'quick_replies') {
            // Quick replies
            $quick_replies = isset($options['quick_replies']) ? $options['quick_replies'] : array();
            
            $payload['message'] = array(
                'text' => $message,
                'quick_replies' => $quick_replies
            );
        } else {
            // Regular text message
            $payload['message'] = array(
                'text' => $message
            );
        }
        
        // Send the request
        $api_request = new API_Request();
        $response = $api_request->post(
            $endpoint,
            $payload,
            array(
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('Messenger API error', array(
                'error' => $response->get_error_message(),
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            $this->logger->error('Messenger API returned error', array(
                'error' => $response_body['error'],
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        if (isset($response_body['message_id'])) {
            return array(
                'success' => true,
                'message_id' => $response_body['message_id'],
                'recipient_id' => $response_body['recipient_id'] ?? $recipient_id
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Invalid API response'
        );
    }

    /**
     * Process incoming webhook data from Messenger.
     *
     * @since    1.0.0
     * @param    array    $data    The webhook data to process.
     * @return   array    Processing result.
     */
    public function process_webhook($data) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not available'
            );
        }
        
        // Check if this is a verification request
        if (isset($data['hub_mode']) && $data['hub_mode'] === 'subscribe') {
            return $this->handle_verification_request($data);
        }
        
        // For now, just log the webhook data
        $this->logger->info('Received Messenger webhook data', array(
            'data' => json_encode($data)
        ));
        
        return array(
            'success' => true,
            'message' => 'Webhook received'
        );
    }

    /**
     * Handle webhook verification request.
     *
     * @since    1.0.0
     * @param    array    $data    The verification data.
     * @return   array    Verification result.
     */
    private function handle_verification_request($data) {
        // Facebook sends parameters with underscores, not dots
        $mode = isset($data['hub_mode']) ? $data['hub_mode'] : '';
        $token = isset($data['hub_verify_token']) ? $data['hub_verify_token'] : '';
        $challenge = isset($data['hub_challenge']) ? $data['hub_challenge'] : '';
        
        $this->logger->info('Messenger verification attempt', array(
            'mode' => $mode,
            'token_received' => $token,
            'challenge' => $challenge,
            'our_token' => $this->settings['app_secret']
        ));
        
        if ($mode !== 'subscribe' || $token !== $this->settings['app_secret']) {
            $this->logger->warning('Invalid webhook verification attempt', array(
                'mode' => $mode,
                'token_valid' => $token === $this->settings['app_secret']
            ));
            
            return array(
                'success' => false,
                'error' => 'Verification failed'
            );
        }
        
        $this->logger->info('Webhook verification successful');
        
        return array(
            'success' => true,
            'challenge' => $challenge
        );
    }

    /**
     * Verify webhook signature/security.
     *
     * @since    1.0.0
     * @param    string    $signature     The signature from the webhook headers.
     * @param    string    $body          The raw request body.
     * @return   bool      Whether the webhook is valid.
     */
    public function verify_webhook($signature, $body) {
        // Facebook uses x-hub-signature headers
        if (empty($signature) || empty($body) || empty($this->settings['app_secret'])) {
            return false;
        }
        
        // Extract the signature value
        $signature_parts = explode('=', $signature);
        if (count($signature_parts) !== 2 || $signature_parts[0] !== 'sha1') {
            return false;
        }
        
        $expected_signature = hash_hmac('sha1', $body, $this->settings['app_secret']);
        $actual_signature = $signature_parts[1];
        
        return hash_equals($expected_signature, $actual_signature);
    }

    /**
     * Test the Messenger connection.
     *
     * @since    1.0.0
     * @return   array    Result of the connection test.
     */
    public function test_connection() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not properly configured. Please ensure you have entered all required fields.'
            );
        }
        
        // First, check if we have all the required settings
        if (empty($this->settings['page_id']) || !is_numeric($this->settings['page_id'])) {
            return array(
                'success' => false,
                'error' => 'Page	xmax_id must be a valid numeric ID'
            );
        }
        
        if (empty($this->settings['app_id']) || !is_numeric($this->settings['app_id'])) {
            return array(
                'success' => false,
                'error' => 'App ID must be a valid numeric ID'
            );
        }
        
        if (empty($this->settings['access_token'])) {
            return array(
                'success' => false,
                'error' => 'Access token is required'
            );
        }
        
        // Test the connection by fetching the page details
        $endpoint = "https://graph.facebook.com/v16.0/{$this->settings['page_id']}?fields=name,id,category&access_token={$this->settings['access_token']}";
        
        $api_request = new API_Request();
        
        try {
            $response = $api_request->get($endpoint);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => 'API Error: ' . $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($status_code !== 200) {
                $error_message = isset($response_body['error']['message']) ? 
                    $response_body['error']['message'] : 
                    'Unknown error. Status code: ' . $status_code;
                
                return array(
                    'success' => false,
                    'error' => $error_message
                );
            }
            
            // Check if the page ID matches
            if (!isset($response_body['id']) || $response_body['id'] !== $this->settings['page_id']) {
                return array(
                    'success' => false,
                    'error' => 'Page ID mismatch'
                );
            }
            
            // Initialize result array
            $result = array(
                'success' => true,
                'details' => array(
                    'name' => $response_body['name'] ?? '',
                    'id' => $response_body['id'] ?? '',
                    'category' => $response_body['category'] ?? '',
                    'status' => 'Connected',
                )
            );
            
            // Check token expiration
            if (isset($this->settings['token_refreshed_at'])) {
                $token_age = time() - $this->settings['token_refreshed_at'];
                // If token is older than 30 days, try to refresh
                if ($token_age > 30 * 24 * 60 * 60) {
                    $this->logger->info('Token age detected as ' . round($token_age/86400) . ' days, attempting refresh');
                    $token_refreshed = $this->refresh_access_token();
                    
                    if ($token_refreshed) {
                        $result['token_refreshed'] = true;
                        $result['details']['token_status'] = 'Refreshed';
                    } else {
                        $result['token_refreshed'] = false;
                        $result['details']['token_status'] = 'Expired - manual refresh needed';
                    }
                } else {
                    $result['details']['token_status'] = 'Valid (' . round($token_age/86400) . ' days old)';
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the channel name.
     *
     * @since    1.0.0
     * @return   string   The channel name.
     */
    public function get_name() {
        return __('Facebook Messenger', 'conversaai-pro-wp');
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string   The channel identifier.
     */
    public function get_id() {
        return 'messenger';
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled && 
               !empty($this->settings['page_id']) && 
               !empty($this->settings['app_id']) && 
               !empty($this->settings['access_token']);
    }
}