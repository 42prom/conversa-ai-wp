<?php
/**
 * Instagram messaging channel implementation.
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
 * Instagram channel class.
 *
 * Implements Instagram messaging API.
 *
 * @since      1.0.0
 */
class Instagram_Channel implements Messaging_Channel {

    /**
     * Instagram Graph API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The Instagram API endpoint.
     */
    private $api_endpoint = 'https://graph.facebook.com/v16.0';

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
        $this->logger->info('Initialized Instagram channel', array(
            'enabled' => $this->enabled,
            'has_account_id' => !empty($settings['account_id']),
            'has_token' => !empty($settings['access_token']),
        ));
        
        return true;
    }

    /**
     * Send a message to an Instagram recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient ID (Instagram user ID).
     * @param    string    $message         The message to send.
     * @param    array     $options         Additional options for the message.
     * @return   array     Response data with message ID and other info.
     */
    public function send_message($recipient_id, $message, $options = array()) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Instagram channel is not available or properly configured'
            );
        }
        
        // Build the API request
        $endpoint = $this->api_endpoint . '/' . $this->settings['account_id'] . '/messages';
        
        // Determine message type (text, media, etc.)
        $message_type = isset($options['type']) ? $options['type'] : 'text';
        
        // Build message payload
        $payload = array(
            'recipient' => array(
                'id' => $recipient_id
            ),
            'message_type' => 'RESPONSE'
        );
        
        // Add message content based on type
        if ($message_type === 'media') {
            // Media message
            $media_url = isset($options['media_url']) ? $options['media_url'] : '';
            $media_type = isset($options['media_type']) ? $options['media_type'] : 'image';
            
            if (empty($media_url)) {
                return array(
                    'success' => false,
                    'error' => 'Media URL is required for media messages'
                );
            }
            
            $payload['message'] = array(
                'attachment' => array(
                    'type' => $media_type,
                    'payload' => array(
                        'url' => $media_url,
                        'is_reusable' => false
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
            $endpoint . '?access_token=' . urlencode($this->settings['access_token']),
            $payload,
            array(
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('Instagram API error', array(
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
            $this->logger->error('Instagram API returned error', array(
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
     * Process incoming webhook data from Instagram.
     *
     * @since    1.0.0
     * @param    array    $data    The webhook data to process.
     * @return   array    Processing result.
     */
    public function process_webhook($data) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Instagram channel is not available'
            );
        }
        
        // Check if this is a verification request
        if (isset($data['hub.mode']) && $data['hub.mode'] === 'subscribe') {
            return $this->handle_verification_request($data);
        }
        
        // For now, just log the webhook data
        $this->logger->info('Received Instagram webhook data', array(
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
        $mode = $data['hub_mode'] ?? '';
        $token = $data['hub_verify_token'] ?? '';
        $challenge = $data['hub_challenge'] ?? '';
        
        // Get app secret from messenger settings
        $app_secret = '';
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        if (isset($channels_settings['messenger']['app_secret'])) {
            $app_secret = $channels_settings['messenger']['app_secret'];
        }
        
        if ($mode !== 'subscribe' || $token !== $app_secret) {
            $this->logger->warning('Invalid webhook verification attempt', array(
                'mode' => $mode,
                'token_valid' => $token === $app_secret
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
        // Instagram uses the same security mechanism as Facebook Messenger
        // Get app secret from messenger settings
        $app_secret = '';
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        if (isset($channels_settings['messenger']['app_secret'])) {
            $app_secret = $channels_settings['messenger']['app_secret'];
        }
        
        if (empty($signature) || empty($body) || empty($app_secret)) {
            return false;
        }
        
        // Extract the signature value
        $signature_parts = explode('=', $signature);
        if (count($signature_parts) !== 2 || $signature_parts[0] !== 'sha1') {
            return false;
        }
        
        $expected_signature = hash_hmac('sha1', $body, $app_secret);
        $actual_signature = $signature_parts[1];
        
        return hash_equals($expected_signature, $actual_signature);
    }

    /**
     * Test the Instagram connection.
     *
     * @since    1.0.0
     * @return   array    Result of the connection test.
     */
    public function test_connection() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Instagram channel is not properly configured. Please ensure you have entered all required fields.'
            );
        }
        
        // First, check if we have all the required settings
        if (empty($this->settings['account_id']) || !is_numeric($this->settings['account_id'])) {
            return array(
                'success' => false,
                'error' => 'Instagram Account ID must be a valid numeric ID'
            );
        }
        
        if (empty($this->settings['access_token'])) {
            return array(
                'success' => false,
                'error' => 'Access token is required'
            );
        }
        
        // Test the connection by fetching the Instagram account details
        $endpoint = $this->api_endpoint . '/' . $this->settings['account_id'] . '?fields=username,name,profile_picture_url&access_token=' . urlencode($this->settings['access_token']);
        
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
            
            if (isset($response_body['error'])) {
                return array(
                    'success' => false,
                    'error' => $response_body['error']['message'] ?? 'Unknown API error'
                );
            }
            
            // Check if the account ID matches
            if (!isset($response_body['id']) || $response_body['id'] !== $this->settings['account_id']) {
                return array(
                    'success' => false,
                    'error' => 'Account ID mismatch'
                );
            }
            
            // Successfully fetched account details
            return array(
                'success' => true,
                'details' => array(
                    'username' => $response_body['username'] ?? '',
                    'name' => $response_body['name'] ?? '',
                    'id' => $response_body['id'] ?? '',
                    'status' => 'Connected',
                )
            );
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
        return __('Instagram', 'conversaai-pro-wp');
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string   The channel identifier.
     */
    public function get_id() {
        return 'instagram';
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled && 
               !empty($this->settings['account_id']) && 
               !empty($this->settings['access_token']);
    }
}