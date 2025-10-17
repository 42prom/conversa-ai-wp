<?php
/**
 * Messaging Channel Manager.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/messaging
 */

namespace ConversaAI_Pro_WP\Integrations\Messaging;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Utils\Logger;

/**
 * Channel Manager class.
 *
 * Manages all messaging channels and coordinates message routing.
 *
 * @since      1.0.0
 */
class Channel_Manager {

    /**
     * Available messaging channels.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $channels    The registered channels.
     */
    private $channels = array();

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Utils\Logger    $logger    Logger instance.
     */
    private $logger;

    /**
     * Singleton instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Integrations\Messaging\Channel_Manager    $instance    Singleton instance.
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @since    1.0.0
     * @return   \ConversaAI_Pro_WP\Integrations\Messaging\Channel_Manager    Singleton instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    private function __construct() {
        $this->logger = new Logger();
        $this->register_channels();
        $this->initialize_channels();
        
        // Register REST API endpoints for webhooks
        add_action('rest_api_init', array($this, 'register_webhooks'));
    }

    /**
     * Register available messaging channels.
     *
     * @since    1.0.0
     */
    private function register_channels() {
        // Register WhatsApp channel
        $this->channels['whatsapp'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\Messaging\WhatsApp_Channel',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-whatsapp-channel.php',
            'instance' => null,
        );
        
        // Register Messenger channel
        $this->channels['messenger'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\Messaging\Messenger_Channel',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-messenger-channel.php',
            'instance' => null,
        );
        
        // Register Instagram channel
        $this->channels['instagram'] = array(
            'class' => '\ConversaAI_Pro_WP\Integrations\Messaging\Instagram_Channel',
            'file' => CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-instagram-channel.php',
            'instance' => null,
        );
        
        // Allow adding more channels via filter
        $this->channels = apply_filters('conversaai_pro_register_messaging_channels', $this->channels);
    }

