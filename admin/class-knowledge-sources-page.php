<?php
/**
 * Knowledge Sources management page.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Integrations\WP\Content_Indexer;
use ConversaAI_Pro_WP\Integrations\WP\WooCommerce_Indexer;

/**
 * Knowledge Sources page class.
 *
 * Handles the knowledge sources management functionality.
 *
 * @since      1.0.0
 */
class Knowledge_Sources_Page {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action('wp_ajax_conversaai_index_content', array($this, 'ajax_index_content'));
        add_action('wp_ajax_conversaai_index_products', array($this, 'ajax_index_products'));
        add_action('wp_ajax_conversaai_save_indexing_settings', array($this, 'ajax_save_indexing_settings'));
        add_action('wp_ajax_conversaai_get_indexing_stats', array($this, 'ajax_get_indexing_stats'));
        add_action('wp_ajax_conversaai_save_wc_display_settings', array($this, 'ajax_save_wc_display_settings'));
    }

    public function display() {
        $indexing_settings = get_option('conversaai_pro_indexing_settings', array(
            'post_types' => array('page', 'post'),
            'auto_index' => true,
            'exclude_categories' => array(),
        ));
        
        $stats = $this->get_indexing_stats();
        
        $post_types = get_post_types(array('public' => true), 'objects');
        
        $woocommerce_active = class_exists('WooCommerce');
        
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/knowledge-sources-page.php';
    }

    public function ajax_index_content() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_knowledge_sources_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'conversaai-pro-wp')));
        }
        
        $force = isset($_POST['force']) ? (bool) $_POST['force'] : false;
        
        // Get post types from the request - this is the key change
        $post_types = isset($_POST['post_types']) && is_array($_POST['post_types']) 
            ? array_map('sanitize_text_field', $_POST['post_types']) 
            : null;
        
        try {
            $indexer = new Content_Indexer();
            $results = $indexer->index_all_content($force, $post_types);
            
            update_option('conversaai_pro_last_content_index', time());
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Content indexing completed. %d items indexed, %d skipped, %d errors.', 'conversaai-pro-wp'),
                    $results['indexed'] ?? 0,
                    $results['skipped'] ?? 0,
                    $results['errors'] ?? 0
                ),
                'stats' => $this->get_indexing_stats(),
            ));
        } catch (\Exception $e) {
            error_log('ConversaAI: Index Content Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error indexing content: ', 'conversaai-pro-wp') . $e->getMessage()));
        }
    }

    public function ajax_index_products() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_knowledge_sources_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'conversaai-pro-wp')));
        }
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => __('WooCommerce is not active.', 'conversaai-pro-wp')));
        }
        
        $force = isset($_POST['force']) ? (bool) $_POST['force'] : false;
        
        try {
            // Log input parameters
            error_log('ConversaAI: Starting ajax_index_products with force=' . ($force ? 'true' : 'false'));
            
            $indexer = new WooCommerce_Indexer();
            
            // Verify WooCommerce functions
            if (!function_exists('wc_get_products')) {
                error_log('ConversaAI: wc_get_products function not available');
                wp_send_json_error(array('message' => __('WooCommerce functions not available.', 'conversaai-pro-wp')));
            }
            
            $results = $indexer->index_all_products($force);
            
            // Log results
            error_log('ConversaAI: Index products results: ' . print_r($results, true));
            
            update_option('conversaai_pro_last_product_index', time());
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Product indexing completed. %d items indexed, %d skipped, %d errors.', 'conversaai-pro-wp'),
                    $results['indexed'] ?? 0,
                    $results['skipped'] ?? 0,
                    $results['errors'] ?? 0
                ),
                'stats' => $this->get_indexing_stats(),
            ));
        } catch (\Exception $e) {
            $error_message = 'ConversaAI: Index Products Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            error_log($error_message);
            error_log('ConversaAI: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('Error indexing products: ', 'conversaai-pro-wp') . $e->getMessage()));
        }
    }

    public function ajax_save_indexing_settings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_knowledge_sources_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'conversaai-pro-wp')));
        }
        
        // Decode JSON settings
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
        
        if (!is_array($settings)) {
            wp_send_json_error(array('message' => __('Invalid settings format.', 'conversaai-pro-wp')));
        }
        
        // Sanitize settings
        $sanitized = array();
        $sanitized['post_types'] = isset($settings['post_types']) && is_array($settings['post_types']) 
            ? array_map('sanitize_text_field', $settings['post_types']) 
            : array('page', 'post');
        $sanitized['auto_index'] = isset($settings['auto_index']) ? (bool) $settings['auto_index'] : true;
        $sanitized['exclude_categories'] = isset($settings['exclude_categories']) && is_array($settings['exclude_categories']) 
            ? array_map('intval', $settings['exclude_categories']) 
            : array();
        
        // Save settings
        update_option('conversaai_pro_indexing_settings', $sanitized);
        
        wp_send_json_success(array(
            'message' => __('Indexing settings saved successfully.', 'conversaai-pro-wp'),
            'settings' => $sanitized,
        ));
    }

    public function ajax_get_indexing_stats() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_knowledge_sources_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'conversaai-pro-wp')));
        }
        
        wp_send_json_success(array(
            'stats' => $this->get_indexing_stats(),
        ));
    }

    private function get_indexing_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        $content_count = 0;
        $product_count = 0;
        $page_count = 0;
        $post_count = 0;
        $total_count = 0;
        
        $entries = $wpdb->get_results("SELECT metadata FROM $table_name", ARRAY_A);
        $total_count = count($entries);
        
        foreach ($entries as $entry) {
            $metadata = !empty($entry['metadata']) ? json_decode($entry['metadata'], true) : [];
            if (!is_array($metadata)) {
                $metadata = [];
            }
            
            $source = isset($metadata['source']) ? $metadata['source'] : '';
            if ($source === 'wp_content') {
                $content_count++;
                if (isset($metadata['post_type'])) {
                    if ($metadata['post_type'] === 'page') {
                        $page_count++;
                    } elseif ($metadata['post_type'] === 'post') {
                        $post_count++;
                    }
                }
            } elseif ($source === 'woocommerce_product') {
                $product_count++;
            }
        }
        
        $manual_count = $total_count - $content_count - $product_count;
        
        $last_content_index = get_option('conversaai_pro_last_content_index', 0);
        $last_product_index = get_option('conversaai_pro_last_product_index', 0);
        
        return array(
            'wp_content_count' => $content_count, // Match template
            'product_count' => $product_count,
            'page_count' => $page_count,
            'post_count' => $post_count,
            'manual_count' => max(0, $manual_count),
            'total_count' => $total_count,
            'last_content_index' => $last_content_index ? date('Y-m-d H:i:s', $last_content_index) : __('Never', 'conversaai-pro-wp'),
            'last_product_index' => $last_product_index ? date('Y-m-d H:i:s', $last_product_index) : __('Never', 'conversaai-pro-wp'),
        );
    }

    /**
     * AJAX handler for saving WooCommerce display settings.
     */
    public function ajax_save_wc_display_settings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_knowledge_sources_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'conversaai-pro-wp')));
        }
        
        // Get and sanitize settings
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
        
        if (!is_array($settings)) {
            wp_send_json_error(array('message' => __('Invalid settings format.', 'conversaai-pro-wp')));
        }
        
        // Sanitize each setting
        $sanitized = array(
            'product_intro' => isset($settings['product_intro']) ? wp_kses_post($settings['product_intro']) : '"%s" is a WooCommerce product.',
            'product_price' => isset($settings['product_price']) ? wp_kses_post($settings['product_price']) : 'Priced at %s.',
            'product_link' => isset($settings['product_link']) ? wp_kses_post($settings['product_link']) : 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.',
            'link_color' => isset($settings['link_color']) ? sanitize_hex_color($settings['link_color']) : '#4c66ef',
            'product_question' => isset($settings['product_question']) ? sanitize_text_field($settings['product_question']) : 'What is the product "%s"?',
            'product_detail_question' => isset($settings['product_detail_question']) ? sanitize_text_field($settings['product_detail_question']) : 'Can you describe the product "%s" in detail?',
            'show_categories' => isset($settings['show_categories']) ? (bool)$settings['show_categories'] : true,
            'categories_format' => isset($settings['categories_format']) ? sanitize_text_field($settings['categories_format']) : 'Product categories: %s.'
        );
        
        // Save settings
        update_option('conversaai_pro_woocommerce_display', $sanitized);
        
        // Add debug point to verify settings are being saved
        error_log('WooCommerce display settings saved: ' . print_r($sanitized, true));
        
        wp_send_json_success(array(
            'message' => __('WooCommerce display settings saved successfully.', 'conversaai-pro-wp'),
            'settings' => $sanitized
        ));
    }
}