<?php
/**
 * Manages conversation state and context.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/core
 */

namespace ConversaAI_Pro_WP\Core;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Conversation Manager class.
 *
 * Handles creation, retrieval, and management of conversation state and context.
 *
 * @since      1.0.0
 */
class Conversation_Manager {

    /**
     * The ID of the current conversation session.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $session_id    The current conversation session ID.
     */
    private $session_id;

    /**
     * The conversation history.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $conversation    The conversation messages.
     */
    private $conversation = array();

    /**
     * Metadata for the conversation.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $metadata    Conversation metadata.
     */
    private $metadata = array();

    /**
     * The communication channel for this conversation.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $channel    The channel (e.g., 'webchat', 'whatsapp', etc.).
     */
    private $channel = 'webchat';

    /**
     * Maximum conversation history to maintain.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_history    Maximum number of messages to keep.
     */
    private $max_history = 10;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $session_id    Optional. The session ID to load.
     * @param    string    $channel       Optional. The communication channel.
     */
    public function __construct($session_id = null, $channel = 'webchat') {
        $this->channel = $channel;
        
        if ($session_id) {
            $this->session_id = $session_id;
            $this->load_conversation();
        } else {
            $this->session_id = $this->generate_session_id();
            $this->init_new_conversation();
        }
    }

    /**
     * Generate a unique session ID.
     *
     * @since    1.0.0
     * @return   string    The generated session ID.
     */
    private function generate_session_id() {
        return uniqid('convo_', true);
    }

    /**
     * Initialize a new conversation.
     *
     * @since    1.0.0
     */
    private function init_new_conversation() {
        $this->conversation = array();
        $this->metadata = array(
            'started_at' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'page_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip_address' => $this->get_client_ip(),
        );
        
        // Add welcome message if configured
        $general_settings = get_option('conversaai_pro_general_settings', array());
        if (!empty($general_settings['welcome_message'])) {
            $this->add_message(array(
                'role' => 'assistant',
                'content' => $general_settings['welcome_message'],
                'timestamp' => current_time('mysql'),
            ));
        }
        
        $this->save_conversation();
    }

    /**
     * Get the client IP address.
     *
     * @since    1.0.0
     * @return   string    The client IP address.
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        
        return $ip;
    }

    /**
     * Load a conversation from the database.
     *
     * @since    1.0.0
     * @return   bool    Whether the conversation was loaded successfully.
     */
    private function load_conversation() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE session_id = %s",
                $this->session_id
            ),
            ARRAY_A
        );
        
        if ($row) {
            $this->conversation = maybe_unserialize($row['messages']);
            $this->metadata = json_decode($row['metadata'], true) ?: []; // Decode JSON
            return true;
        }
        
        // If conversation not found, initialize a new one
        $this->init_new_conversation();
        // Log failure to find conversation
        error_log('ConversaAI Pro: Could not load conversation with session_id: ' . $this->session_id);
        return false;
    }

    /**
     * Save the conversation to the database.
     *
     * @since    1.0.0
     * @return   bool    Whether the conversation was saved successfully.
     */
    public function save_conversation() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        $user_id = isset($this->metadata['user_id']) ? $this->metadata['user_id'] : get_current_user_id();
        
        $data = array(
            'session_id' => $this->session_id,
            'user_id' => $user_id,
            'channel' => $this->channel,
            'messages' => maybe_serialize($this->conversation),
            'metadata' => wp_json_encode($this->metadata),
        );
        
        $format = array(
            '%s', // session_id
            '%d', // user_id
            '%s', // channel
            '%s', // messages
            '%s', // metadata
        );
        
        // Check if the conversation already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE session_id = %s",
                $this->session_id
            )
        );
        
        if ($exists) {
            // Update existing conversation
            $result = $wpdb->update(
                $table_name,
                $data,
                array('session_id' => $this->session_id),
                $format,
                array('%s')
            );
        } else {
            // Insert new conversation
            $result = $wpdb->insert($table_name, $data, $format);
        }
        
        return $result !== false;
    }

    /**
     * Add a message to the conversation.
     *
     * @since    1.0.0
     * @param    array    $message    The message to add.
     * @return   bool     Whether the message was added successfully.
     */
    public function add_message($message) {
        // Validate message
        if (!isset($message['role']) || !isset($message['content'])) {
            return false;
        }
        
        // Add timestamp if not provided
        if (!isset($message['timestamp'])) {
            $message['timestamp'] = current_time('mysql');
        }
        
        // Add message to the conversation
        $this->conversation[] = $message;
        
        // Trim conversation if it exceeds max history
        if (count($this->conversation) > $this->max_history) {
            // Keep the first message (usually the welcome message) and remove oldest message after that
            $welcome = array_shift($this->conversation);
            array_shift($this->conversation); // Remove oldest message
            array_unshift($this->conversation, $welcome); // Add welcome back
        }
        
        // Save the updated conversation
        return $this->save_conversation();
    }

    /**
     * Get the conversation history in a format suitable for AI context.
     *
     * @since    1.0.0
     * @param    int      $limit    Optional. Maximum number of messages to return.
     * @return   array    The conversation history.
     */
    public function get_conversation_history($limit = null) {
        if ($limit === null) {
            return $this->conversation;
        }
        
        // If limit provided, return only the most recent messages
        return array_slice($this->conversation, -$limit);
    }

    /**
     * Get the session ID for this conversation.
     *
     * @since    1.0.0
     * @return   string    The session ID.
     */
    public function get_session_id() {
        return $this->session_id;
    }

    /**
     * Update metadata for the conversation.
     *
     * @since    1.0.0
     * @param    array    $metadata    Metadata to update.
     * @return   bool     Whether the metadata was updated successfully.
     */
    public function update_metadata($metadata) {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this->save_conversation();
    }

    /**
     * Get the conversation metadata.
     *
     * @since    1.0.0
     * @return   array    The conversation metadata.
     */
    public function get_metadata() {
        return $this->metadata;
    }
}