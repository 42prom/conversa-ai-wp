<?php
/**
 * OpenAI provider implementation.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/ai
 */

namespace ConversaAI_Pro_WP\Integrations\AI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Utils\API_Request;

/**
 * OpenAI provider.
 *
 * Implements communication with OpenAI's API.
 *
 * @since      1.0.0
 */
class OpenAI_Provider implements AI_Provider {

    /**
     * The API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The OpenAI API key.
     */
    private $api_key = '';

    /**
     * The selected model.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $model    The selected OpenAI model.
     */
    private $model = 'gpt-3.5-turbo';

    /**
     * The temperature setting.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $temperature    The temperature setting.
     */
    private $temperature = 0.7;

    /**
     * The maximum tokens setting.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_tokens    The maximum tokens setting.
     */
    private $max_tokens = 1024;

    /**
     * The API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The OpenAI API endpoint.
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Initialize the provider with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Provider-specific settings.
     */
    public function initialize($settings) {
        // Set API key
        $this->api_key = isset($settings['openai_api_key']) ? $settings['openai_api_key'] : '';
        
        // Set model (if available and specified)
        if (isset($settings['default_model']) && in_array($settings['default_model'], $this->get_available_models())) {
            $this->model = $settings['default_model'];
        }
        
        // Set temperature
        $this->temperature = isset($settings['temperature']) ? (float) $settings['temperature'] : 0.7;
        
        // Set max tokens
        $this->max_tokens = isset($settings['max_tokens']) ? (int) $settings['max_tokens'] : 1024;
    }

    /**
     * Process a user query and generate a response.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Response data with message and other info.
     */
    public function process_query($query, $conversation_history = array()) {
        // Ensure API key is set
        if (empty($this->api_key)) {
            throw new \Exception(__('OpenAI API key is not configured.', 'conversaai-pro-wp'));
        }
        
        // Prepare the messages array for the API
        $messages = $this->prepare_messages($query, $conversation_history);
        
        // Prepare the request payload
        $payload = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
        );
        
        // Send request to OpenAI API with improved error handling
        $api_request = new API_Request();
        
        // Set a longer timeout for potentially slower connections
        $api_request->set_timeout(45); // 45 seconds instead of default 15
        
        try {
            $response = $api_request->post(
                $this->api_endpoint,
                $payload,
                array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                )
            );
            
            // Check for WP_Error
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $error_code = $response->get_error_code();
                
                // Log the error
                error_log('ConversaAI Pro - OpenAI Request Error: ' . $error_code . ' - ' . $error_message);
                