    /**
     * Initialize messaging channels with settings.
     *
     * @since    1.0.0
     */
    private function initialize_channels() {
        // Get channels settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        foreach ($this->channels as $channel_id => $channel_info) {
            // Load the channel file if needed
            if (file_exists($channel_info['file']) && !class_exists($channel_info['class'])) {
                require_once $channel_info['file'];
            }
            
            // Create channel instance
            if (class_exists($channel_info['class'])) {
                try {
                    $this->channels[$channel_id]['instance'] = new $channel_info['class']();
                    
                    // Initialize with settings
                    $settings = isset($channels_settings[$channel_id]) ? $channels_settings[$channel_id] : array();
                    $this->channels[$channel_id]['instance']->initialize($settings);
                    
                    $this->logger->info('Initialized channel', array(
                        'channel' => $channel_id,
                        'enabled' => $settings['enabled'] ?? false
                    ));
                } catch (\Exception $e) {
                    $this->logger->error('Error initializing channel', array(
                        'channel' => $channel_id,
                        'error' => $e->getMessage()
                    ));
                }
            } else {
                $this->logger->error('Failed to load channel class', array(
                    'channel' => $channel_id,
                    'class' => $channel_info['class']
                ));
            }
        }
    }

    /**
     * Get a specific channel instance.
     *
     * @since    1.0.0
     * @param    string    $channel_id    The channel identifier.
     * @return   Messaging_Channel|null   The channel instance or null if not found.
     */
    public function get_channel($channel_id) {
        if (isset($this->channels[$channel_id]) && isset($this->channels[$channel_id]['instance'])) {
            return $this->channels[$channel_id]['instance'];
        }
        
        return null;
    }

    /**
     * Get all available channels.
     *
     * @since    1.0.0
     * @return   array    List of available channels.
     */
    public function get_available_channels() {
        $available = array();
        
        foreach ($this->channels as $id => $info) {
            if (isset($info['instance']) && $info['instance']->is_available()) {
                $available[$id] = $info['instance']->get_name();
            }
        }
        
        return $available;
    }

    /**
     * Send a message through a specific channel.
     *
     * @since    1.0.0
     * @param    string    $channel_id       The channel to use.
     * @param    string    $recipient_id     The recipient identifier.
     * @param    string    $message          The message to send.
     * @param    array     $options          Additional options for the message.
     * @return   array     Response data.
     */
    public function send_message($channel_id, $recipient_id, $message, $options = array()) {
        $channel = $this->get_channel($channel_id);
        
        if (!$channel) {
            return array(
                'success' => false,
                'error' => sprintf(__('Channel "%s" not found', 'conversaai-pro-wp'), $channel_id)
            );
        }
        
        if (!$channel->is_available()) {
            return array(
                'success' => false,
                'error' => sprintf(__('Channel "%s" is not available', 'conversaai-pro-wp'), $channel_id)
            );
        }
        
        // Send the message through the channel
        $result = $channel->send_message($recipient_id, $message, $options);
        
        // Log the result
        $this->logger->info(
            $result['success'] ? 'Message sent successfully' : 'Failed to send message',
            array(
                'channel' => $channel_id,
                'recipient' => $recipient_id,
                'success' => $result['success'],
                'error' => isset($result['error']) ? $result['error'] : null
            )
        );
        
        return $result;
    }

    /**
     * Process webhook data for a specific channel.
     *
     * @since    1.0.0
     * @param    string    $channel_id    The channel identifier.
     * @param    array     $data          The webhook data.
     * @param    string    $signature     Optional. The webhook signature for verification.
     * @param    string    $raw_body      Optional. The raw request body for verification.
     * @return   array     Processing result.
     */
    public function process_webhook($channel_id, $data, $signature = '', $raw_body = '') {
        $channel = $this->get_channel($channel_id);
        
        if (!$channel) {
            return array(
                'success' => false,
                'error' => sprintf(__('Channel "%s" not found', 'conversaai-pro-wp'), $channel_id)
            );
        }
        
        // Verify webhook signature if provided
        if (!empty($signature) && !empty($raw_body) && !$channel->verify_webhook($signature, $raw_body)) {
            $this->logger->warning('Invalid webhook signature', array(
                'channel' => $channel_id
            ));
            
            return array(
                'success' => false,
                'error' => 'Invalid webhook signature'
            );
        }
        
        // Process the webhook data
        $result = $channel->process_webhook($data);
        
        // Log the result
        $this->logger->info(
            $result['success'] ? 'Webhook processed successfully' : 'Failed to process webhook',
            array(
                'channel' => $channel_id,
                'success' => $result['success'],
                'error' => isset($result['error']) ? $result['error'] : null
            )
        );
        
        return $result;
    }

    /**
     * Register REST API endpoints for webhooks.
     *
     * @since    1.0.0
     */
    public function register_webhooks() {
        register_rest_route('conversaai/v1', '/webhook/(?P<channel>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_webhook_verification'),
            'permission_callback' => '__return_true',
            'args' => array(
                'hub_mode' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'hub_verify_token' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'hub_challenge' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        register_rest_route('conversaai/v1', '/webhook/(?P<channel>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook_event'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle webhook verification requests (GET).
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response   Response object.
     */
    public function handle_webhook_verification($request) {
        $channel_id = $request->get_param('channel');
        
        // Debug log to verify parameters
        error_log('ConversaAI Webhook verification triggered for: ' . $channel_id);
        error_log('Parameters: ' . print_r($request->get_params(), true));
        
        if (!isset($this->channels[$channel_id])) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => 'Invalid channel'
            ), 404);
        }
        
        // Process the verification request
        $result = $this->process_webhook($channel_id, $request->get_params());
        
        if (!$result['success']) {
            return new \WP_REST_Response($result, 400);
        }
        
        // For verification requests, return the challenge code directly as plain text
        if (isset($result['challenge'])) {
            // Return plain text response for Facebook verification
            $response = new \WP_REST_Response($result['challenge'], 200);
            $response->set_headers(['Content-Type' => 'text/plain']);
            return $response;
        }
        
        return new \WP_REST_Response($result, 200);
    }

    /**
     * Handle webhook event requests (POST).
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response   Response object.
     */
    public function handle_webhook_event($request) {
        $channel_id = $request->get_param('channel');
        $data = $request->get_json_params();
        
        if (empty($data)) {
            $data = $request->get_body_params();
        }
        
        if (!isset($this->channels[$channel_id])) {
            return new \WP_REST_Response(array(
                'success' => false,
                'error' => 'Invalid channel'
            ), 404);
        }
        
        // Get signature from headers for verification
        $signature = $request->get_header('x-hub-signature');
        
        // Get raw body for signature verification
        $raw_body = $request->get_body();
        
        // Process the webhook event
        $result = $this->process_webhook($channel_id, $data, $signature, $raw_body);
        
        if (!$result['success']) {
            return new \WP_REST_Response($result, 400);
        }
        
        return new \WP_REST_Response(array('success' => true), 200);
    }
}