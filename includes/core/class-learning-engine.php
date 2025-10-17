<?php
/**
 * Learning engine for improving AI responses.
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
 * Learning Engine class.
 *
 * Handles self-improvement and feedback analysis.
 *
 * @since      1.0.0
 */
class Learning_Engine {

    /**
     * Knowledge Base instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Core\Knowledge_Base    $knowledge_base    The knowledge base.
     */
    private $knowledge_base;

    /**
     * Analytics Manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Core\Analytics_Manager    $analytics_manager    The analytics manager.
     */
    private $analytics_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->knowledge_base = new Knowledge_Base();
        $this->analytics_manager = new Analytics_Manager();
    }

    /**
     * Get pending knowledge entries for review.
     *
     * @since    1.0.0
     * @param    int       $limit    Optional. Maximum number of entries to return.
     * @return   array     Pending knowledge entries.
     */
    public function get_pending_knowledge_entries($limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE approved = 0 
                ORDER BY confidence DESC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $entries ?: array();
    }

    /**
     * Get learning statistics.
     *
     * @since    1.0.0
     * @return   array     Learning statistics.
     */
    public function get_learning_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Get counts
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 0");
        $approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 1 AND JSON_EXTRACT(metadata, '$.auto_approved') IS NULL");
        $auto_approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 1 AND JSON_EXTRACT(metadata, '$.auto_approved') = 1");
        $rejected_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE JSON_EXTRACT(metadata, '$.rejected') = 1");
        
        // Fallback for databases without JSON functions
        if ($wpdb->last_error) {
            $approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE approved = 1");
            $auto_approved_count = 0;
            $rejected_count = 0;
            
            // Manual counting for auto-approved and rejected
            $entries = $wpdb->get_results("SELECT metadata FROM $table_name WHERE approved = 1", ARRAY_A);
            foreach ($entries as $entry) {
                $metadata = json_decode($entry['metadata'], true);
                if (isset($metadata['auto_approved']) && $metadata['auto_approved']) {
                    $auto_approved_count++;
                    $approved_count--; // Adjust approved count
                }
            }
            
            $entries = $wpdb->get_results("SELECT metadata FROM $table_name", ARRAY_A);
            foreach ($entries as $entry) {
                $metadata = json_decode($entry['metadata'], true);
                if (isset($metadata['rejected']) && $metadata['rejected']) {
                    $rejected_count++;
                }
            }
        }
        
        // Get average confidence
        $avg_confidence = $wpdb->get_var("SELECT AVG(confidence) FROM $table_name WHERE approved = 1");
        
        // Get settings
        $learning_settings = get_option('conversaai_pro_learning_settings', array(
            'auto_extraction' => true,
            'min_confidence' => 0.7,
            'auto_approve' => false,
            'min_auto_approve_confidence' => 0.9,
        ));
        
        return array(
            'pending_count' => (int) $pending_count,
            'approved_count' => (int) $approved_count,
            'auto_approved_count' => (int) $auto_approved_count,
            'rejected_count' => (int) $rejected_count,
            'total_count' => (int) $pending_count + $approved_count + $auto_approved_count,
            'avg_confidence' => (float) $avg_confidence ?: 0,
            'settings' => $learning_settings
        );
    }

    /**
     * Analyze a conversation for potential knowledge acquisition.
     *
     * @since    1.0.0
     * @param    string    $session_id    The conversation session ID.
     * @return   array     Analysis results.
     */
    public function analyze_conversation($session_id) {
        // Get conversation manager instance
        $conversation = new \ConversaAI_Pro_WP\Core\Conversation_Manager($session_id);
        $messages = $conversation->get_conversation_history();
        
        if (empty($messages)) {
            return false;
        }
        
        // Group messages into QA pairs
        $qa_pairs = array();
        $current_question = null;
        
        foreach ($messages as $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                continue;
            }
            
            if ($message['role'] === 'user') {
                // This is a user message (question)
                $current_question = $message['content'];
            } elseif ($message['role'] === 'assistant' && $current_question !== null) {
                // This is an assistant message (answer) to the current question
                $qa_pairs[] = array(
                    'question' => $current_question,
                    'answer' => $message['content'],
                );
                $current_question = null; // Reset for next pair
            }
        }
        
        // Process QA pairs to create potential knowledge entries
        $potential_entries = array();
        
        foreach ($qa_pairs as $pair) {
            // Simple filtering: skip very short questions or answers
            if (strlen($pair['question']) < 10 || strlen($pair['answer']) < 20) {
                continue;
            }
            
            // Calculate a confidence score based on various factors
            $confidence = $this->calculate_entry_confidence($pair['question'], $pair['answer']);
            
            // Create the potential entry
            $potential_entries[] = array(
                'question' => $pair['question'],
                'answer' => $pair['answer'],
                'topic' => '', // Auto-detection would be nice but requires NLP
                'confidence' => $confidence,
                'metadata' => wp_json_encode(array(
                    'source' => 'conversation',
                    'session_id' => $session_id,
                    'extracted_at' => current_time('mysql'),
                )),
            );
        }
        
        return array(
            'session_id' => $session_id,
            'message_count' => count($messages),
            'qa_pair_count' => count($qa_pairs),
            'potential_entries' => $potential_entries,
        );
    }
    
    /**
     * Calculate confidence score for a potential knowledge entry.
     *
     * @since    1.0.0
     * @param    string    $question       The question.
     * @param    string    $answer         The answer.
     * @param    array     $messages       Full conversation messages.
     * @param    int       $message_index  Index of the current message.
     * @return   float     Confidence score between 0 and 1.
     */
    private function calculate_response_confidence($question, $answer, $messages, $message_index) {
        $confidence = 0.7; // Base confidence
        
        // Factor 1: Answer length and substance
        $answer_length = strlen($answer);
        if ($answer_length > 300) {
            $confidence += 0.1; // Detailed answer
        } elseif ($answer_length < 50) {
            $confidence -= 0.1; // Very short answer
        }
        
        // Factor 2: Question clarity
        $question_length = strlen($question);
        if ($question_length > 10 && $question_length < 200) {
            $confidence += 0.05; // Clear, concise question
        } else {
            $confidence -= 0.05; // Too short or too long
        }
        
        // Factor 3: Follow-up indicators
        if (isset($messages[$message_index + 1]) && isset($messages[$message_index + 1]['role'])) {
            if ($messages[$message_index + 1]['role'] === 'user') {
                $next_message = $messages[$message_index + 1]['content'];
                
                // Check if the next message indicates a satisfied response
                if (preg_match('/thank|thanks|got it|great|perfect|that helps/i', $next_message)) {
                    $confidence += 0.1; // User expressed satisfaction
                }
                // Check if next message indicates confusion or dissatisfaction
                elseif (preg_match('/not what i|don\'t understand|wrong|incorrect|that doesn\'t|doesn\'t answer/i', $next_message)) {
                    $confidence -= 0.2; // User was dissatisfied
                }
            }
        }
        
        // Keep within bounds
        return max(0, min(1, $confidence));
    }

    /**
     * Add potential knowledge entries to the knowledge base.
     *
     * @since    1.0.0
     * @param    array     $entries           Array of potential entries.
     * @param    bool      $auto_approve      Whether to auto-approve high confidence entries.
     * @return   array     Results with counts of added and auto-approved entries.
     */
    public function add_potential_entries($entries, $auto_approve = false, $min_confidence = 0.9) {
        if (!is_array($entries) || empty($entries)) {
            return array(
                'added' => 0,
                'skipped' => 0,
                'auto_approved' => 0,
            );
        }
        
        $added = 0;
        $skipped = 0;
        $auto_approved_count = 0;
        
        // Get Knowledge Base instance
        $kb = new \ConversaAI_Pro_WP\Core\Knowledge_Base();
        
        foreach ($entries as $entry) {
            // Check if the entry has the required fields
            if (!isset($entry['question']) || !isset($entry['answer'])) {
                $skipped++;
                continue;
            }
            
            // Set default values if not provided
            $topic = isset($entry['topic']) ? $entry['topic'] : '';
            $confidence = isset($entry['confidence']) ? (float) $entry['confidence'] : 0.5;
            $metadata = isset($entry['metadata']) ? $entry['metadata'] : '';
            
            // Determine approval status
            $approved = $auto_approve && $confidence >= $min_confidence;
            
            // Add to knowledge base
            $result = $kb->add_entry(
                $entry['question'],
                $entry['answer'],
                $topic,
                $confidence,
                $approved,
                $metadata
            );
            
            if ($result) {
                $added++;
                if ($approved) {
                    $auto_approved_count++;
                }
            } else {
                $skipped++;
            }
        }
        
        return array(
            'added' => $added,
            'skipped' => $skipped,
            'auto_approved' => $auto_approved_count,
        );
    }

    /**
     * Calculate similarity between two strings.
     *
     * @since    1.0.0
     * @param    string    $str1    First string.
     * @param    string    $str2    Second string.
     * @return   float     Similarity score between 0 and 1.
     */
    private function calculate_similarity($str1, $str2) {
        // Normalize strings
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        // If strings are identical
        if ($str1 === $str2) {
            return 1.0;
        }
        
        // If either string is empty
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        // Calculate Levenshtein distance
        $distance = levenshtein($str1, $str2);
        $max_length = max(strlen($str1), strlen($str2));
        
        // Convert distance to similarity
        return 1.0 - ($distance / $max_length);
    }

    /**
     * Extract knowledge from a conversation.
     *
     * @since    1.0.0
     * @param    string    $session_id    The conversation session ID.
     * @return   array     Extraction results.
     */
    public function extract_knowledge_from_conversation($session_id, $auto_approve = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        // Check if this conversation has already been processed
        $metadata = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT metadata FROM $table_name WHERE session_id = %s",
                $session_id
            )
        );
        
        $already_processed = false;
        if ($metadata) {
            // Try to decode as JSON first
            $json_metadata = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_metadata)) {
                $md = $json_metadata;
            } else {
                // If not JSON, try to unserialize
                $md = maybe_unserialize($metadata);
            }
            
            // Check if already processed
            if (is_array($md) && isset($md['knowledge_extracted']) && $md['knowledge_extracted']) {
                $already_processed = true;
            }
        }
        
        // Analyze the conversation
        $analysis = $this->analyze_conversation($session_id);
        
        // Set default result
        $result = array(
            'session_id' => $session_id,
            'already_processed' => $already_processed,
            'extracted' => 0,
            'auto_approved' => 0,
            'skipped' => 0,
        );
        
        // If analysis failed or no potential entries
        if (!$analysis || empty($analysis['potential_entries'])) {
            // Mark as processed anyway
            $this->mark_conversation_processed($session_id, 0);
            return $result;
        }
        
        // Use the provided auto_approve value, or get from settings
        if ($auto_approve === null) {
            $learning_settings = get_option('conversaai_pro_learning_settings', array());
            $auto_approve = isset($learning_settings['auto_approve']) ? (bool) $learning_settings['auto_approve'] : false;
            $min_auto_approve_confidence = isset($learning_settings['min_auto_approve_confidence']) 
                ? (float) $learning_settings['min_auto_approve_confidence'] 
                : 0.9;
        } else {
            $min_auto_approve_confidence = 0.9; // Default if not specified
        }
        
        // Add the entries to the knowledge base
        $add_result = $this->add_potential_entries($analysis['potential_entries'], $auto_approve, $min_auto_approve_confidence);
        
        // Mark the conversation as processed
        $this->mark_conversation_processed($session_id, $add_result['added']);
        
        // Return the results
        return array(
            'session_id' => $session_id,
            'already_processed' => $already_processed,
            'extracted' => $add_result['added'],
            'auto_approved' => $add_result['auto_approved'],
            'skipped' => $add_result['skipped'],
        );
    }

    /**
     * Approve a knowledge entry.
     *
     * @since    1.0.0
     * @param    int       $entry_id      The entry ID.
     * @param    string    $question      Optional. Edited question.
     * @param    string    $answer        Optional. Edited answer.
     * @param    string    $topic         Optional. Topic.
     * @return   bool      Whether the entry was approved.
     */
    public function approve_knowledge_entry($entry_id, $question = '', $answer = '', $topic = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Get the entry
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $entry_id
            ),
            ARRAY_A
        );
        
        if (!$entry) {
            return false;
        }
        
        $data = array(
            'approved' => 1,
        );
        
        // Update question/answer/topic if provided
        if (!empty($question)) {
            $data['question'] = $question;
        }
        
        if (!empty($answer)) {
            $data['answer'] = $answer;
        }
        
        if (!empty($topic)) {
            $data['topic'] = $topic;
        }
        
        // Update metadata to reflect manual approval
        $metadata = json_decode($entry['metadata'], true) ?: array();
        $metadata['approved_at'] = current_time('mysql');
        $metadata['approved_by'] = get_current_user_id();
        $data['metadata'] = json_encode($metadata);
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $entry_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Reject a knowledge entry.
     *
     * @since    1.0.0
     * @param    int       $entry_id    The entry ID.
     * @param    string    $reason      Optional. Reason for rejection.
     * @return   bool      Whether the entry was rejected.
     */
    public function reject_knowledge_entry($entry_id, $reason = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Get the entry
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $entry_id
            ),
            ARRAY_A
        );
        
        if (!$entry) {
            return false;
        }
        
        // Update metadata to mark as rejected
        $metadata = json_decode($entry['metadata'], true) ?: array();
        $metadata['rejected'] = true;
        $metadata['rejected_at'] = current_time('mysql');
        $metadata['rejected_by'] = get_current_user_id();
        
        if (!empty($reason)) {
            $metadata['rejection_reason'] = $reason;
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'approved' => 0,
                'metadata' => json_encode($metadata)
            ),
            array('id' => $entry_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Mark a conversation as processed for knowledge extraction.
     *
     * @since    1.0.0
     * @param    string    $session_id    The conversation session ID.
     * @param    int       $entries_count Number of entries extracted.
     * @return   bool      Whether the update was successful.
     */
    private function mark_conversation_processed($session_id, $entries_count = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        // Get current metadata
        $current_metadata = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT metadata FROM $table_name WHERE session_id = %s",
                $session_id
            )
        );
        
        // Handle metadata parsing - check if it's JSON or serialized
        $metadata = array();
        if (!empty($current_metadata)) {
            // Try to decode as JSON first
            $json_metadata = json_decode($current_metadata, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_metadata)) {
                $metadata = $json_metadata;
            } else {
                // If not JSON, try to unserialize
                $unserialized = maybe_unserialize($current_metadata);
                if (is_array($unserialized)) {
                    $metadata = $unserialized;
                }
            }
        }
        
        // Set the knowledge extracted flag
        $metadata['knowledge_extracted'] = true;
        $metadata['extracted_at'] = current_time('mysql');
        $metadata['extracted_entries'] = $entries_count;
        
        // Update metadata - use the same format (JSON or serialized) as the original
        $new_metadata = '';
        if (json_decode($current_metadata) !== null) {
            // If original was JSON, use JSON
            $new_metadata = wp_json_encode($metadata);
        } else {
            // Otherwise use serialization
            $new_metadata = maybe_serialize($metadata);
        }
        
        // Update metadata
        $result = $wpdb->update(
            $table_name,
            array('metadata' => $new_metadata),
            array('session_id' => $session_id),
            array('%s'),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Calculate confidence for a potential knowledge entry.
     *
     * @since    1.0.0
     * @param    string    $question    The question text.
     * @param    string    $answer      The answer text.
     * @return   float     A confidence score between 0 and 1.
     */
    private function calculate_entry_confidence($question, $answer) {
        // This is a simple heuristic approach
        $confidence = 0.5; // Start with a neutral score
        
        // Length-based factors
        $q_length = strlen($question);
        $a_length = strlen($answer);
        
        // Longer questions tend to be more specific
        if ($q_length > 50) $confidence += 0.1;
        if ($q_length > 100) $confidence += 0.05;
        
        // Very short answers are less likely to be good KB entries
        if ($a_length < 30) $confidence -= 0.2;
        if ($a_length > 100) $confidence += 0.1;
        
        // Question form detection
        if (strpos($question, '?') !== false) $confidence += 0.1;
        
        // Question starts with typical question words
        $question_starters = array('what', 'how', 'why', 'when', 'where', 'who', 'which', 'can', 'could', 'should', 'would', 'is', 'are');
        $first_word = strtolower(explode(' ', trim($question))[0]);
        if (in_array($first_word, $question_starters)) $confidence += 0.1;
        
        // Cap confidence between 0 and 1
        return max(0, min(1, $confidence));
    }
}