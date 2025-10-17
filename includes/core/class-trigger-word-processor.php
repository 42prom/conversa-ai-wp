<?php
/**
 * Trigger Word Processor.
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
 * Trigger Word Processor class.
 *
 * Processes user messages for trigger words and manages responses.
 *
 * @since      1.0.0
 */
class Trigger_Word_Processor {

    /**
     * Trigger words collection.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $trigger_words    The trigger words with their responses.
     */
    private $trigger_words = array();

    /**
     * Initialize the processor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_trigger_words();
    }

    /**
     * Load trigger words from the database.
     *
     * @since    1.0.0
     */
    private function load_trigger_words() {
        $this->trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Sort by priority (higher first)
        uasort($this->trigger_words, function($a, $b) {
            $a_priority = isset($a['priority']) ? intval($a['priority']) : 10;
            $b_priority = isset($b['priority']) ? intval($b['priority']) : 10;
            
            return $b_priority - $a_priority;
        });
    }

    /**
     * Process a user message for trigger words.
     *
     * @since    1.0.0
     * @param    string    $message    The user message.
     * @return   array|null    The response data or null if no trigger matched.
     */
    public function process_message($message) {
        if (empty($this->trigger_words) || empty($message)) {
            return null;
        }
        
        // Normalize message
        $normalized_message = strtolower(trim($message));
        
        // Check each trigger word
        foreach ($this->trigger_words as $id => $trigger) {
            // Skip inactive triggers
            if (isset($trigger['active']) && !$trigger['active']) {
                continue;
            }
            
            // If no responses, skip
            if (!isset($trigger['responses']) || empty($trigger['responses'])) {
                continue;
            }
            
            // Check for match based on match type
            $match = false;
            $trigger_word = strtolower(trim($trigger['word']));
            $match_type = isset($trigger['match_type']) ? $trigger['match_type'] : 'exact';
            
            switch ($match_type) {
                case 'exact':
                    $match = ($normalized_message === $trigger_word);
                    break;
                    
                case 'contains':
                    $match = (strpos($normalized_message, $trigger_word) !== false);
                    break;
                    
                case 'starts_with':
                    $match = (strpos($normalized_message, $trigger_word) === 0);
                    break;
                    
                case 'ends_with':
                    $match = (substr($normalized_message, -strlen($trigger_word)) === $trigger_word);
                    break;
                    
                case 'regex':
                    // Be careful with user-provided regex
                    $pattern = '/' . str_replace('/', '\/', $trigger_word) . '/i';
                    $match = @preg_match($pattern, $normalized_message);
                    break;
            }
            
            if ($match) {
                // Get a random response
                $response = $this->get_random_response($trigger['responses']);
                
                // Check for follow-ups
                $follow_ups = isset($trigger['follow_ups']) && !empty($trigger['follow_ups']) 
                    ? $trigger['follow_ups'] 
                    : array();
                
                return array(
                    'matched' => true,
                    'trigger_id' => $id,
                    'response' => $response,
                    'follow_ups' => $follow_ups,
                );
            }
        }
        
        return null;
    }

    /**
     * Get a random response from the available options.
     *
     * @since    1.0.0
     * @param    array    $responses    The available responses.
     * @return   string    A random response.
     */
    private function get_random_response($responses) {
        if (empty($responses)) {
            return '';
        }
        
        $index = array_rand($responses);
        return $responses[$index];
    }

    /**
     * Get a random follow-up question if available.
     *
     * @since    1.0.0
     * @param    string    $trigger_id    The trigger ID.
     * @return   string|null    A random follow-up question or null if none available.
     */
    public function get_follow_up($trigger_id) {
        if (!isset($this->trigger_words[$trigger_id]) || !isset($this->trigger_words[$trigger_id]['follow_ups']) || empty($this->trigger_words[$trigger_id]['follow_ups'])) {
            return null;
        }
        
        $follow_ups = $this->trigger_words[$trigger_id]['follow_ups'];
        $index = array_rand($follow_ups);
        
        return $follow_ups[$index];
    }
}