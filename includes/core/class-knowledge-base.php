<?php
/**
 * Knowledge base management.
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
 * Knowledge Base class.
 *
 * Handles storage and retrieval of knowledge.
 *
 * @since      1.0.0
 */
class Knowledge_Base {

    /**
     * Search the knowledge base for a query.
     *
     * @since    1.0.0
     * @param    string    $query    The search query.
     * @return   array|null    The knowledge base entry or null if not found.
     */
    public function search($query) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Check if we have data in the table first
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count == 0) {
            return null;
        }
        
        // First try exact match
        $exact_match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE approved = 1 AND question = %s 
                LIMIT 1",
                $query
            ),
            ARRAY_A
        );
        
        if ($exact_match) {
            // Found exact match, return with high confidence
            return array(
                'id' => $exact_match['id'],
                'question' => $exact_match['question'],
                'answer' => $exact_match['answer'],
                'topic' => $exact_match['topic'],
                'confidence' => 1.0, // Perfect match
            );
        }
        
        // Try fuzzy search with MySQL's MATCH AGAINST
        $fuzzy_result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *, MATCH(question) AGAINST(%s) AS score 
                FROM $table_name 
                WHERE approved = 1 
                ORDER BY score DESC 
                LIMIT 1",
                $query
            ),
            ARRAY_A
        );
        
        // Check if we found anything with a reasonable score
        if (!$fuzzy_result || empty($fuzzy_result['score']) || $fuzzy_result['score'] < 0.1) {
            // Try a simpler LIKE search as last resort
            $like_term = '%' . $wpdb->esc_like($query) . '%';
            $like_result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name 
                    WHERE approved = 1 AND question LIKE %s 
                    LIMIT 1",
                    $like_term
                ),
                ARRAY_A
            );
            
            if (!$like_result) {
                return null;
            }
            
            // Calculate a confidence score based on the similarity
            $confidence = $this->calculate_similarity($query, $like_result['question']);
            
            return array(
                'id' => $like_result['id'],
                'question' => $like_result['question'],
                'answer' => $like_result['answer'],
                'topic' => $like_result['topic'],
                'confidence' => $confidence,
            );
        }
        
        // Normalize the score to a confidence value between 0 and 1
        $confidence = min(1, max(0, $fuzzy_result['score'] / 10));
        
        return array(
            'id' => $fuzzy_result['id'],
            'question' => $fuzzy_result['question'],
            'answer' => $fuzzy_result['answer'],
            'topic' => $fuzzy_result['topic'],
            'confidence' => $confidence,
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
        // Simple Levenshtein distance-based similarity
        $lev = levenshtein($str1, $str2);
        $max_len = max(strlen($str1), strlen($str2));
        
        if ($max_len === 0) {
            return 1.0;
        }
        
        return 1.0 - ($lev / $max_len);
    }

    /**
     * Add an entry to the knowledge base.
     *
     * @since    1.0.0
     * @param    string    $question     The question.
     * @param    string    $answer       The answer.
     * @param    string    $topic        Optional. The topic.
     * @param    float     $confidence   Optional. The confidence score.
     * @param    bool      $approved     Optional. Whether the entry is approved.
     * @param    string    $metadata     Optional. JSON encoded metadata.
     * @return   int|false    The new entry ID or false on failure.
     */
    public function add_entry($question, $answer, $topic = '', $confidence = 0.5, $approved = false, $metadata = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Ensure metadata is at least an empty object or string
        if (!isset($metadata) || empty($metadata)) {
            $metadata = '{}'; // Store as empty JSON object for consistency
        } elseif (is_object($metadata) || is_array($metadata)) {
            $metadata = json_encode($metadata); // Convert to JSON string if object or array
        }
        
        // No need to manually escape data, $wpdb->insert handles this properly
        $result = $wpdb->insert(
            $table_name,
            array(
                'question' => $question,
                'answer' => $answer,
                'topic' => $topic,
                'confidence' => $confidence,
                'approved' => $approved ? 1 : 0,
                'metadata' => $metadata,
            ),
            array(
                '%s', // question
                '%s', // answer
                '%s', // topic
                '%f', // confidence
                '%d', // approved
                '%s', // metadata
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing knowledge base entry.
     *
     * @since    1.0.0
     * @param    int       $id           The entry ID.
     * @param    array     $data         The data to update.
     * @return   bool    Whether the update was successful.
     */
    public function update_entry($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // If metadata is provided as an array, convert to JSON
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        
        // No need to manually escape data, $wpdb->update handles this properly
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            null, // Format will be determined automatically
            array('%d') // id format
        );
        
        return $result !== false;
    }

    /**
     * Get all knowledge base entries.
     *
     * @since    1.0.0
     * @param    array     $args    Optional. Query arguments.
     * @return   array    The knowledge base entries.
     */
    public function get_entries($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'topic' => '',
            'approved' => null,
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
            'question_exact' => '', // For exact question matching
        );
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $where = array();
        if (!empty($args['topic'])) {
            $where[] = $wpdb->prepare("topic = %s", $args['topic']);
        }
        
        if ($args['approved'] !== null) {
            $where[] = $wpdb->prepare("approved = %d", $args['approved'] ? 1 : 0);
        }
        
        if (!empty($args['question_exact'])) {
            $where[] = $wpdb->prepare("question = %s", $args['question_exact']);
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
        $limit_clause = $wpdb->prepare("LIMIT %d, %d", $args['offset'], $args['limit']);
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $limit_clause";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get a count of knowledge base entries.
     *
     * @since    1.0.0
     * @param    array     $args    Optional. Query arguments.
     * @return   int    The count of entries.
     */
    public function get_entries_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'topic' => '',
            'approved' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $where = array();
        if (!empty($args['topic'])) {
            $where[] = $wpdb->prepare("topic = %s", $args['topic']);
        }
        
        if ($args['approved'] !== null) {
            $where[] = $wpdb->prepare("approved = %d", $args['approved'] ? 1 : 0);
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT COUNT(*) FROM $table_name $where_clause";
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Delete a knowledge base entry.
     *
     * @since    1.0.0
     * @param    int       $id    The entry ID.
     * @return   bool    Whether the deletion was successful.
     */
    public function delete_entry($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Update the usage count for a knowledge base entry.
     *
     * @since    1.0.0
     * @param    int       $id    The entry ID.
     * @return   bool    Whether the update was successful.
     */
    public function increment_usage_count($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET usage_count = usage_count + 1 WHERE id = %d",
                $id
            )
        );
        
        return $result !== false;
    }
}