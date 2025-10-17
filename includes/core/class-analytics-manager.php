<?php
/**
 * Analytics manager for tracking conversation data.
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
 * Analytics Manager class.
 *
 * Handles tracking and storing of analytics data.
 *
 * @since      1.0.0
 */
class Analytics_Manager {

    /**
     * Track a message and response.
     *
     * @since    1.0.0
     * @param    string    $query       The user query.
     * @param    array     $response    The response data.
     * @param    string    $session_id  The conversation session ID.
     * @param    string    $channel     The communication channel.
     */
    public function track_message($query, $response, $session_id, $channel = 'webchat') {
        $this->increment_daily_counter('message_count', $channel);
        
        if (isset($response['source']) && $response['source'] === 'ai') {
            $this->increment_daily_counter('ai_request_count', $channel);
        } elseif (isset($response['source']) && ($response['source'] === 'knowledge_base' || $response['source'] === 'knowledge_base_fallback')) {
            $this->increment_daily_counter('kb_answer_count', $channel);
        }
        
        // Track the query for trending questions
        $this->track_query($query, $channel);
    }

    /**
     * Track a new conversation.
     *
     * @since    1.0.0
     * @param    string    $session_id  The conversation session ID.
     * @param    string    $channel     The communication channel.
     */
    public function track_new_conversation($session_id, $channel = 'webchat') {
        $this->increment_daily_counter('conversation_count', $channel);
    }

