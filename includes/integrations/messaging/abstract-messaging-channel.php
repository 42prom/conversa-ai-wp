<?php
/**
 * Abstract base class for messaging channels.
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
use ConversaAI_Pro_WP\Utils\Logger;

/**
 * Abstract base class for messaging channels.
 *
 * Implements common functionality for all messaging channels.
 *
 * @since      1.0.0
 */
abstract class Abstract_Messaging_Channel implements Messaging_Channel {

    /**
     * Channel settings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $settings    Channel settings.
     */
    protected $settings = array();

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \ConversaAI_Pro_WP\Utils\Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Whether the channel is enabled.
     *
     * @since    1.0.0
     * @access   protected
     * @var      bool    $enabled    Whether the channel is enabled.
     */
    protected $enabled = false;

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
        $this->logger->info('Initialized ' . $this->get_name() . ' channel', array(
            'id' => $this->get_id(),
            'enabled' => $this->enabled
        ));
        
        return true;
    }

    /**
     * Process message from channel and get AI response.
     *
     * @since    1.0.0
     * @param    string    $sender_id       The sender's identifier.
     * @param    string    $message_text    The message text.
     * @param    string    $session_id      Optional. The conversation session ID.
     * @return   array     The response data.
     */
    protected function process_message_with_ai($sender_id, $message_text, $session_id = null) {
        if (empty($session_id)) {
            // Generate a session ID based on the channel and sender
            $session_id = 'channel_' . $this->get_id() . '_' . md5($sender_id);
        }
        
        // Initialize or load the conversation
        $conversation = new Conversation_Manager($session_id, $this->get_id());
        
        // Add the user message to the conversation
        $conversation->add_message(array(
            'role' => 'user',
            'content' => $message_text,
            'sender_id' => $sender_id,
        ));
        
        // Get the conversation history
        $history = $conversation->get_conversation_history();
        
        // Process the message using the router
        $router = new Router();
        $response = $router->process_query($message_text, $history);
        
        // Add the assistant response to the conversation
        $conversation->add_message(array(
            'role' => 'assistant',
            'content' => $response['answer'],
        ));
        
        // Update metadata
        $conversation->update_metadata(array(
            'last_activity' => current_time('mysql'),
            'channel' => $this->get_id(),
            'sender_id' => $sender_id,
        ));
        
        // Return the response
        return array(
            'message' => $response['answer'],
            'session_id' => $session_id,
            'source' => $response['source'] ?? 'ai',
        );
    }

    /**
     * Handle an incoming message.
     *
     * @since    1.0.0
     * @param    string    $sender_id       The sender's identifier.
     * @param    string    $message_text    The message text.
     * @param    array     $metadata        Additional metadata.
     * @return   bool      Whether the message was handled successfully.
     */
    protected function handle_incoming_message($sender_id, $message_text, $metadata = array()) {
        if (!$this->enabled) {
            $this->logger->info('Message received but channel is disabled', array(
                'channel' => $this->get_id(),
                'sender' => $sender_id
            ));
            return false;
        }
        
        try {
            // Log incoming message
            $this->logger->info('Incoming message received', array(
                'channel' => $this->get_id(),
                'sender' => $sender_id,
                'message_length' => strlen($message_text)
            ));
            
            // Extract session ID from metadata if available
            $session_id = isset($metadata['session_id']) ? $metadata['session_id'] : null;
            
            // Process message with AI
            $response = $this->process_message_with_ai($sender_id, $message_text, $session_id);
            
            // Send the response back to the user
            $result = $this->send_message($sender_id, $response['message']);
            
            // Log the result
            $this->logger->info('Response sent', array(
                'channel' => $this->get_id(),
                'sender' => $sender_id,
                'success' => isset($result['success']) ? $result['success'] : false,
                'session_id' => $response['session_id'],
                'source' => $response['source']
            ));
            
            return isset($result['success']) ? $result['success'] : false;
        } catch (\Exception $e) {
            // Log the error
            $this->logger->error('Error handling incoming message', array(
                'channel' => $this->get_id(),
                'sender' => $sender_id,
                'error' => $e->getMessage()
            ));
            
            return false;
        }
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled;
    }

    /**
     * Get the welcome message for the channel.
     *
     * @since    1.0.0
     * @return   string   The welcome message.
     */
    protected function get_welcome_message() {
        return isset($this->settings['welcome_message']) && !empty($this->settings['welcome_message']) 
            ? $this->settings['welcome_message'] 
            : sprintf(
                __('Hello! Thank you for reaching out to us on %s. How can I assist you today?', 'conversaai-pro-wp'),
                $this->get_name()
            );
    }

    /**
     * Send a welcome message to a new conversation.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient identifier.
     * @return   array     Response data.
     */
    protected function send_welcome_message($recipient_id) {
        $welcome_message = $this->get_welcome_message();
        return $this->send_message($recipient_id, $welcome_message);
    }

    /**
     * Map standard emoji shortcodes to unicode characters.
     *
     * @since    1.0.0
     * @param    string    $text    The text with emoji shortcodes.
     * @return   string    The text with unicode emojis.
     */
    protected function convert_emoji_shortcodes($text) {
        $emoji_map = array(
            ':)' => '😊',
            ':-)' => '😊',
            ':(' => '😢',
            ':-(' => '😢',
            ';)' => '😉',
            ';-)' => '😉',
            ':D' => '😄',
            ':-D' => '😄',
            ':P' => '😛',
            ':-P' => '😛',
            '<3' => '❤️',
            ':heart:' => '❤️',
            ':thumbsup:' => '👍',
            ':thumbsdown:' => '👎',
            ':ok:' => '👌',
            ':pray:' => '🙏',
            ':clap:' => '👏',
            ':fire:' => '🔥',
            ':100:' => '💯',
            ':thinking:' => '🤔',
            ':laughing:' => '😂',
            ':smiling:' => '😊',
            ':sad:' => '😢',
            ':angry:' => '😠',
            ':confused:' => '😕',
            ':slight_smile:' => '🙂',
            ':slight_frown:' => '🙁',
        );
        
        return str_replace(array_keys($emoji_map), array_values($emoji_map), $text);
    }

    /**
     * Prepares text for sending by handling special characters and emojis.
     *
     * @since    1.0.0
     * @param    string    $text    The text to prepare.
     * @return   string    The prepared text.
     */
    protected function prepare_text_for_sending($text) {
        // Convert emoji shortcodes
        $text = $this->convert_emoji_shortcodes($text);
        
        // Remove HTML tags (most messaging platforms don't support HTML)
        $text = strip_tags($text);
        
        // Convert HTML entities to their character equivalents
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Trim whitespace
        $text = trim($text);
        
        return $text;
    }
}