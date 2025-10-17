<?php
/**
 * Knowledge Base admin page functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Knowledge_Base;
use ConversaAI_Pro_WP\DB\KB_Import_Export;

/**
 * Knowledge Base admin page class.
 *
 * Handles the knowledge base management functionality.
 *
 * @since      1.0.0
 */
class KB_Admin_Page {

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
        
        // Add AJAX handlers for knowledge base operations
        add_action('wp_ajax_conversaai_get_kb_entries', array($this, 'ajax_get_kb_entries'));
        add_action('wp_ajax_conversaai_save_kb_entry', array($this, 'ajax_save_kb_entry'));
        add_action('wp_ajax_conversaai_delete_kb_entry', array($this, 'ajax_delete_kb_entry'));
        add_action('wp_ajax_conversaai_import_kb', array($this, 'ajax_import_kb'));
        add_action('wp_ajax_conversaai_export_kb', array($this, 'ajax_export_kb'));
        add_action('wp_ajax_conversaai_bulk_kb_action', array($this, 'ajax_bulk_kb_action'));
    }

    /**
     * Display the knowledge base admin page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Get initial data for the page
        $kb = new Knowledge_Base();
        $kb_count = $kb->get_entries_count();
        
        // Get topics for filter dropdown
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        $topics = $wpdb->get_col("SELECT DISTINCT topic FROM $table_name WHERE topic != '' ORDER BY topic ASC");
        
        // Get initial KB entries (first page)
        $kb_entries = $kb->get_entries(array('limit' => 20));
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/knowledge-base-page.php';
    }

    /**
     * AJAX handler for getting knowledge base entries.
     *
     * @since    1.0.0
     */
    public function ajax_get_kb_entries() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
    
        // Get and sanitize parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_sql_orderby($_POST['orderby']) : 'id';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
    
        if (!$orderby) {
            $orderby = 'id';
        }
    
        // Initialize knowledge base
        $kb = new \ConversaAI_Pro_WP\Core\Knowledge_Base();
    
        // Prepare query args
        $args = array(
            'topic' => $topic,
            'orderby' => $orderby,
            'order' => $order,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        );
    
        // Handle status filter
        if ($status === 'approved') {
            $args['approved'] = true;
        } else if ($status === 'pending') {
            $args['approved'] = false;
        }
    
        // Handle search if provided
        if (!empty($search)) {
            global $wpdb;
            $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            
            // Custom query with search
            $where = array();
            if ($topic) {
                $where[] = $wpdb->prepare("topic = %s", $topic);
            }
            
            if ($status === 'approved') {
                $where[] = "approved = 1";
            } else if ($status === 'pending') {
                $where[] = "approved = 0";
            }
            
            $where[] = $wpdb->prepare("question LIKE %s", $search_term);
            
            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
            $total = $wpdb->get_var($total_query);
            
            $entries_query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order LIMIT %d, %d";
            $entries = $wpdb->get_results($wpdb->prepare($entries_query, ($page - 1) * $per_page, $per_page), ARRAY_A);
        } else {
            // Use standard get_entries method for non-search queries
            $total = $kb->get_entries_count(array(
                'topic' => $topic,
                'approved' => $args['approved'] ?? null,
            ));
            
            $entries = $kb->get_entries($args);
        }
    
        // Calculate total pages
        $pages = ceil($total / $per_page);
    
        // Send the response
        wp_send_json_success(array(
            'entries' => $entries,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
        ));
    }

    /**
     * AJAX handler for retrieving a single knowledge base entry.
     *
     * @since    1.0.0
     */
    public function ajax_get_kb_entry() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }

        // Get entry ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('Invalid entry ID.', 'conversaai-pro-wp')));
        }

        // Get the entry from database
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        // Check if entry exists
        if (!$entry) {
            wp_send_json_error(array('message' => __('Entry not found.', 'conversaai-pro-wp')));
        }

        // Send the entry data
        wp_send_json_success(array('entry' => $entry));
    }

    /**
     * AJAX handler for saving a knowledge base entry.
     *
     * @since    1.0.0
     */
    public function ajax_save_kb_entry() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
    
        // Get and sanitize data
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $question = isset($_POST['question']) ? wp_unslash(sanitize_text_field($_POST['question'])) : '';
        $answer = isset($_POST['answer']) ? wp_unslash(wp_kses_post($_POST['answer'])) : '';
        $topic = isset($_POST['topic']) ? wp_unslash(sanitize_text_field($_POST['topic'])) : '';
        $confidence = isset($_POST['confidence']) ? floatval($_POST['confidence']) : 0.85;
        $approved = isset($_POST['approved']) ? intval($_POST['approved']) : 0;
    
        // Validate required fields
        if (empty($question) || empty($answer)) {
            wp_send_json_error(array('message' => __('Question and answer are required.', 'conversaai-pro-wp')));
        }
    
        // Initialize knowledge base
        $kb = new \ConversaAI_Pro_WP\Core\Knowledge_Base();
    
        // Process the entry
        if ($id > 0) {
            // Update existing entry
            $data = array(
                'question' => $question,
                'answer' => $answer,
                'topic' => $topic,
                'confidence' => $confidence,
                'approved' => $approved,
            );
    
            $success = $kb->update_entry($id, $data);
            $message = __('Knowledge base entry updated successfully.', 'conversaai-pro-wp');
        } else {
            // Add new entry
            $success = $kb->add_entry($question, $answer, $topic, $confidence, $approved);
            $message = __('Knowledge base entry added successfully.', 'conversaai-pro-wp');
        }
    
        // Check result and respond
        if ($success) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Failed to save knowledge base entry.', 'conversaai-pro-wp')));
        }
    }

    /**
     * AJAX handler for deleting a knowledge base entry.
     *
     * @since    1.0.0
     */
    public function ajax_delete_kb_entry() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete knowledge base entries.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('Invalid entry ID.', 'conversaai-pro-wp')));
        }
        
        $kb = new Knowledge_Base();
        $result = $kb->delete_entry($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Entry deleted successfully.', 'conversaai-pro-wp'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete entry.', 'conversaai-pro-wp')));
        }
    }

    /**
     * AJAX handler for importing knowledge base entries.
     *
     * @since    1.0.0
     */
    public function ajax_import_kb() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to import knowledge base entries.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $file_content = isset($_POST['file_content']) ? sanitize_textarea_field($_POST['file_content']) : '';
        $file_type = isset($_POST['file_type']) ? sanitize_text_field($_POST['file_type']) : 'csv';
        $skip_header = isset($_POST['skip_header']) ? (bool) $_POST['skip_header'] : true;
        $update_existing = isset($_POST['update_existing']) ? (bool) $_POST['update_existing'] : false;
        $approve_all = isset($_POST['approve_all']) ? (bool) $_POST['approve_all'] : false;
        
        if (empty($file_content)) {
            wp_send_json_error(array('message' => __('No import data provided.', 'conversaai-pro-wp')));
        }
        
        $import_export = new KB_Import_Export();
        $options = array(
            'skip_header' => $skip_header,
            'update_existing' => $update_existing,
            'approved_by_default' => $approve_all,
        );
        
        if ($file_type === 'csv') {
            $result = $import_export->import_from_csv($file_content, $options);
        } elseif ($file_type === 'json') {
            $result = $import_export->import_from_json($file_content, $options);
        } else {
            wp_send_json_error(array('message' => __('Unsupported file type.', 'conversaai-pro-wp')));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Import completed: %d imported, %d skipped, %d errors.', 'conversaai-pro-wp'),
                $result['imported'],
                $result['skipped'],
                $result['errors']
            ),
            'details' => $result,
        ));
    }

    /**
     * AJAX handler for exporting knowledge base entries.
     *
     * @since    1.0.0
     */
    public function ajax_export_kb() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to export knowledge base entries.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        $approved_only = isset($_POST['approved_only']) ? (bool) $_POST['approved_only'] : false;
        
        // Build query args
        $args = array();
        
        if (!empty($topic)) {
            $args['topic'] = $topic;
        }
        
        if ($approved_only) {
            $args['approved'] = true;
        }
        
        $import_export = new KB_Import_Export();
        
        if ($format === 'csv') {
            $data = $import_export->export_to_csv($args);
            $mime_type = 'text/csv';
            $filename = 'knowledge_base_export_' . date('Y-m-d') . '.csv';
        } elseif ($format === 'json') {
            $data = $import_export->export_to_json($args);
            $mime_type = 'application/json';
            $filename = 'knowledge_base_export_' . date('Y-m-d') . '.json';
        } else {
            wp_send_json_error(array('message' => __('Unsupported export format.', 'conversaai-pro-wp')));
        }
        
        wp_send_json_success(array(
            'data' => $data,
            'filename' => $filename,
            'mime_type' => $mime_type,
        ));
    }

    /**
     * AJAX handler for bulk actions on knowledge base entries.
     *
     * @since    1.0.0
     */
    public function ajax_bulk_kb_action() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_kb_nonce')) {
            error_log('ConversaAI: Security check failed in bulk action');
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            error_log('ConversaAI: User does not have permission for bulk action');
            wp_send_json_error(array('message' => __('You do not have permission to perform bulk actions.', 'conversaai-pro-wp')));
        }
        
        // Get and validate input
        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $entry_ids = isset($_POST['entry_ids']) ? $_POST['entry_ids'] : array();
        
        // Debug output
        error_log('ConversaAI: Bulk action: ' . $bulk_action);
        error_log('ConversaAI: Entry IDs: ' . print_r($entry_ids, true));
        
        // Ensure entry_ids is an array of integers
        if (is_array($entry_ids)) {
            $entry_ids = array_map('intval', $entry_ids);
        } else {
            error_log('ConversaAI: entry_ids is not an array: ' . gettype($entry_ids));
            $entry_ids = array();
        }
        
        if (empty($bulk_action) || empty($entry_ids)) {
            error_log('ConversaAI: No action or entries selected');
            wp_send_json_error(array('message' => __('No action or entries selected.', 'conversaai-pro-wp')));
        }
        
        $kb = new Knowledge_Base();
        $success_count = 0;
        $error_count = 0;
        
        switch ($bulk_action) {
            case 'approve':
                foreach ($entry_ids as $id) {
                    $result = $kb->update_entry($id, array('approved' => 1));
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                $message = sprintf(
                    __('Successfully approved %d entries. %d errors.', 'conversaai-pro-wp'),
                    $success_count,
                    $error_count
                );
                break;
                
            case 'disapprove':
                foreach ($entry_ids as $id) {
                    $result = $kb->update_entry($id, array('approved' => 0));
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                $message = sprintf(
                    __('Successfully disapproved %d entries. %d errors.', 'conversaai-pro-wp'),
                    $success_count,
                    $error_count
                );
                break;
                
            case 'delete':
                foreach ($entry_ids as $id) {
                    $result = $kb->delete_entry($id);
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                $message = sprintf(
                    __('Successfully deleted %d entries. %d errors.', 'conversaai-pro-wp'),
                    $success_count,
                    $error_count
                );
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action.', 'conversaai-pro-wp')));
                break;
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'success_count' => $success_count,
            'error_count' => $error_count,
        ));
    }
}