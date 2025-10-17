<?php
/**
 * Router class for determining how to handle user queries.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/core
 */

namespace ConversaAI_Pro_WP\Core;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Integrations\AI\AI_Factory;

/**
 * Router class.
 *
 * Determines whether to handle queries using local knowledge base
 * or forward to an AI provider.
 *
 * @since      1.0.0
 */
class Router {

    /**
     * The confidence threshold for using local knowledge base answers.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $confidence_threshold    The confidence threshold.
     */
    private $confidence_threshold;

    /**
     * Whether to prioritize local KB over AI.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $prioritize_local_kb    Whether to prioritize local KB.
     */
    private $prioritize_local_kb;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $ai_settings = get_option('conversaai_pro_ai_settings', array());
        
        $this->confidence_threshold = isset($ai_settings['confidence_threshold']) 
            ? (float) $ai_settings['confidence_threshold'] 
            : CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD;
            
        $this->prioritize_local_kb = isset($ai_settings['prioritize_local_kb']) 
            ? (bool) $ai_settings['prioritize_local_kb'] 
            : true;
    }

    /**
     * Search WordPress content for an answer.
     *
     * @since    1.0.0
     * @param    string    $query    The search query.
     * @return   array|null    The content result or null if not found.
     */
    private function search_wordpress_content($query) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Try to find content with source=wp_content
        $content_match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE JSON_EXTRACT(metadata, '$.source') = 'wp_content'
                AND approved = 1
                AND MATCH(question) AGAINST(%s)
                ORDER BY confidence DESC
                LIMIT 1",
                $query
            ),
            ARRAY_A
        );
        
        if (!$content_match || $wpdb->last_error) {
            // Fallback to simpler search if JSON or MATCH AGAINST not available
            $like_term = '%' . $wpdb->esc_like($query) . '%';
            $content_match = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name 
                    WHERE metadata LIKE %s
                    AND approved = 1
                    AND question LIKE %s
                    ORDER BY confidence DESC
                    LIMIT 1",
                    '%wp_content%',
                    $like_term
                ),
                ARRAY_A
            );
        }
        
        if (!$content_match) {
            return null;
        }
        
        return array(
            'answer' => $content_match['answer'],
            'confidence' => $content_match['confidence'],
            'id' => $content_match['id'],
            'source' => 'wp_content',
        );
    }

    /**
     * Process a user query and route it to the appropriate handler.
     *
     * @since    1.0.0
     * @param    string    $query              The user query.
     * @param    array     $conversation_history    The conversation history.
     * @return   array     The response data.
     */
    public function process_query($query, $conversation_history = array()) {
        // Try trigger words first if enabled
        $settings = get_option('conversaai_pro_learning_settings', array());
        $use_trigger_words = isset($settings['use_trigger_words']) ? (bool) $settings['use_trigger_words'] : true;

        if ($use_trigger_words) {
            require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/core/class-trigger-word-processor.php';
            $trigger_processor = new Trigger_Word_Processor();
            $trigger_result = $trigger_processor->process_message($query);
            
            if ($trigger_result && isset($trigger_result['matched']) && $trigger_result['matched']) {
                return array(
                    'source' => 'trigger_word',
                    'answer' => $this->format_links($trigger_result['response']),
                    'trigger_id' => $trigger_result['trigger_id'],
                    'follow_ups' => $trigger_result['follow_ups'],
                    'confidence' => 1.0, // Trigger words have maximum confidence
                );
            }
        }
        
        // Gather relevant context from knowledge base
        $knowledge_result = $this->search_knowledge_base($query);
        
        // Gather context from WordPress content
        $content_result = $this->search_wordpress_content($query);
        
        // Gather context from WooCommerce products if available
        $product_context = '';
        if (class_exists('WooCommerce')) {
            $product_result = $this->search_woocommerce_products($query);
            if ($product_result) {
                $product_context = $product_result['answer'];
            }
        }
        
        // Combine all context sources
        $context = $this->prepare_combined_context($query, $knowledge_result, $content_result, $product_context);
        
        // If we have high confidence KB match and prioritize local KB, use it directly
        if ($knowledge_result && 
            $knowledge_result['confidence'] >= $this->confidence_threshold && 
            $this->prioritize_local_kb) {
            
            // Log the KB hit
            $this->log_kb_hit($query, $knowledge_result);
            
            // Format any links in the knowledge base response
            $knowledge_result['answer'] = $this->format_links($knowledge_result['answer']);
            
            return array(
                'source' => 'knowledge_base',
                'answer' => $knowledge_result['answer'],
                'confidence' => $knowledge_result['confidence'],
            );
        }
        
        // Query AI with the combined context and the original query
        $ai_result = $this->query_ai_with_context($query, $context, $conversation_history);
        
        if ($ai_result) {
            // Format any links in the AI response
            $ai_result['answer'] = $this->format_links($ai_result['answer']);
            
            return array(
                'source' => 'knowledge_augmented_ai',
                'answer' => $ai_result['answer'],
                'model' => $ai_result['model'],
                'context_sources' => $context['sources'],
            );
        }
        
        // Fallback to best local result even if confidence is low
        if ($knowledge_result) {
            // Format any links in the knowledge base response
            $knowledge_result['answer'] = $this->format_links($knowledge_result['answer']);
            
            return array(
                'source' => 'knowledge_base_fallback',
                'answer' => $knowledge_result['answer'],
                'confidence' => $knowledge_result['confidence'],
            );
        }
        
        if ($content_result) {
            // Format any links in the content response
            $content_result['answer'] = $this->format_links($content_result['answer']);
            
            return array(
                'source' => 'wp_content_fallback',
                'answer' => $content_result['answer'],
                'confidence' => $content_result['confidence'],
            );
        }
        
        // Last resort fallback
        return array(
            'source' => 'fallback',
            'answer' => __('I\'m sorry, I couldn\'t find an answer to your question. Could you please rephrase or ask something else?', 'conversaai-pro-wp'),
        );
    }

    /**
     * Format links in text to proper HTML links.
     *
     * @since    1.0.0
     * @param    string    $text    The text containing potential links.
     * @return   string    The text with formatted HTML links.
     */
    private function format_links($text) {
        // Get the link color from settings or use default
        $appearance_settings = get_option('conversaai_pro_appearance_settings', array());
        $link_color = isset($appearance_settings['response_link_color']) ? $appearance_settings['response_link_color'] : '#4c66ef';
        
        // Don't process empty text
        if (empty($text)) {
            return $text;
        }
        
        // Process markdown-style links: [text](url)
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer" style="color:' . $link_color . '">$1</a>', $text);
        
        // Handle WooCommerce product links properly
        if (strpos($text, '/product/') !== false) {
            // Make sure we're not matching inside existing HTML tags
            $pattern = '~(?<!href=["|\'])(?<!src=["|\'])(https?://[^\s<>"\']+/product/[^\s<>"\']+)~i';
            $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:' . $link_color . '">View product details</a>';
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        // Handle general URLs that aren't already in HTML links
        // This pattern makes sure we're not matching URLs that are already part of an href
        $pattern = '~(?<!href=["|\'])(?<!src=["|\'])(https?://[^\s<>"\']+)~i';
        $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:' . $link_color . '">$1</a>';
        $text = preg_replace($pattern, $replacement, $text);
        
        // Clean up any malformed/broken link patterns
        $text = preg_replace('~([^"\'=])(https?://[^\s<>"\']+)"~i', '$1<a href="$2" target="_blank" rel="noopener noreferrer" style="color:' . $link_color . '">$2</a>"', $text);
        
        // Prevent double-wrapping of links
        $text = preg_replace('~<a href="<a href="([^"]+)"([^>]*)>([^<]+)</a>"([^>]*)>([^<]+)</a>~i', '<a href="$1"$2>$3</a>', $text);
        
        return $text;
    }

    /**
     * Prepare combined context from various sources.
     *
     * @since    1.0.0
     * @param    string         $query              The original user query.
     * @param    array|null     $knowledge_result   Knowledge base search result.
     * @param    array|null     $content_result     WordPress content search result.
     * @param    string         $product_context    WooCommerce product context.
     * @return   array          The formatted context and sources information.
     */
    private function prepare_combined_context($query, $knowledge_result, $content_result, $product_context = '') {
        $context_text = "USER QUERY: " . $query . "\n\n";
        $context_sources = [];
        
        // Add knowledge base information if available
        if ($knowledge_result) {
            $confidence_level = $this->get_confidence_level($knowledge_result['confidence']);
            $context_text .= "### Knowledge Base Information (Confidence: {$confidence_level}):\n";
            $context_text .= $knowledge_result['answer'] . "\n\n";
            $context_sources[] = 'knowledge_base';
        }
        
        // Add WordPress content information
        if ($content_result) {
            $context_text .= "### WordPress Content Information:\n";
            $context_text .= $content_result['answer'] . "\n\n";
            $context_sources[] = 'wp_content';
        }
        
        // Add product information if available
        if (!empty($product_context)) {
            $context_text .= "### WooCommerce Product Information:\n";
            $context_text .= $product_context . "\n\n";
            $context_sources[] = 'woocommerce_product';
        }
        
        return [
            'text' => $context_text,
            'sources' => $context_sources
        ];
    }

    /**
     * Convert numerical confidence to textual level.
     *
     * @since    1.0.0
     * @param    float     $confidence    The confidence score.
     * @return   string    The confidence level description.
     */
    private function get_confidence_level($confidence) {
        if ($confidence >= 0.9) {
            return 'High';
        } elseif ($confidence >= 0.7) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Query the AI with context-enhanced prompt.
     *
     * @since    1.0.0
     * @param    string    $query                The user query.
     * @param    array     $context              The context information.
     * @param    array     $conversation_history    The conversation history.
     * @return   array|false    The AI result or false if failed.
     */
    private function query_ai_with_context($query, $context, $conversation_history) {
        $ai_settings = get_option('conversaai_pro_ai_settings', array());
        $provider_name = isset($ai_settings['default_provider']) ? $ai_settings['default_provider'] : CONVERSAAI_PRO_DEFAULT_AI_PROVIDER;
        
        try {
            // Get the appropriate AI provider
            $ai_factory = new \ConversaAI_Pro_WP\Integrations\AI\AI_Factory();
            $provider = $ai_factory->get_provider($provider_name, true);
            
            if (!$provider) {
                throw new \Exception("AI provider '$provider_name' not available");
            }
            
            // Process the query with context
            $response = $provider->process_query_with_context($query, $context['text'], $conversation_history);
            
            return array(
                'answer' => $response['message'],
                'model' => $response['model'],
                'context_sources' => $context['sources'],
            );
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro - AI query error: ' . $e->getMessage());
            
            return false;
        }
    }

    /**
     * Search for WooCommerce products matching the query.
     *
     * @since    1.0.0
     * @param    string    $query    The search query.
     * @return   array|null    The product information or null if not found.
     */
    private function search_woocommerce_products($query) {
        // Only proceed if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return null;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Look for product entries in the knowledge base
        $product_match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE metadata LIKE %s
                AND approved = 1
                AND MATCH(question) AGAINST(%s)
                ORDER BY confidence DESC
                LIMIT 1",
                '%woocommerce_product%',
                $query
            ),
            ARRAY_A
        );
        
        if (!$product_match || $wpdb->last_error) {
            // Fallback to simpler search if JSON or MATCH AGAINST not available
            $like_term = '%' . $wpdb->esc_like($query) . '%';
            $product_match = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name 
                    WHERE metadata LIKE %s
                    AND approved = 1
                    AND question LIKE %s
                    ORDER BY confidence DESC
                    LIMIT 1",
                    '%woocommerce_product%',
                    $like_term
                ),
                ARRAY_A
            );
        }
        
        if (!$product_match) {
            return null;
        }
        
        return array(
            'answer' => $product_match['answer'],
            'confidence' => $product_match['confidence'],
            'id' => $product_match['id'],
            'source' => 'woocommerce_product',
        );
    }

    /**
     * Search the knowledge base for an answer to the query.
     *
     * @since    1.0.0
     * @param    string    $query    The user query.
     * @return   array|false    The knowledge base result or false if not found.
     */
    private function search_knowledge_base($query) {
        // Initialize the knowledge base class
        $kb = new Knowledge_Base();
        
        // Search for a matching entry
        $result = $kb->search($query);
        
        if (!$result) {
            return false;
        }
        
        // Increment the usage count for this entry
        $kb->increment_usage_count($result['id']);
        
        return array(
            'answer' => $result['answer'],
            'confidence' => $result['confidence'],
            'id' => $result['id'],
        );
    }

    /**
     * Query the AI for an answer.
     *
     * @since    1.0.0
     * @param    string    $query                The user query.
     * @param    array     $conversation_history    The conversation history.
     * @return   array|false    The AI result or false if failed.
     */
    private function query_ai($query, $conversation_history) {
        $ai_settings = get_option('conversaai_pro_ai_settings', array());
        $provider_name = isset($ai_settings['default_provider']) ? $ai_settings['default_provider'] : CONVERSAAI_PRO_DEFAULT_AI_PROVIDER;
        
        try {
            // Get the appropriate AI provider - always use a fresh instance to ensure latest settings
            $ai_factory = new AI_Factory();
            $provider = $ai_factory->get_provider($provider_name, true);
            
            if (!$provider) {
                throw new \Exception("AI provider '$provider_name' not available");
            }
            
            // Process the query with the AI provider
            $response = $provider->process_query($query, $conversation_history);
            
            return array(
                'answer' => $response['message'],
                'model' => $response['model'],
            );
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro - AI query error: ' . $e->getMessage());
            
            return false;
        }
    }

    /**
     * Log a knowledge base hit for analytics.
     *
     * @since    1.0.0
     * @param    string    $query       The user query.
     * @param    array     $result      The knowledge base result.
     * @param    string    $hit_type    The type of hit (normal or fallback).
     */
    private function log_kb_hit($query, $result, $hit_type = 'normal') {
        global $wpdb;
        
        // Update usage count for the knowledge item
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET usage_count = usage_count + 1 WHERE id = %d",
                $result['id']
            )
        );
        
        // In a full implementation, we would also log analytics data here
    }
}