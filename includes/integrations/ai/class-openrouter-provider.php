<?php
/**
 * OpenRouter provider implementation.
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
 * OpenRouter provider.
 *
 * Implements communication with OpenRouter's API.
 *
 * @since      1.0.0
 */
class OpenRouter_Provider implements AI_Provider {

    /**
     * The API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The OpenRouter API key.
     */
    private $api_key = '';

    /**
     * The selected model.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $model    The selected OpenRouter model.
     */
    private $model = 'openai/gpt-3.5-turbo';

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
     * @var      string    $api_endpoint    The OpenRouter API endpoint.
     */
    private $api_endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Initialize the provider with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Provider-specific settings.
     */
    public function initialize($settings) {
        // Set API key
        $this->api_key = isset($settings['openrouter_api_key']) ? $settings['openrouter_api_key'] : '';
        
        // Set model (if available and specified)
        if (isset($settings['openrouter_model']) && in_array($settings['openrouter_model'], array_keys($this->get_available_models()))) {
            $this->model = $settings['openrouter_model'];
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
        // Implementation follows the same pattern as OpenAI and DeepSeek
        // Ensure API key is set
        if (empty($this->api_key)) {
            throw new \Exception(__('OpenRouter API key is not configured.', 'conversaai-pro-wp'));
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
        
        // Send request to OpenRouter API
        $api_request = new API_Request();
        $api_request->set_timeout(45);
        
        try {
            $response = $api_request->post(
                $this->api_endpoint,
                $payload,
                array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                )
            );
            
            // Error handling and response parsing
            // (Same pattern as OpenAI and DeepSeek implementations)
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $error_code = $response->get_error_code();
                
                error_log('ConversaAI Pro - OpenRouter Request Error: ' . $error_code . ' - ' . $error_message);
                
                if (strpos($error_code, 'timeout') !== false) {
                    throw new \Exception(__('The request to OpenRouter timed out. This might be due to a slow internet connection or high server load.', 'conversaai-pro-wp'));
                } else if (strpos($error_code, 'http_request_failed') !== false) {
                    throw new \Exception(__('Network error while connecting to OpenRouter. Please check your internet connection.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 400) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $error_message = isset($body['error']['message']) ? 
                    $body['error']['message'] : 
                    __('Error from OpenRouter: HTTP ', 'conversaai-pro-wp') . $status_code;
                
                error_log('ConversaAI Pro - OpenRouter API Error: ' . $status_code . ' - ' . $error_message);
                
                if ($status_code == 429) {
                    throw new \Exception(__('OpenRouter rate limit exceeded. Please try again in a moment.', 'conversaai-pro-wp'));
                } else {
                    throw new \Exception($error_message);
                }
            }
            
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($response_body['choices']) || empty($response_body['choices'][0]['message']['content'])) {
                throw new \Exception(__('Invalid response from OpenRouter API.', 'conversaai-pro-wp'));
            }
            
            $message = $response_body['choices'][0]['message']['content'];
            
            return array(
                'message' => $message,
                'model' => $this->model,
                'tokens_used' => isset($response_body['usage']['total_tokens']) ? $response_body['usage']['total_tokens'] : 0,
            );
        } catch (\Exception $e) {
            error_log('ConversaAI Pro - OpenRouter Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            throw $e;
        }
    }

    /**
     * Prepare messages array with context for OpenRouter API.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    string    $context              Additional context for the query.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Formatted messages for the API.
     */
    private function prepare_messages_with_context($query, $context, $conversation_history) {
        // Implementation follows the same pattern as OpenAI and DeepSeek
        $messages = array();
        
        // Get prompts from settings
        $prompts = get_option('conversaai_pro_prompts', array());
        $default_prompt_content = 'You are a helpful assistant for a WordPress website. Provide concise, accurate information to the user\'s queries.';
        
        // Find the default prompt or one specific to this provider
        $system_prompt = $default_prompt_content;
        foreach ($prompts as $prompt) {
            if ((isset($prompt['is_default']) && $prompt['is_default']) || 
                (isset($prompt['provider']) && ($prompt['provider'] === 'openrouter' || $prompt['provider'] === 'all'))) {
                $system_prompt = $prompt['content'];
                break;
            }
        }
        
        // Enhance with context
        if (!empty($context)) {
            $system_prompt .= "\n\n### CONTEXT INFORMATION:\n" . $context . "\n\n";
            $system_prompt .= "Instructions for using context:\n";
            $system_prompt .= "1. Use the above context information to inform your responses when relevant.\n";
            $system_prompt .= "2. Prioritize information from the context over general knowledge when answering.\n";
            $system_prompt .= "3. If the context information is not relevant to the query, rely on your general knowledge.\n";
            $system_prompt .= "4. DO NOT mention the context explicitly in your response (don't say phrases like 'based on the context' or 'according to the provided information').\n";
            $system_prompt .= "5. Respond in a natural, conversational manner.\n";
        }
        
        // Add system message and conversation history
        $messages[] = array(
            'role' => 'system',
            'content' => $system_prompt,
        );
        
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                if (in_array($message['role'], array('system', 'user', 'assistant'))) {
                    $messages[] = array(
                        'role' => $message['role'],
                        'content' => $message['content'],
                    );
                }
            }
        }
        
        $messages[] = array(
            'role' => 'user',
            'content' => $query,
        );
        
        return $messages;
    }

    /**
     * Prepare messages array for OpenRouter API.
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
        
        // Find the default prompt or one specific to OpenRouter
        $system_prompt = $default_prompt_content;
        foreach ($prompts as $prompt) {
            if ((isset($prompt['is_default']) && $prompt['is_default']) || 
                (isset($prompt['provider']) && ($prompt['provider'] === 'openrouter' || $prompt['provider'] === 'all'))) {
                $system_prompt = $prompt['content'];
                break;
            }
        }
        
        // Add system message
        $system_message_found = false;
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && $message['role'] === 'system') {
                $system_message_found = true;
                break;
            }
        }
        
        if (!$system_message_found) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt,
            );
        }
        
        // Add conversation history
        foreach ($conversation_history as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                // Only include roles that OpenRouter API accepts
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
            'openai/gpt-3.5-turbo' => 'OpenAI GPT-3.5 Turbo',
            'openai/gpt-4' => 'OpenAI GPT-4',
            'anthropic/claude-2' => 'Anthropic Claude 2',
            'anthropic/claude-instant-1' => 'Anthropic Claude Instant',
            'meta-llama/llama-2-70b-chat' => 'Meta Llama 2 70B Chat',
            'google/palm-2-chat-bison' => 'Google PaLM 2 Chat',
            'mistral/mistral-7b-instruct' => 'Mistral 7B Instruct',
            'deepseek/deepseek-chat' => 'DeepSeek Chat',
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
        return __('OpenRouter', 'conversaai-pro-wp');
    }
}