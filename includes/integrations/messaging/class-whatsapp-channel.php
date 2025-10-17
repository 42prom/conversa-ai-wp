<?php
/**
 * WhatsApp messaging channel implementation.
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
 * WhatsApp channel class.
 *
 * Implements WhatsApp Business API messaging.
 *
 * @since      1.0.0
 */
class WhatsApp_Channel implements Messaging_Channel {

    /**
     * WhatsApp Business API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The WhatsApp API endpoint.
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
        $this->logger->info('Initialized WhatsApp channel', array(
            'enabled' => $this->enabled,
            'has_phone' => !empty($settings['phone_number']),
            'has_business_account' => !empty($settings['business_account_id'])
        ));
        
        return true;
    }

    /**
     * Send a message to a WhatsApp recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient phone number.
     * @param    string    $message         The message to send.
     * @param    array     $options         Additional options for the message.
     * @return   array     Response data with message ID and other info.
     */
    public function send_message($recipient_id, $message, $options = array()) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not available or properly configured'
            );
        }
        
        // Format recipient (ensure it's in international format)
        $recipient_id = $this->format_phone_number($recipient_id);
        
        // Prepare the message payload
        $endpoint = $this->api_endpoint . '/' . $this->settings['business_account_id'] . '/messages';
        
        // Basic message payload
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient_id,
            'type' => 'text',
            'text' => array(
                'body' => $message
            )
        );
        
        // Send the request
        $api_request = new API_Request();
        $response = $api_request->post(
            $endpoint,
            $payload,
            array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('WhatsApp API error', array(
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
            $this->logger->error('WhatsApp API returned error', array(
                'error' => $response_body['error'],
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        if (isset($response_body['messages']) && isset($response_body['messages'][0]['id'])) {
            return array(
                'success' => true,
                'message_id' => $response_body['messages'][0]['id']
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Invalid API response'
        );
    }

    /**
     * Send a template message to a WhatsApp recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient phone number.
     * @param    string    $template_name   The name of the approved template.
     * @param    array     $parameters      Template parameters.
     * @param    string    $language        The language code, default 'en_US'.
     * @return   array     Response data with message ID and other info.
     */
    public function send_template_message($recipient_id, $template_name, $parameters = [], $language = 'en_US') {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not available or properly configured'
            );
        }
        
        // Format recipient (ensure it's in international format)
        $recipient_id = $this->format_phone_number($recipient_id);
        
        // Prepare the message payload
        $endpoint = $this->api_endpoint . '/' . $this->settings['business_account_id'] . '/messages';
        
        // Format the template components
        $components = $this->format_template_components($parameters);
        
        // Basic message payload for template
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient_id,
            'type' => 'template',
            'template' => array(
                'name' => $template_name,
                'language' => array('code' => $language),
                'components' => $components
            )
        );
        
        // Send the request
        $api_request = new \ConversaAI_Pro_WP\Utils\API_Request();
        $response = $api_request->post(
            $endpoint,
            $payload,
            array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('WhatsApp template API error', array(
                'error' => $response->get_error_message(),
                'recipient' => $recipient_id,
                'template' => $template_name
            ));
            
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            $this->logger->error('WhatsApp API returned error', array(
                'error' => $response_body['error'],
                'recipient' => $recipient_id,
                'template' => $template_name
            ));
            
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        if (isset($response_body['messages']) && isset($response_body['messages'][0]['id'])) {
            return array(
                'success' => true,
                'message_id' => $response_body['messages'][0]['id'],
                'template' => $template_name
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Invalid API response'
        );
    }

    /**
     * Format template components for WhatsApp API.
     *
     * @since    1.0.0
     * @param    array    $parameters    The template parameters.
     * @return   array    Formatted template components.
     */
    private function format_template_components($parameters) {
        $components = [];
        
        // Add header component if provided
        if (!empty($parameters['header'])) {
            $header = [
                'type' => 'header',
                'parameters' => []
            ];
            
            if (isset($parameters['header']['type']) && isset($parameters['header']['value'])) {
                $header['parameters'][] = [
                    'type' => $parameters['header']['type'],
                    'text' => $parameters['header']['value']
                ];
            }
            
            $components[] = $header;
        }
        
        // Add body component with parameters
        if (!empty($parameters['body'])) {
            $body = [
                'type' => 'body',
                'parameters' => []
            ];
            
            foreach ($parameters['body'] as $param) {
                if (isset($param['type']) && isset($param['value'])) {
                    $param_data = ['type' => $param['type']];
                    
                    switch ($param['type']) {
                        case 'text':
                            $param_data['text'] = $param['value'];
                            break;
                        case 'currency':
                            $param_data['currency'] = [
                                'code' => $param['currency_code'] ?? 'USD',
                                'amount' => $param['value']
                            ];
                            break;
                        case 'date_time':
                            $param_data['date_time'] = [
                                'fallback_value' => $param['value']
                            ];
                            break;
                    }
                    
                    $body['parameters'][] = $param_data;
                }
            }
            
            $components[] = $body;
        }
        
        // Add buttons component if provided
        if (!empty($parameters['buttons'])) {
            $buttons = [
                'type' => 'buttons',
                'buttons' => []
            ];
            
            foreach ($parameters['buttons'] as $button) {
                if (isset($button['type']) && $button['type'] === 'quick_reply') {
                    $buttons['buttons'][] = [
                        'type' => 'quick_reply',
                        'text' => $button['text'] ?? 'Button',
                        'payload' => $button['payload'] ?? 'payload'
                    ];
                }
            }
            
            $components[] = $buttons;
        }
        
        return $components;
    }

    /**
     * Process incoming webhook data from WhatsApp.
     *
     * @since    1.0.0
     * @param    array    $data    The webhook data to process.
     * @return   array    Processing result.
     */
    public function process_webhook($data) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not available'
            );
        }
        
        // Check if this is a verification request
        if (isset($data['hub_mode']) && $data['hub_mode'] === 'subscribe') {
            return $this->handle_verification_request($data);
        }
        
        // For now, just log the webhook data
        $this->logger->info('Received WhatsApp webhook data', array(
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
        
        if ($mode !== 'subscribe' || $token !== $this->settings['webhook_secret']) {
            $this->logger->warning('Invalid webhook verification attempt', array(
                'mode' => $mode,
                'token_valid' => $token === $this->settings['webhook_secret']
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
        // WhatsApp/Meta uses x-hub-signature headers
        if (empty($signature) || empty($body) || empty($this->settings['api_key'])) {
            return false;
        }
        
        // Extract the signature value
        $signature_parts = explode('=', $signature);
        if (count($signature_parts) !== 2 || $signature_parts[0] !== 'sha256') {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $body, $this->settings['api_key']);
        $actual_signature = $signature_parts[1];
        
        return hash_equals($expected_signature, $actual_signature);
    }

    /**
     * Test the WhatsApp connection.
     *
     * @since    1.0.0
     * @return   array    Result of the connection test.
     */
    public function test_connection() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not properly configured. Please ensure you have entered all required fields.'
            );
        }
        
        // First, check if we have all the required settings with appropriate values
        if (empty($this->settings['phone_number']) || substr($this->settings['phone_number'], 0, 1) !== '+') {
            return array(
                'success' => false,
                'error' => 'Phone number must be in international format starting with "+"'
            );
        }
        
        if (empty($this->settings['business_account_id']) || !is_numeric($this->settings['business_account_id'])) {
            return array(
                'success' => false,
                'error' => 'Business Account ID must be a valid numeric ID'
            );
        }
        
        // Test the connection by fetching the business profile
        $endpoint = $this->api_endpoint . '/' . $this->settings['phone_number'] . '/whatsapp_business_profile';
        
        $api_request = new API_Request();
        $params = array();
        $headers = array(
            'Authorization' => 'Bearer ' . $this->settings['api_key'],
            'Content-Type' => 'application/json',
        );
        
        try {
            $response = $api_request->get($endpoint, $headers);
            
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
            
            // Check for a valid response
            if (!isset($response_body['data'])) {
                return array(
                    'success' => false,
                    'error' => 'Invalid API response format'
                );
            }
            
            // Successfully fetched business profile
            return array(
                'success' => true,
                'details' => array(
                    'phone_number' => $this->settings['phone_number'],
                    'business_account_id' => $this->settings['business_account_id'],
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
        return __('WhatsApp', 'conversaai-pro-wp');
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string   The channel identifier.
     */
    public function get_id() {
        return 'whatsapp';
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled && 
               !empty($this->settings['phone_number']) && 
               !empty($this->settings['api_key']) && 
               !empty($this->settings['business_account_id']);
    }

    /**
     * Format phone number to ensure it's in international format.
     *
     * @since    1.0.0
     * @param    string    $phone_number    The phone number to format.
     * @return   string    The formatted phone number.
     */
    private function format_phone_number($phone_number) {
        // Remove any non-numeric characters except the plus sign
        $phone_number = preg_replace('/[^\d+]/', '', $phone_number);
        
        // Ensure it starts with a plus sign
        if (substr($phone_number, 0, 1) !== '+') {
            $phone_number = '+' . $phone_number;
        }
        
        return $phone_number;
    }
}