    /**
     * Track a successful conversation.
     *
     * @since    1.0.0
     * @param    string    $session_id  The conversation session ID.
     * @param    string    $channel     The communication channel.
     * @param    float     $score       Optional. The success score (0-1).
     */
    public function track_successful_conversation($session_id, $channel = 'webchat', $score = 1.0) {
        $this->increment_daily_counter('successful_conversation_count', $channel);
        
        // Store the success score for the conversation
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, metadata FROM $table_name WHERE session_id = %s",
                $session_id
            ),
            ARRAY_A
        );
        
        if ($conversation) {
            $metadata = maybe_unserialize($conversation['metadata']);
            $metadata['success_score'] = $score;
            
            $wpdb->update(
                $table_name,
                array('metadata' => maybe_serialize($metadata)),
                array('id' => $conversation['id']),
                array('%s'),
                array('%d')
            );
        }
    }

    /**
     * Track a query for trending questions.
     *
     * @since    1.0.0
     * @param    string    $query       The user query.
     * @param    string    $channel     The communication channel.
     */
    private function track_query($query, $channel = 'webchat') {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        // Get today's record
        $today = date('Y-m-d');
        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, metadata FROM $table_name WHERE date = %s AND source = %s",
                $today,
                $channel
            ),
            ARRAY_A
        );
        
        if ($record) {
            $metadata = maybe_unserialize($record['metadata']);
            
            if (!isset($metadata['queries'])) {
                $metadata['queries'] = array();
            }
            
            // Sanitize and normalize the query
            $sanitized_query = trim(strtolower($query));
            
            // Skip very short queries
            if (strlen($sanitized_query) < 3) {
                return;
            }
            
            // Increment query count or add new
            $found = false;
            foreach ($metadata['queries'] as &$q) {
                // Simple "fuzzy" match - if query is at least 80% similar to existing one
                $similarity = $this->calculate_similarity($sanitized_query, $q['query']);
                if ($similarity > 0.8) {
                    $q['count']++;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $metadata['queries'][] = array(
                    'query' => $sanitized_query,
                    'count' => 1,
                );
            }
            
            // Limit the number of stored queries to prevent huge metadata
            if (count($metadata['queries']) > 100) {
                usort($metadata['queries'], function($a, $b) {
                    return $b['count'] - $a['count'];
                });
                
                $metadata['queries'] = array_slice($metadata['queries'], 0, 100);
            }
            
            $wpdb->update(
                $table_name,
                array('metadata' => maybe_serialize($metadata)),
                array('id' => $record['id']),
                array('%s'),
                array('%d')
            );
        }
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
     * Increment a daily counter.
     *
     * @since    1.0.0
     * @param    string    $counter     The counter to increment.
     * @param    string    $channel     The communication channel.
     */
    private function increment_daily_counter($counter, $channel = 'webchat') {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        // Get today's record
        $today = date('Y-m-d');
        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE date = %s AND source = %s",
                $today,
                $channel
            ),
            ARRAY_A
        );
        
        if ($record) {
            // Update existing record
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_name SET $counter = $counter + 1 WHERE id = %d",
                    $record['id']
                )
            );
        } else {
            // Create new record
            $data = array(
                'date' => $today,
                'source' => $channel,
                $counter => 1,
                'metadata' => maybe_serialize(array('queries' => array())),
            );
            
            $wpdb->insert($table_name, $data);
        }
    }

    /**
     * Get analytics data.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date (Y-m-d).
     * @param    string    $end_date      The end date (Y-m-d).
     * @param    string    $channel       Optional. Filter by channel.
     * @return   array     The analytics data.
     */
    public function get_analytics($start_date, $end_date, $channel = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        $where = array(
            $wpdb->prepare("date >= %s", $start_date),
            $wpdb->prepare("date <= %s", $end_date),
        );
        
        if (!empty($channel)) {
            $where[] = $wpdb->prepare("source = %s", $channel);
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where);
        
        $results = $wpdb->get_results(
            "SELECT date, source, conversation_count, message_count, ai_request_count, kb_answer_count, successful_conversation_count, metadata
            FROM $table_name
            $where_clause
            ORDER BY date ASC",
            ARRAY_A
        );
        
        // Process results
        $data = array(
            'by_date' => array(),
            'by_channel' => array(),
            'totals' => array(
                'conversation_count' => 0,
                'message_count' => 0,
                'ai_request_count' => 0,
                'kb_answer_count' => 0,
                'successful_conversation_count' => 0,
            ),
            'success_rate' => 0,
            'kb_usage_rate' => 0,
            'trending_queries' => array(),
        );
        
        $all_queries = array();
        
        foreach ($results as $row) {
            // Add to date-based data
            if (!isset($data['by_date'][$row['date']])) {
                $data['by_date'][$row['date']] = array(
                    'conversation_count' => 0,
                    'message_count' => 0,
                    'ai_request_count' => 0,
                    'kb_answer_count' => 0,
                    'successful_conversation_count' => 0,
                );
            }
            
            // Add to channel-based data
            if (!isset($data['by_channel'][$row['source']])) {
                $data['by_channel'][$row['source']] = array(
                    'conversation_count' => 0,
                    'message_count' => 0,
                    'ai_request_count' => 0,
                    'kb_answer_count' => 0,
                    'successful_conversation_count' => 0,
                );
            }
            
            // Update counters
            foreach (array('conversation_count', 'message_count', 'ai_request_count', 'kb_answer_count', 'successful_conversation_count') as $counter) {
                $count = isset($row[$counter]) ? intval($row[$counter]) : 0;
                $data['by_date'][$row['date']][$counter] += $count;
                $data['by_channel'][$row['source']][$counter] += $count;
                $data['totals'][$counter] += $count;
            }
            
            // Extract queries for trending analysis
            $metadata = maybe_unserialize($row['metadata']);
            if (isset($metadata['queries']) && is_array($metadata['queries'])) {
                foreach ($metadata['queries'] as $query_data) {
                    if (isset($query_data['query']) && isset($query_data['count'])) {
                        $query = $query_data['query'];
                        $count = $query_data['count'];
                        
                        if (isset($all_queries[$query])) {
                            $all_queries[$query] += $count;
                        } else {
                            $all_queries[$query] = $count;
                        }
                    }
                }
            }
        }
        
        // Calculate success rate
        if ($data['totals']['conversation_count'] > 0) {
            $data['success_rate'] = $data['totals']['successful_conversation_count'] / $data['totals']['conversation_count'];
        }
        
        // Calculate KB usage rate
        if ($data['totals']['message_count'] > 0) {
            $data['kb_usage_rate'] = $data['totals']['kb_answer_count'] / $data['totals']['message_count'];
        }
        
        // Get top trending queries
        arsort($all_queries);
        $trending = array();
        $count = 0;
        
        foreach ($all_queries as $query => $frequency) {
            $trending[] = array(
                'query' => $query,
                'count' => $frequency,
            );
            
            $count++;
            if ($count >= 20) {
                break;
            }
        }
        
        $data['trending_queries'] = $trending;
        
        return $data;
    }

    /**
     * Get conversation success metrics.
     *
     * @since    1.0.0
     * @return   array     Conversation success metrics.
     */
    public function get_conversation_success_metrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        // Get total number of conversations with and without success scores
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get conversations with success scores
        $success_sql = "
            SELECT COUNT(*) as count, 
                   AVG(CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2))) as avg_score,
                   MAX(CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2))) as max_score,
                   MIN(CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2))) as min_score
            FROM $table_name
            WHERE JSON_EXTRACT(metadata, '$.success_score') IS NOT NULL
        ";
        
        // Use simpler query if JSON functions are not available (older MySQL)
        if ($wpdb->last_error) {
            $conversations = $wpdb->get_results(
                "SELECT metadata FROM $table_name",
                ARRAY_A
            );
            
            $scored_count = 0;
            $score_sum = 0;
            $max_score = 0;
            $min_score = 1;
            
            foreach ($conversations as $conversation) {
                $metadata = maybe_unserialize($conversation['metadata']);
                if (isset($metadata['success_score'])) {
                    $scored_count++;
                    $score = floatval($metadata['success_score']);
                    $score_sum += $score;
                    $max_score = max($max_score, $score);
                    $min_score = min($min_score, $score);
                }
            }
            
            $avg_score = $scored_count > 0 ? $score_sum / $scored_count : 0;
            
            $success_data = array(
                'count' => $scored_count,
                'avg_score' => $avg_score,
                'max_score' => $max_score,
                'min_score' => $min_score,
            );
        } else {
            $success_data = $wpdb->get_row($success_sql, ARRAY_A);
        }
        
        // Calculate score distribution
        $distribution = array(
            'excellent' => 0,  // 0.8 - 1.0
            'good' => 0,       // 0.6 - 0.8
            'average' => 0,    // 0.4 - 0.6
            'poor' => 0,       // 0.2 - 0.4
            'very_poor' => 0,  // 0.0 - 0.2
        );
        
        // Use JSON query if available
        $distribution_sql = "
            SELECT 
                SUM(CASE WHEN CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) >= 0.8 THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) >= 0.6 AND CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) < 0.8 THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) >= 0.4 AND CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) < 0.6 THEN 1 ELSE 0 END) as average,
                SUM(CASE WHEN CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) >= 0.2 AND CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) < 0.4 THEN 1 ELSE 0 END) as poor,
                SUM(CASE WHEN CAST(JSON_EXTRACT(metadata, '$.success_score') AS DECIMAL(10,2)) < 0.2 THEN 1 ELSE 0 END) as very_poor
            FROM $table_name
            WHERE JSON_EXTRACT(metadata, '$.success_score') IS NOT NULL
        ";
        
        // Check for database error and process accordingly
        if ($wpdb->last_error) {
            $conversations = $conversations ?? []; // Default to empty array if null/undefined
            foreach ($conversations as $conversation) {
                $metadata = maybe_unserialize($conversation['metadata'] ?? ''); // Default to empty string if null
                if (isset($metadata['success_score'])) {
                    $score = floatval($metadata['success_score']);
                    
                    if ($score >= 0.8) {
                        $distribution['excellent']++;
                    } elseif ($score >= 0.6) {
                        $distribution['good']++;
                    } elseif ($score >= 0.4) {
                        $distribution['average']++;
                    } elseif ($score >= 0.2) {
                        $distribution['poor']++;
                    } else {
                        $distribution['very_poor']++;
                    }
                }
            }
        } else {
            $dist_data = $wpdb->get_row($distribution_sql, ARRAY_A) ?? []; // Default to empty array if null
            $distribution = $dist_data ?: $distribution; // Use $dist_data if not empty, else keep default
        }

        return array(
            'total_conversations' => $total_count ?? 0,
            'scored_conversations' => $success_data['count'] ?? 0,
            'avg_score' => $success_data['avg_score'] ?? 0.0,
            'max_score' => $success_data['max_score'] ?? 0.0,
            'min_score' => $success_data['min_score'] ?? 0.0,
            'distribution' => $distribution ?? [],
        );
    }

    /**
     * Track a context-enhanced AI message.
     *
     * @since    1.0.0
     * @param    string    $query              The user query.
     * @param    array     $response           The response data.
     * @param    array     $context_sources    The sources used for context.
     * @param    string    $session_id         The conversation session ID.
     * @param    string    $channel            The communication channel.
     */
    public function track_context_enhanced_message($query, $response, $context_sources, $session_id, $channel = 'webchat') {
        $this->increment_daily_counter('context_enhanced_count', $channel);
        
        // Track usage of different context sources
        foreach ($context_sources as $source) {
            $counter_name = 'context_source_' . $source;
            $this->increment_daily_counter($counter_name, $channel);
        }
        
        // Track the query for trending questions
        $this->track_query($query, $channel);
        
        // Update metadata for analytics
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        // Get today's record
        $today = date('Y-m-d');
        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, metadata FROM $table_name WHERE date = %s AND source = %s",
                $today,
                $channel
            ),
            ARRAY_A
        );
        
        if ($record) {
            $metadata = maybe_unserialize($record['metadata']);
            
            if (!isset($metadata['context_sources'])) {
                $metadata['context_sources'] = array();
            }
            
            // Track context source usage
            foreach ($context_sources as $source) {
                if (!isset($metadata['context_sources'][$source])) {
                    $metadata['context_sources'][$source] = 0;
                }
                $metadata['context_sources'][$source]++;
            }
            
            $wpdb->update(
                $table_name,
                array('metadata' => maybe_serialize($metadata)),
                array('id' => $record['id']),
                array('%s'),
                array('%d')
            );
        }
    }

}