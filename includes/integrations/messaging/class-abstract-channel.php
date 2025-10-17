<?php
/**
 * Abstract messaging channel class.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/messaging
 */

namespace ConversaAI_Pro_WP\Integrations\Messaging;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Conversation_Manager;
use ConversaAI_Pro_WP\Core\Router;
use ConversaAI_Pro_WP\Utils\API_Request;
use ConversaAI_Pro_WP\Utils\Logger;

/**
 * Abstract messaging channel class.
 *
 * Base class for all messaging channels.
 *
 * @since      1.0.0
 */
abstract class Abstract_Channel {

    /**
     * The channel identifier.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $channel_id    The channel identifier.
     */
    protected $channel_id;

    /**
     * The channel display name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $display_name    The channel display name.
     */
    protected $display_name;

    /**
     * The channel status (enabled/disabled).
     *
     * @since    1.0.0
     * @access   protected
     * @var      bool    $enabled    Whether the channel is enabled.
     */
    protected $enabled = false;

    /**
     * The channel settings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $settings    The channel-specific settings.
     */
    protected $settings = array();

    /**
     * The router instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Router    $router    The router instance.
     */
    protected $router;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    The logger instance.
     */
    protected $logger;

    /**
     * Initialize the channel with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Channel-specific settings.
     */
    public function initialize($settings = array()) {
        $this->settings = $settings;
        $this->enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        $this->router = new Router();
        $this->logger = new Logger();
    }

    /**
     * Check if the channel is enabled.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is enabled.
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string    The channel identifier.
     */
    public function get_channel_id() {
        return $this->channel_id;
    }

    /**
     * Get the channel display name.
     *
     * @since    1.0.0
     * @return   string    The channel display name.
     */
    public function get_display_name() {
        return $this->display_name;
    }

    /**
     * Get the channel settings.
     *
     * @since    1.0.0
     * @return   array    The channel settings.
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Update channel settings.
     *
     * @since    1.0.0
     * @param    array    $settings    New settings to apply.
     * @return   bool     Whether the settings were updated successfully.
     */
    public function update_settings($settings) {
        $this->settings = array_merge($this->settings, $settings);
        $this->enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : $this->enabled;
        
        // Save settings to database
        $all_channels_settings = get_option('conversaai_pro_channels_settings', array());
        $all_channels_settings[$this->channel_id] = $this->settings;
        update_option('conversaai_pro_channels_settings', $all_channels_settings);
        
        return true;
    }

    /**
     * Process an incoming message from the channel.
     *
     * @since    1.0.0
     * @param    array    $message_data    The message data from the channel.
     * @return   array|bool    Response data or false on failure.
     */
    public function process_incoming_message($message_data) {
        if (!$this->is_enabled() || empty($message_data)) {
            return false;
        }
        
        try {
            // Extract required information from the message
            $sender_id = $this->extract_sender_id($message_data);
            $message_text = $this->extract_message_text($message_data);
            $conversation_id = $this->get_conversation_id($sender_id);
            
            if (empty($message_text)) {
                return false;
            }
            
            // Initialize or load the conversation
            $conversation = new Conversation_Manager($conversation_id);
            $conversation->set_channel($this->channel_id);
            
            // Add the user message to the conversation
            $conversation->add_message(array(
                'role' => 'user',
                'content' => $message_text,
            ));
            
            // Get the conversation history
            $history = $conversation->get_conversation_history();
            
            // Process the message using the router
            $response = $this->router->process_query($message_text, $history);
            
            // Add the assistant response to the conversation
            $conversation->add_message(array(
                'role' => 'assistant',
                'content' => $response['answer'],
            ));
            
            // Send the response back to the channel
            $this->send_response($sender_id, $response['answer']);
            
            return array(
                'success' => true,
                'message' => $response['answer'],
                'source' => $response['source'],
            );
        } catch (\Exception $e) {
            $this->logger->error('Error processing message from ' . $this->display_name . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract the sender ID from the message data.
     *
     * @since    1.0.0
     * @param    array    $message_data    The message data from the channel.
     * @return   string    The sender ID.
     */
    abstract protected function extract_sender_id($message_data);

    /**
     * Extract the message text from the message data.
     *
     * @since    1.0.0
     * @param    array    $message_data    The message data from the channel.
     * @return   string    The message text.
     */
    abstract protected function extract_message_text($message_data);

    /**
     * Send a response message to the channel.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient ID.
     * @param    string    $message         The message to send.
     * @return   bool     Whether the message was sent successfully.
     */
    abstract public function send_response($recipient_id, $message);

    /**
     * Verify the channel configuration.
     *
     * @since    1.0.0
     * @return   bool|array    True if valid, or array of errors if invalid.
     */
    abstract public function verify_configuration();

    /**
     * Register any webhooks required by the channel.
     *
     * @since    1.0.0
     * @return   bool     Whether the webhooks were registered successfully.
     */
    abstract public function register_webhooks();

    /**
     * Get a conversation ID for a sender.
     *
     * @since    1.0.0
     * @param    string    $sender_id    The sender ID.
     * @return   string    The conversation ID.
     */
    protected function get_conversation_id($sender_id) {
        // Format: channel_[channel_id]_user_[sender_id]
        return 'channel_' . $this->channel_id . '_user_' . $sender_id;
    }
}