                // Provide helpful message based on error type
                if (strpos($error_code, 'timeout') !== false) {
                    throw new \Exception(__('The request to OpenAI timed out. This might be due to a slow internet connection or high server load.', 'conversaai-pro-wp'));
                } else if (strpos($error_code, 'http_request_failed') !== false) {
                    throw new \Exception(__('Network error while connecting to OpenAI. Please check your internet connection.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            // Check HTTP status code
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 400) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $error_message = isset($body['error']['message']) ? 
                    $body['error']['message'] : 
                    __('Error from OpenAI: HTTP ', 'conversaai-pro-wp') . $status_code;
                
                error_log('ConversaAI Pro - OpenAI API Error: ' . $status_code . ' - ' . $error_message);
                
                if ($status_code == 429) {
                    throw new \Exception(__('OpenAI rate limit exceeded. Please try again in a moment.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            // Parse the response
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($response_body['choices']) || empty($response_body['choices'][0]['message']['content'])) {
                throw new \Exception(__('Invalid response from OpenAI API.', 'conversaai-pro-wp'));
            }
            
            // Extract the message content
            $message = $response_body['choices'][0]['message']['content'];
            
            // Return the formatted response
            return array(
                'message' => $message,
                'model' => $this->model,
                'tokens_used' => isset($response_body['usage']['total_tokens']) ? $response_body['usage']['total_tokens'] : 0,
            );
        } catch (\Exception $e) {
            // Log the detailed exception
            error_log('ConversaAI Pro - OpenAI Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            throw $e; // Re-throw to be handled by the caller
        }
    }

    /**
     * Process a user query with additional context and generate a response.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    string    $context              Additional context for the query.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Response data with message and other info.
     */
    public function process_query_with_context($query, $context, $conversation_history = array()) {
        // Ensure API key is set
        if (empty($this->api_key)) {
            throw new \Exception(__('OpenAI API key is not configured.', 'conversaai-pro-wp'));
        }
        
        // Prepare the messages array for the API with context
        $messages = $this->prepare_messages_with_context($query, $context, $conversation_history);
        
        // Prepare the request payload
        $payload = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
        );
        
        // Send request to OpenAI API with improved error handling
        $api_request = new \ConversaAI_Pro_WP\Utils\API_Request();
        
        // Set a longer timeout for potentially slower connections
        $api_request->set_timeout(45); // 45 seconds instead of default 15
        
        try {
            $response = $api_request->post(
                $this->api_endpoint,
                $payload,
                array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                )
            );
            
            // Check for WP_Error
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $error_code = $response->get_error_code();
                
                // Log the error
                error_log('ConversaAI Pro - OpenAI Request Error: ' . $error_code . ' - ' . $error_message);
                
                // Provide helpful message based on error type
                if (strpos($error_code, 'timeout') !== false) {
                    throw new \Exception(__('The request to OpenAI timed out. This might be due to a slow internet connection or high server load.', 'conversaai-pro-wp'));
                } else if (strpos($error_code, 'http_request_failed') !== false) {
                    throw new \Exception(__('Network error while connecting to OpenAI. Please check your internet connection.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            // Check HTTP status code
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 400) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $error_message = isset($body['error']['message']) ? 
                    $body['error']['message'] : 
                    __('Error from OpenAI: HTTP ', 'conversaai-pro-wp') . $status_code;
                
                error_log('ConversaAI Pro - OpenAI API Error: ' . $status_code . ' - ' . $error_message);
                
                if ($status_code == 429) {
                    throw new \Exception(__('OpenAI rate limit exceeded. Please try again in a moment.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            // Parse the response
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($response_body['choices']) || empty($response_body['choices'][0]['message']['content'])) {
                throw new \Exception(__('Invalid response from OpenAI API.', 'conversaai-pro-wp'));
            }
            
            // Extract the message content
            $message = $response_body['choices'][0]['message']['content'];
            
            // Return the formatted response
            return array(
                'message' => $message,
                'model' => $this->model,
                'tokens_used' => isset($response_body['usage']['total_tokens']) ? $response_body['usage']['total_tokens'] : 0,
            );
        } catch (\Exception $e) {
            // Log the detailed exception
            error_log('ConversaAI Pro - OpenAI Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            throw $e; // Re-throw to be handled by the caller
        }
    }

    /**
     * Prepare messages array with context for OpenAI API.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    string    $context              Additional context for the query.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Formatted messages for the API.
     */
    private function prepare_messages_with_context($query, $context, $conversation_history) {
        $messages = array();
        
        // Get prompts from settings
        $prompts = get_option('conversaai_pro_prompts', array());
        $default_prompt_content = 'You are a helpful assistant for a WordPress website. Provide concise, accurate information to the user\'s queries.';
        
        // Find the default prompt or one specific to OpenAI
        $system_prompt = $default_prompt_content;
        foreach ($prompts as $prompt) {
            if ((isset($prompt['is_default']) && $prompt['is_default']) || 
                (isset($prompt['provider']) && ($prompt['provider'] === 'openai' || $prompt['provider'] === 'all'))) {
                $system_prompt = $prompt['content'];
                break;
            }
        }
        
        // Enhanced system prompt with improved context utilization instructions
        $system_message = $system_prompt;
        
        if (!empty($context)) {
            $system_message .= "\n\n=== REFERENCE INFORMATION ===\n" . $context;
            $system_message .= "\n=== END REFERENCE INFORMATION ===\n\n";
            $system_message .= "When responding to the user:\n";
            $system_message .= "- Use the reference information above when it's relevant to the query\n";
            $system_message .= "- Prioritize the reference information over your general knowledge when answering\n";
            $system_message .= "- If the reference information doesn't contain relevant details, use your general knowledge\n";
            $system_message .= "- NEVER mention that you're using reference information or context in your response\n";
            $system_message .= "- Provide natural, conversational responses as if the information is simply something you know\n";
            $system_message .= "- If the reference information contradicts your knowledge, trust the reference information\n";
        }
        
        // Add system message
        $messages[] = array(
            'role' => 'system',
            'content' => $system_message,
        );
        
        // Add conversation history
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                // Only include roles that OpenAI API accepts
                if (in_array($message['role'], array('system', 'user', 'assistant'))) {
                    $messages[] = array(
                        'role' => $message['role'],
                        'content' => $message['content'],
                    );
                }
            }
        }
        
        // Add the current query as user message
        $messages[] = array(
            'role' => 'user',
            'content' => $query,
        );
        
        return $messages;
    }

    /**
     * Prepare messages array for OpenAI API.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Formatted messages for the API.
     */
    private function prepare_messages($query, $conversation_history) {
        $messages = array();
        
        // Get prompts from settings
        $prompts = get_option('conversaai_pro_prompts', array());
        $default_prompt_content = 'You are a helpful assistant for a WordPress website. Provide concise, accurate information to the user\'s queries.';
        
        // Find the default prompt or one specific to OpenAI
        $system_prompt = $default_prompt_content;
        foreach ($prompts as $prompt) {
            if ((isset($prompt['is_default']) && $prompt['is_default']) || 
                (isset($prompt['provider']) && ($prompt['provider'] === 'openai' || $prompt['provider'] === 'all'))) {
                $system_prompt = $prompt['content'];
                break;
            }
        }
        
        // Add system message if not already in the history
        $system_message_found = false;
        
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && $message['role'] === 'system') {
                $system_message_found = true;
                break;
            }
        }
        
        if (!$system_message_found) {
            // Add system message
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt,
            );
        }
        
        // Add conversation history
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                // Only include roles that OpenAI API accepts
                if (in_array($message['role'], array('system', 'user', 'assistant'))) {
                    $messages[] = array(
                        'role' => $message['role'],
                        'content' => $message['content'],
                    );
                }
            }
        }
        
        // Add the current query as user message
        $messages[] = array(
            'role' => 'user',
            'content' => $query,
        );
        
        return $messages;
    }

    /**
     * Get available models for this provider.
     *
     * @since    1.0.0
     * @return   array    List of available models.
     */
    public function get_available_models() {
        return array(
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
        );
    }

    /**
     * Check if the provider is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the provider is available.
     */
    public function is_available() {
        return !empty($this->api_key);
    }

    /**
     * Get the provider's name.
     *
     * @since    1.0.0
     * @return   string   The provider name.
     */
    public function get_name() {
        return __('OpenAI', 'conversaai-pro-wp');
    }
}