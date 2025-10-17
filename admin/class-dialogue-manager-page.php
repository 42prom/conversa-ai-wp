<?php
/**
 * Dialogue Manager page functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Conversation_Manager;
use ConversaAI_Pro_WP\Core\Analytics_Manager;

/**
 * Dialogue Manager page class.
 *
 * Handles the dialogue management functionality.
 *
 * @since      1.0.0
 */
class Dialogue_Manager_Page {

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
        
        // Add AJAX handlers for the dialogue manager
        add_action('wp_ajax_conversaai_get_dialogues', array($this, 'ajax_get_dialogues'));
        add_action('wp_ajax_conversaai_get_dialogue_details', array($this, 'ajax_get_dialogue_details'));
        add_action('wp_ajax_conversaai_bulk_action_dialogues', array($this, 'ajax_bulk_action_dialogues'));
        
        // Add AJAX handlers for the learning system
        add_action('wp_ajax_conversaai_approve_knowledge_entry', array($this, 'ajax_approve_knowledge_entry'));
        add_action('wp_ajax_conversaai_reject_knowledge_entry', array($this, 'ajax_reject_knowledge_entry'));
        add_action('wp_ajax_conversaai_update_learning_settings', array($this, 'ajax_update_learning_settings'));
        add_action('wp_ajax_conversaai_batch_process_knowledge_entries', array($this, 'ajax_batch_process_knowledge_entries'));
        add_action('wp_ajax_conversaai_extract_knowledge', array($this, 'ajax_extract_knowledge'));
        add_action('wp_ajax_conversaai_get_pending_knowledge_entries', array($this, 'ajax_get_pending_knowledge_entries'));

        // Add AJAX handlers for the trigger words management
        add_action('wp_ajax_conversaai_save_trigger_word', array($this, 'ajax_save_trigger_word'));
        add_action('wp_ajax_conversaai_delete_trigger_word', array($this, 'ajax_delete_trigger_word'));
        add_action('wp_ajax_conversaai_get_trigger_words', array($this, 'ajax_get_trigger_words'));

