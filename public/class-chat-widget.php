<?php
/**
 * Chat widget functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/public
 */

namespace ConversaAI_Pro_WP\Public_Site;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Conversation_Manager;
use ConversaAI_Pro_WP\Core\Router;

/**
 * Chat widget class.
 *
 * Handles the chat widget functionality in the public-facing site.
 *
 * @since      1.0.0
 */
class Chat_Widget {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Register AJAX handlers
        add_action('wp_ajax_conversaai_send_message', array($this, 'process_message'));
        add_action('wp_ajax_nopriv_conversaai_send_message', array($this, 'process_message'));
        add_action('wp_ajax_conversaai_get_conversation', array($this, 'get_conversation_history'));
        add_action('wp_ajax_nopriv_conversaai_get_conversation', array($this, 'get_conversation_history'));
    }

    /**
     * Render the chat widget.
     *
     * @since    1.0.0
     */
    public function render_chat_widget() {
        // Only render if the widget is enabled
        $general_settings = get_option('conversaai_pro_general_settings', array());
        if (!isset($general_settings['enable_chat_widget']) || !$general_settings['enable_chat_widget']) {
            return;
        }
        
        // Get appearance settings
        $appearance_settings = get_option('conversaai_pro_appearance_settings', array());
        
        // Include the widget template
        include CONVERSAAI_PRO_PLUGIN_DIR . 'public/views/chat-widget-container.php';
    }

    /**
     * Process a message sent through the chat widget.
     *
     * @since    1.0.0
     */
    public function process_message() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_pro_chat_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Get the message and session ID
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array('message' => __('No message received.', 'conversaai-pro-wp')));
        }
        
        // Set a longer execution time for this request if possible
        // This is particularly helpful for mobile connections
        if (!ini_get('safe_mode')) {
            set_time_limit(60); // 60 seconds
        }
        
        try {
            // Initialize or load the conversation
            $conversation = new Conversation_Manager($session_id);
            
            // Add the user message to the conversation
            $conversation->add_message(array(
                'role' => 'user',
                'content' => $message,
            ));
            
            // Get the session ID (in case a new conversation was created)
            $session_id = $conversation->get_session_id();
            
            // Get the conversation history
            $history = $conversation->get_conversation_history();
            
            // Process the message using the router with improved error handling
            try {
                $router = new Router();
                $response = $router->process_query($message, $history);
                
                // Check if response is valid before proceeding
                if (!isset($response['answer'])) {
                    throw new \Exception(__('Invalid response format received.', 'conversaai-pro-wp'));
                }
                
                // Add the assistant response to the conversation - don't use esc_html here to preserve links
                $conversation->add_message(array(
                    'role' => 'assistant',
                    'content' => $response['answer'],
                ));
                
                // Send the response
                wp_send_json_success(array(
                    'message' => $response['answer'],
                    'session_id' => $session_id,
                    'source' => $response['source'],
                ));
            } catch (\Exception $e) {
                // Log the error
                error_log('ConversaAI Pro - Router Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                
                // Provide user-friendly error message for common issues
                $error_message = $e->getMessage();
                
                // Check for timeout-related messages
                if (strpos(strtolower($error_message), 'timeout') !== false || 
                    strpos(strtolower($error_message), 'timed out') !== false) {
                    $error_message = __('The request took too long to complete. This might be due to a slow connection. Please try again.', 'conversaai-pro-wp');
                }
                // Check for network-related messages
                else if (strpos(strtolower($error_message), 'network') !== false || 
                         strpos(strtolower($error_message), 'connection') !== false) {
                    $error_message = __('Network connection issue detected. Please check your internet connection and try again.', 'conversaai-pro-wp');
                }
                
                // Add error message to conversation for user reference
                $conversation->add_message(array(
                    'role' => 'system',
                    'content' => $error_message,
                ));
                
                wp_send_json_error(array('message' => $error_message));
            }
        } catch (\Exception $e) {
            // Log the general error
            error_log('ConversaAI Pro - General Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get conversation history for a session.
     *
     * @since    1.0.0
     */
    public function get_conversation_history() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_pro_chat_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Get the session ID
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('No session ID provided.', 'conversaai-pro-wp')));
        }
        
        try {
            // Load the conversation
            $conversation = new \ConversaAI_Pro_WP\Core\Conversation_Manager($session_id);
            
            // Get the conversation history
            $messages = $conversation->get_conversation_history();
            
            // Send the response
            wp_send_json_success(array(
                'messages' => $messages,
                'session_id' => $session_id,
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}