        // Add AJAX handlers for trigger word import/export
        add_action('wp_ajax_conversaai_import_trigger_words', array($this, 'ajax_import_trigger_words'));
        add_action('wp_ajax_conversaai_export_trigger_words', array($this, 'ajax_export_trigger_words'));
    }

    /**
     * Display the dialogue manager page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Get initial data for the page
        $analytics_manager = new Analytics_Manager();
        $success_metrics = $analytics_manager->get_conversation_success_metrics();
        
        // Get recent conversations for initial load
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        $recent_conversations = $wpdb->get_results(
            "SELECT id, session_id, user_id, channel, created_at, updated_at, 
                metadata
            FROM $table_name 
            ORDER BY updated_at DESC 
            LIMIT 20",
            ARRAY_A
        );
        
        // Process metadata to extract success score
        foreach ($recent_conversations as &$convo) {
            $metadata = maybe_unserialize($convo['metadata']);
            $convo['success_score'] = isset($metadata['success_score']) ? floatval($metadata['success_score']) : 0;
            
            // Get user info
            if (!empty($convo['user_id'])) {
                $user = get_user_by('id', $convo['user_id']);
                if ($user) {
                    $convo['user_name'] = $user->display_name;
                    $convo['user_email'] = $user->user_email;
                } else {
                    $convo['user_name'] = __('Guest', 'conversaai-pro-wp');
                    $convo['user_email'] = '';
                }
            } else {
                $convo['user_name'] = __('Guest', 'conversaai-pro-wp');
                $convo['user_email'] = '';
            }
        }
        
        // Detect active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'conversations';
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/dialogue-manager-page.php';
        
        // Load the tab-specific content
        if ($active_tab == 'learning') {
            $this->display_learning_tab();
        } elseif ($active_tab == 'trigger_words') {
            $this->display_trigger_words_tab();
        }
    }

    /**
     * Display the learning tab content.
     *
     * @since    1.0.0
     */
    public function display_learning_tab() {
        // Get potential knowledge entries waiting for review
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        $pending_entries = $learning_engine->get_pending_knowledge_entries();
        $stats = $learning_engine->get_learning_stats();
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/learning-tab.php';
    }

    /**
     * Display the trigger words tab content.
     *
     * @since    1.0.0
     */
    public function display_trigger_words_tab() {
        // Get trigger words from the database
        $trigger_words = get_option('conversaai_pro_trigger_words', array());
        $categories = array();
        
        // Extract unique categories
        if (!empty($trigger_words)) {
            foreach ($trigger_words as $word) {
                if (isset($word['category']) && !empty($word['category']) && !in_array($word['category'], $categories)) {
                    $categories[] = $word['category'];
                }
            }
        }
        
        // Load the trigger words tab template
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/trigger-words-tab.php';
    }

    /**
     * AJAX handler for approving knowledge entries.
     *
     * @since    1.0.0
     */
    public function ajax_approve_knowledge_entry() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to approve knowledge entries.', 'conversaai-pro-wp')));
        }
        
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $edited_question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $edited_answer = isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '';
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        
        if ($entry_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid entry ID.', 'conversaai-pro-wp')));
        }
        
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        $result = $learning_engine->approve_knowledge_entry($entry_id, $edited_question, $edited_answer, $topic);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Knowledge entry approved and added to knowledge base.', 'conversaai-pro-wp')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve knowledge entry.', 'conversaai-pro-wp')));
        }
    }

    /**
     * AJAX handler for rejecting knowledge entries.
     *
     * @since    1.0.0
     */
    public function ajax_reject_knowledge_entry() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to reject knowledge entries.', 'conversaai-pro-wp')));
        }
        
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        if ($entry_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid entry ID.', 'conversaai-pro-wp')));
        }
        
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        $result = $learning_engine->reject_knowledge_entry($entry_id, $reason);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Knowledge entry rejected.', 'conversaai-pro-wp')));
        } else {
            wp_send_json_error(array('message' => __('Failed to reject knowledge entry.', 'conversaai-pro-wp')));
        }
    }

    /**
     * AJAX handler for updating learning settings.
     *
     * @since    1.0.0
     */
    public function ajax_update_learning_settings() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to update learning settings.', 'conversaai-pro-wp')));
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Sanitize settings
        $sanitized = array(
            'auto_extraction' => isset($settings['auto_extraction']) ? (bool) $settings['auto_extraction'] : false,
            'min_confidence' => isset($settings['min_confidence']) ? floatval($settings['min_confidence']) : 0.7,
            'auto_approve' => isset($settings['auto_approve']) ? (bool) $settings['auto_approve'] : false,
            'min_auto_approve_confidence' => isset($settings['min_auto_approve_confidence']) ? floatval($settings['min_auto_approve_confidence']) : 0.9,
        );
        
        update_option('conversaai_pro_learning_settings', $sanitized);
        
        wp_send_json_success(array(
            'message' => __('Learning settings updated successfully.', 'conversaai-pro-wp'),
            'settings' => $sanitized
        ));
    }

    /**
     * AJAX handler for batch processing knowledge entries.
     *
     * @since    1.0.0
     */
    public function ajax_batch_process_knowledge_entries() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to process knowledge entries.', 'conversaai-pro-wp')));
        }
        
        $action = isset($_POST['batch_action']) ? sanitize_text_field($_POST['batch_action']) : '';
        $entry_ids = isset($_POST['entry_ids']) ? array_map('intval', $_POST['entry_ids']) : array();
        
        if (empty($action) || empty($entry_ids)) {
            wp_send_json_error(array('message' => __('Invalid action or no entries selected.', 'conversaai-pro-wp')));
        }
        
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        
        $processed = 0;
        $errors = 0;
        
        foreach ($entry_ids as $id) {
            if ($action === 'approve') {
                $result = $learning_engine->approve_knowledge_entry($id);
            } else if ($action === 'reject') {
                $result = $learning_engine->reject_knowledge_entry($id);
            } else {
                continue;
            }
            
            if ($result) {
                $processed++;
            } else {
                $errors++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Processed %d entries with %d errors.', 'conversaai-pro-wp'),
                $processed,
                $errors
            ),
            'processed' => $processed,
            'errors' => $errors
        ));
    }

    /**
     * AJAX handler for getting dialogues.
     *
     * @since    1.0.0
     */
    public function ajax_get_dialogues() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage dialogues.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        $min_score = isset($_POST['min_score']) ? floatval($_POST['min_score']) : 0;
        $max_score = isset($_POST['max_score']) ? floatval($_POST['max_score']) : 1;
        
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        // Build WHERE clause
        $where_clauses = array();
        $where_values = array();

        if (!empty($start_date)) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $start_date; 
        }

        if (!empty($end_date)) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $end_date; 
        }
        
        if (!empty($channel)) {
            $where_clauses[] = 'channel = %s';
            $where_values[] = $channel;
        }
        
        // Build query
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
            $where_sql = $wpdb->prepare($where_sql, $where_values);
        }
        
        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table_name $where_sql";
        $total = $wpdb->get_var($total_query);
        
        // Get paginated data
        $offset = ($page - 1) * $per_page;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, session_id, user_id, channel, created_at, updated_at, metadata 
                FROM $table_name 
                $where_sql
                ORDER BY updated_at DESC 
                LIMIT %d, %d",
                $offset,
                $per_page
            ),
            ARRAY_A
        );
        
        // Process results to add additional data
        $dialogues = array();
        foreach ($results as $row) {
            $metadata = maybe_unserialize($row['metadata']);
            $success_score = isset($metadata['success_score']) ? floatval($metadata['success_score']) : 0;
            
            // Filter by success score if needed
            if ($success_score < $min_score || $success_score > $max_score) {
                continue;
            }
            
            // Get user info
            $user_name = __('Guest', 'conversaai-pro-wp');
            $user_email = '';
            if (!empty($row['user_id'])) {
                $user = get_user_by('id', $row['user_id']);
                if ($user) {
                    $user_name = $user->display_name;
                    $user_email = $user->user_email;
                }
            }
            
            $dialogues[] = array(
                'id' => $row['id'],
                'session_id' => $row['session_id'],
                'user_id' => $row['user_id'],
                'user_name' => $user_name,
                'user_email' => $user_email,
                'channel' => $row['channel'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'success_score' => $success_score,
            );
        }
        
        // Send response
        wp_send_json_success(array(
            'dialogues' => $dialogues,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ));
    }

    /**
     * AJAX handler for getting dialogue details.
     *
     * @since    1.0.0
     */
    public function ajax_get_dialogue_details() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to view dialogue details.', 'conversaai-pro-wp')));
        }
        
        // Get session ID
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('No session ID provided.', 'conversaai-pro-wp')));
        }
        
        // Get conversation details
        $conversation = new Conversation_Manager($session_id);
        $messages = $conversation->get_conversation_history();
        $metadata = $conversation->get_metadata();
        
        // Get user info if available
        $user_info = array();
        if (!empty($metadata['user_id'])) {
            $user = get_user_by('id', $metadata['user_id']);
            if ($user) {
                $user_info = array(
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'role' => implode(', ', $user->roles),
                );
            }
        }
        
        wp_send_json_success(array(
            'session_id' => $session_id,
            'messages' => $messages,
            'metadata' => $metadata,
            'user_info' => $user_info,
        ));
    }

    /**
     * AJAX handler for bulk actions on dialogues.
     *
     * @since    1.0.0
     */
    public function ajax_bulk_action_dialogues() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform bulk actions.', 'conversaai-pro-wp')));
        }
        
        // Get action and session IDs
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $session_ids = isset($_POST['session_ids']) ? array_map('sanitize_text_field', $_POST['session_ids']) : array();
        
        if (empty($action) || empty($session_ids)) {
            wp_send_json_error(array('message' => __('Missing action or session IDs.', 'conversaai-pro-wp')));
        }
        
        // Process based on action
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        $result = false;
        
        switch ($action) {
            case 'delete':
                // Delete the conversations
                $placeholders = implode(',', array_fill(0, count($session_ids), '%s'));
                $query = $wpdb->prepare(
                    "DELETE FROM $table_name WHERE session_id IN ($placeholders)",
                    $session_ids
                );
                $result = $wpdb->query($query);
                break;
                
            case 'archive':
                // Mark as archived (update metadata)
                $updated = 0;
                foreach ($session_ids as $session_id) {
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
                    
                    // Set the archived flag
                    $metadata['archived'] = true;
                    $metadata['archived_at'] = current_time('mysql');
                    
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
                    $update_result = $wpdb->update(
                        $table_name,
                        array('metadata' => $new_metadata),
                        array('session_id' => $session_id),
                        array('%s'),
                        array('%s')
                    );
                    
                    if ($update_result) {
                        $updated++;
                    }
                }
                $result = $updated;
                break;
                
            case 'flag':
                // Flag conversations for review
                $updated = 0;
                foreach ($session_ids as $session_id) {
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
                    
                    // Set the flagged flag
                    $metadata['flagged'] = true;
                    $metadata['flagged_at'] = current_time('mysql');
                    
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
                    $update_result = $wpdb->update(
                        $table_name,
                        array('metadata' => $new_metadata),
                        array('session_id' => $session_id),
                        array('%s'),
                        array('%s')
                    );
                    
                    if ($update_result) {
                        $updated++;
                    }
                }
                $result = $updated;
                break;
            
                case 'extract_knowledge':
                    // Extract knowledge from conversations
                    $extracted = 0;
                    $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
                    
                    foreach ($session_ids as $session_id) {
                        // Analyze the conversation for potential knowledge
                        $analysis = $learning_engine->analyze_conversation($session_id);
                        
                        // If analysis was successful and has potential entries
                        if (isset($analysis['potential_entries']) && !empty($analysis['potential_entries'])) {
                            // Add the entries to the knowledge base (as pending)
                            $result = $learning_engine->add_potential_entries($analysis['potential_entries'], false);
                            $extracted += $result['added'];
                            
                            // Mark the conversation as processed
                            $current_metadata = $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT metadata FROM $table_name WHERE session_id = %s",
                                    $session_id
                                )
                            );
                            
                            // Handle metadata parsing (JSON or serialized)
                            $metadata = array();
                            if (!empty($current_metadata)) {
                                $json_metadata = json_decode($current_metadata, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($json_metadata)) {
                                    $metadata = $json_metadata;
                                } else {
                                    $unserialized = maybe_unserialize($current_metadata);
                                    if (is_array($unserialized)) {
                                        $metadata = $unserialized;
                                    }
                                }
                            }
                            
                            // Set the knowledge extracted flag
                            $metadata['knowledge_extracted'] = true;
                            $metadata['extracted_at'] = current_time('mysql');
                            $metadata['extracted_entries'] = $result['added'];
                            
                            // Update metadata
                            $new_metadata = '';
                            if (json_decode($current_metadata) !== null) {
                                $new_metadata = wp_json_encode($metadata);
                            } else {
                                $new_metadata = maybe_serialize($metadata);
                            }
                            
                            $wpdb->update(
                                $table_name,
                                array('metadata' => $new_metadata),
                                array('session_id' => $session_id),
                                array('%s'),
                                array('%s')
                            );
                        }
                    }
                    $result = $extracted;
                    break;                
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully processed %d dialogues.', 'conversaai-pro-wp'), is_numeric($result) ? $result : count($session_ids)),
                'action' => $action,
                'count' => is_numeric($result) ? $result : count($session_ids),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to process dialogues.', 'conversaai-pro-wp')));
        }
    }

    /**
     * AJAX handler for extracting knowledge.
     *
     * @since    1.0.0
     */
    public function ajax_extract_knowledge() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to extract knowledge.', 'conversaai-pro-wp')));
        }
        
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'recent';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $force_reprocess = isset($_POST['force_reprocess']) ? (bool)$_POST['force_reprocess'] : false;
        
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        
        if ($source === 'specific' && !empty($session_id)) {
            // Extract from specific conversation
            $result = $learning_engine->extract_knowledge_from_conversation($session_id);
            
            // If already processed and not forcing reprocess, inform the user
            if (isset($result['already_processed']) && $result['already_processed'] && !$force_reprocess) {
                $message = __('This conversation has already been processed. Use force reprocess option to extract again.', 'conversaai-pro-wp');
                wp_send_json_error(array('message' => $message));
            }
            
            wp_send_json_success(array(
                'conversations_processed' => 1,
                'total_extracted' => $result['extracted'],
                'auto_approved' => $result['auto_approved'],
                'skipped' => $result['skipped'],
                'details' => array($result)
            ));
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
            $where_clauses = array();
            
            // Skip already processed conversations unless forcing reprocess
            if (!$force_reprocess) {
                $where_clauses[] = "JSON_EXTRACT(metadata, '$.knowledge_extracted') IS NULL";
            }
            
            // Define query based on source
            if ($source === 'high_score') {
                $where_clauses[] = "JSON_EXTRACT(metadata, '$.success_score') >= 0.8";
            } else {
                // Recent conversations (last 7 days)
                $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            }
            
            // Build WHERE clause
            $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
            
            // Fallback for databases without JSON functions
            if ($wpdb->last_error) {
                // Simpler query without JSON extraction
                $where_clause = $source === 'high_score' 
                    ? "" // We'll filter by score manually
                    : "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            }
            
            // Get conversations
            $conversations = $wpdb->get_results(
                "SELECT session_id, metadata FROM $table_name $where_clause 
                ORDER BY created_at DESC LIMIT 10",
                ARRAY_A
            );
            
            // Process each conversation
            $total_extracted = 0;
            $total_auto_approved = 0;
            $total_skipped = 0;
            $details = array();
            $processed_count = 0;
            
            foreach ($conversations as $conversation) {
                $metadata = json_decode($conversation['metadata'], true) ?: array();
                
                // For high_score source with fallback, check the score manually
                if ($source === 'high_score' && $wpdb->last_error) {
                    $success_score = isset($metadata['success_score']) ? floatval($metadata['success_score']) : 0;
                    
                    if ($success_score < 0.8) {
                        continue;
                    }
                }
                
                // Skip already processed unless forcing reprocess
                if (!$force_reprocess && isset($metadata['knowledge_extracted']) && $metadata['knowledge_extracted']) {
                    continue;
                }
                
                $result = $learning_engine->extract_knowledge_from_conversation($conversation['session_id']);
                $processed_count++;
                
                // Count the results even if no entries were extracted
                $total_extracted += $result['extracted'];
                $total_auto_approved += $result['auto_approved'];
                $total_skipped += isset($result['skipped']) ? $result['skipped'] : 0;
                $details[] = $result;
            }
            
            // If no conversations were processed
            if ($processed_count === 0) {
                $message = $force_reprocess 
                    ? __('No matching conversations found.', 'conversaai-pro-wp')
                    : __('No unprocessed conversations found. Try using the force reprocess option.', 'conversaai-pro-wp');
                
                wp_send_json_error(array('message' => $message));
            }
            
            wp_send_json_success(array(
                'conversations_processed' => $processed_count,
                'total_extracted' => $total_extracted,
                'auto_approved' => $total_auto_approved,
                'skipped' => $total_skipped,
                'details' => $details
            ));
        }
    }
    
    /**
     * AJAX handler for getting pending knowledge entries.
     *
     * @since    1.0.0
     */
    public function ajax_get_pending_knowledge_entries() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to view pending entries.', 'conversaai-pro-wp')));
        }
        
        $learning_engine = new \ConversaAI_Pro_WP\Core\Learning_Engine();
        $entries = $learning_engine->get_pending_knowledge_entries();
        $stats = $learning_engine->get_learning_stats();
        
        wp_send_json_success(array(
            'entries' => $entries,
            'stats' => $stats
        ));
    }

    /**
     * AJAX handler for saving a trigger word.
     *
     * @since    1.0.0
     */
    public function ajax_save_trigger_word() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to save trigger words.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : uniqid('trigger_');
        $word = isset($_POST['word']) ? sanitize_text_field($_POST['word']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $responses = isset($_POST['responses']) ? array_map('sanitize_textarea_field', $_POST['responses']) : array();
        $follow_ups = isset($_POST['follow_ups']) ? array_map('sanitize_textarea_field', $_POST['follow_ups']) : array();
        $active = isset($_POST['active']) ? (bool) $_POST['active'] : true;
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 10;
        $match_type = isset($_POST['match_type']) ? sanitize_text_field($_POST['match_type']) : 'exact';
        
        if (empty($word)) {
            wp_send_json_error(array('message' => __('Trigger word cannot be empty.', 'conversaai-pro-wp')));
        }
        
        // Get current trigger words
        $trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Create or update trigger word
        $trigger_word = array(
            'id' => $id,
            'word' => $word,
            'category' => $category,
            'description' => $description,
            'responses' => $responses,
            'follow_ups' => $follow_ups,
            'active' => $active,
            'priority' => $priority,
            'match_type' => $match_type,
            'created' => isset($trigger_words[$id]['created']) ? $trigger_words[$id]['created'] : current_time('mysql'),
            'modified' => current_time('mysql'),
        );
        
        $trigger_words[$id] = $trigger_word;
        
        // Save to options
        update_option('conversaai_pro_trigger_words', $trigger_words);
        
        wp_send_json_success(array(
            'message' => __('Trigger word saved successfully.', 'conversaai-pro-wp'),
            'trigger_word' => $trigger_word,
        ));
    }

    /**
     * AJAX handler for deleting a trigger word.
     *
     * @since    1.0.0
     */
    public function ajax_delete_trigger_word() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete trigger words.', 'conversaai-pro-wp')));
        }
        
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        
        if (empty($id)) {
            wp_send_json_error(array('message' => __('No trigger word ID provided.', 'conversaai-pro-wp')));
        }
        
        // Get current trigger words
        $trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Check if trigger word exists
        if (!isset($trigger_words[$id])) {
            wp_send_json_error(array('message' => __('Trigger word not found.', 'conversaai-pro-wp')));
        }
        
        // Remove the trigger word
        unset($trigger_words[$id]);
        
        // Save to options
        update_option('conversaai_pro_trigger_words', $trigger_words);
        
        wp_send_json_success(array('message' => __('Trigger word deleted successfully.', 'conversaai-pro-wp')));
    }

    /**
     * AJAX handler for getting trigger words.
     *
     * @since    1.0.0
     */
    public function ajax_get_trigger_words() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to view trigger words.', 'conversaai-pro-wp')));
        }
        
        // Get optional category filter
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // Get all trigger words
        $trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Debug
        error_log('Retrieved ' . count($trigger_words) . ' trigger words');
        
        // Filter by category if specified
        if (!empty($category)) {
            $filtered_words = array();
            foreach ($trigger_words as $id => $word) {
                if (isset($word['category']) && $word['category'] === $category) {
                    $filtered_words[$id] = $word;
                }
            }
            $trigger_words = $filtered_words;
        }
        
        // Extract unique categories
        $categories = array();
        foreach ($trigger_words as $word) {
            if (isset($word['category']) && !empty($word['category']) && !in_array($word['category'], $categories)) {
                $categories[] = $word['category'];
            }
        }
        
        // Sort categories alphabetically
        sort($categories);
        
        wp_send_json_success(array(
            'trigger_words' => $trigger_words,
            'categories' => $categories,
        ));
    }

    /**
     * AJAX handler for importing trigger words.
     *
     * @since    1.0.0
     */
    public function ajax_import_trigger_words() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to import trigger words.', 'conversaai-pro-wp')));
        }
        
        // Get import parameters
        $file_content = isset($_POST['file_content']) ? $_POST['file_content'] : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $has_header = isset($_POST['has_header']) ? (bool) $_POST['has_header'] : true;
        $import_behavior = isset($_POST['import_behavior']) ? sanitize_text_field($_POST['import_behavior']) : 'merge';
        $default_active = isset($_POST['default_active']) ? (bool) $_POST['default_active'] : true;
        
        if (empty($file_content)) {
            wp_send_json_error(array('message' => __('No file content provided.', 'conversaai-pro-wp')));
        }
        
        // Get existing trigger words
        $existing_trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Initialize counters
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $imported_words = array();
        
        // Clear existing data if replace mode is selected
        if ($import_behavior === 'replace') {
            $existing_trigger_words = array();
        }
        
        // Process based on format
        if ($format === 'csv') {
            // Process CSV
            $rows = str_getcsv($file_content, "\n");
            
            // Skip header if needed
            if ($has_header && count($rows) > 0) {
                array_shift($rows);
            }
            
            foreach ($rows as $row_index => $row) {
                // Parse CSV row
                $columns = str_getcsv($row, ',');
                
                // Ensure we have at least the word
                if (count($columns) < 1 || empty($columns[0])) {
                    $errors++;
                    continue;
                }
                
                // Extract data
                $word = isset($columns[0]) ? trim($columns[0]) : '';
                $category = isset($columns[1]) ? trim($columns[1]) : '';
                $match_type = isset($columns[2]) ? trim($columns[2]) : 'exact';
                $description = isset($columns[3]) ? trim($columns[3]) : '';
                $priority = isset($columns[4]) && is_numeric($columns[4]) ? intval($columns[4]) : 10;
                $active = isset($columns[5]) ? (bool) $columns[5] : $default_active;
                
                // Process responses and follow-ups (pipe separated)
                $responses = isset($columns[6]) && !empty($columns[6]) ? explode('|', $columns[6]) : array();
                $follow_ups = isset($columns[7]) && !empty($columns[7]) ? explode('|', $columns[7]) : array();
                
                // Sanitize
                $responses = array_map('trim', $responses);
                $follow_ups = array_map('trim', $follow_ups);
                
                // Generate ID based on word (or use existing if in merge mode)
                $id = 'trigger_' . sanitize_title($word) . '_' . substr(md5($word), 0, 8);
                
                // Check if word already exists
                $word_exists = false;
                foreach ($existing_trigger_words as $existing_id => $existing_word) {
                    if (strtolower($existing_word['word']) === strtolower($word)) {
                        $word_exists = true;
                        
                        // If merge mode, update existing
                        if ($import_behavior === 'merge') {
                            $id = $existing_id;
                            $updated++;
                        } else if ($import_behavior === 'add') {
                            $skipped++;
                            continue 2; // Skip to next row
                        }
                        
                        break;
                    }
                }
                
                // Create trigger word object
                $trigger_word = array(
                    'id' => $id,
                    'word' => $word,
                    'category' => $category,
                    'description' => $description,
                    'match_type' => $match_type,
                    'priority' => $priority,
                    'active' => $active,
                    'responses' => $responses,
                    'follow_ups' => $follow_ups,
                    'created' => isset($existing_trigger_words[$id]['created']) ? $existing_trigger_words[$id]['created'] : current_time('mysql'),
                    'modified' => current_time('mysql'),
                );
                
                // Add to collection
                $existing_trigger_words[$id] = $trigger_word;
                $imported_words[$id] = $trigger_word;
                
                if ($word_exists && $import_behavior === 'merge') {
                    // Already counted as updated
                } else {
                    $imported++;
                }
            }
        } else {
            // Process JSON
            $json_data = json_decode($file_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => __('Invalid JSON format.', 'conversaai-pro-wp')));
            }
            
            if (!is_array($json_data)) {
                wp_send_json_error(array('message' => __('JSON data is not an array.', 'conversaai-pro-wp')));
            }
            
            foreach ($json_data as $item) {
                // Ensure we have at least the word
                if (!isset($item['word']) || empty($item['word'])) {
                    $errors++;
                    continue;
                }
                
                $word = trim($item['word']);
                
                // Generate ID based on word (or use from JSON if available)
                $id = isset($item['id']) ? $item['id'] : 'trigger_' . sanitize_title($word) . '_' . substr(md5($word), 0, 8);
                
                // Check if word already exists
                $word_exists = false;
                foreach ($existing_trigger_words as $existing_id => $existing_word) {
                    if (strtolower($existing_word['word']) === strtolower($word)) {
                        $word_exists = true;
                        
                        // If merge mode, update existing
                        if ($import_behavior === 'merge') {
                            $id = $existing_id;
                            $updated++;
                        } else if ($import_behavior === 'add') {
                            $skipped++;
                            continue 2; // Skip to next item
                        }
                        
                        break;
                    }
                }
                
                // Create trigger word object
                $trigger_word = array(
                    'id' => $id,
                    'word' => $word,
                    'category' => isset($item['category']) ? sanitize_text_field($item['category']) : '',
                    'description' => isset($item['description']) ? sanitize_textarea_field($item['description']) : '',
                    'match_type' => isset($item['match_type']) ? sanitize_text_field($item['match_type']) : 'exact',
                    'priority' => isset($item['priority']) ? intval($item['priority']) : 10,
                    'active' => isset($item['active']) ? (bool) $item['active'] : $default_active,
                    'responses' => isset($item['responses']) ? array_map('sanitize_textarea_field', $item['responses']) : array(),
                    'follow_ups' => isset($item['follow_ups']) ? array_map('sanitize_textarea_field', $item['follow_ups']) : array(),
                    'created' => isset($existing_trigger_words[$id]['created']) ? $existing_trigger_words[$id]['created'] : current_time('mysql'),
                    'modified' => current_time('mysql'),
                );
                
                // Add to collection
                $existing_trigger_words[$id] = $trigger_word;
                $imported_words[$id] = $trigger_word;
                
                if ($word_exists && $import_behavior === 'merge') {
                    // Already counted as updated
                } else {
                    $imported++;
                }
            }
        }
        
        // Save updated trigger words
        update_option('conversaai_pro_trigger_words', $existing_trigger_words);
        
        // Create status message
        $message = sprintf(
            __('Import completed: %d imported, %d updated, %d skipped, %d errors.', 'conversaai-pro-wp'),
            $imported,
            $updated,
            $skipped,
            $errors
        );
        
        wp_send_json_success(array(
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'imported_words' => $imported_words,
        ));
    }

    /**
     * AJAX handler for exporting trigger words.
     *
     * @since    1.0.0
     */
    public function ajax_export_trigger_words() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_dialogue_manager_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to export trigger words.', 'conversaai-pro-wp')));
        }
        
        // Get export parameters
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $active_only = isset($_POST['active_only']) ? (bool) $_POST['active_only'] : false;
        
        // Get trigger words
        $trigger_words = get_option('conversaai_pro_trigger_words', array());
        
        // Filter trigger words if needed
        if (!empty($category) || $active_only) {
            $filtered_words = array();
            
            foreach ($trigger_words as $id => $word) {
                $match_category = empty($category) || (isset($word['category']) && $word['category'] === $category);
                $match_active = !$active_only || (isset($word['active']) && $word['active']);
                
                if ($match_category && $match_active) {
                    $filtered_words[$id] = $word;
                }
            }
            
            $trigger_words = $filtered_words;
        }
        
        // Generate filename
        $date = date('Y-m-d');
        $category_slug = !empty($category) ? '-' . sanitize_title($category) : '';
        $active_slug = $active_only ? '-active' : '';
        $filename = "trigger-words{$category_slug}{$active_slug}-{$date}";
        
        // Export based on format
        if ($format === 'csv') {
            $filename .= '.csv';
            
            // Create CSV header
            $output = "word,category,match_type,description,priority,active,responses,follow_ups\n";
            
            // Add rows
            foreach ($trigger_words as $word) {
                // Convert responses and follow-ups to pipe separated strings
                $responses_str = !empty($word['responses']) ? '"' . implode('|', $word['responses']) . '"' : '""';
                $follow_ups_str = !empty($word['follow_ups']) ? '"' . implode('|', $word['follow_ups']) . '"' : '""';
                
                // Format CSV row
                $row = array(
                    '"' . str_replace('"', '""', $word['word']) . '"',
                    '"' . str_replace('"', '""', isset($word['category']) ? $word['category'] : '') . '"',
                    '"' . str_replace('"', '""', isset($word['match_type']) ? $word['match_type'] : 'exact') . '"',
                    '"' . str_replace('"', '""', isset($word['description']) ? $word['description'] : '') . '"',
                    isset($word['priority']) ? $word['priority'] : 10,
                    isset($word['active']) ? ($word['active'] ? 1 : 0) : 1,
                    $responses_str,
                    $follow_ups_str,
                );
                
                $output .= implode(',', $row) . "\n";
            }
            
            $content = $output;
        } else {
            $filename .= '.json';
            
            // Prepare data (remove unnecessary fields)
            $export_data = array();
            
            foreach ($trigger_words as $id => $word) {
                // Keep only essential data
                $export_word = array(
                    'id' => $id,
                    'word' => $word['word'],
                    'category' => isset($word['category']) ? $word['category'] : '',
                    'description' => isset($word['description']) ? $word['description'] : '',
                    'match_type' => isset($word['match_type']) ? $word['match_type'] : 'exact',
                    'priority' => isset($word['priority']) ? $word['priority'] : 10,
                    'active' => isset($word['active']) ? $word['active'] : true,
                    'responses' => isset($word['responses']) ? $word['responses'] : array(),
                    'follow_ups' => isset($word['follow_ups']) ? $word['follow_ups'] : array(),
                );
                
                $export_data[] = $export_word;
            }
            
            $content = json_encode($export_data, JSON_PRETTY_PRINT);
        }
        
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => $content,
            'count' => count($trigger_words),
        ));
    }
}