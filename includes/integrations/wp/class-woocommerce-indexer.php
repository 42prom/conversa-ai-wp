<?php
/**
 * WooCommerce product indexer.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/wp
 */

namespace ConversaAI_Pro_WP\Integrations\WP;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Knowledge_Base;
use ConversaAI_Pro_WP\Utils\Logger;

/**
 * WooCommerce indexer class.
 *
 * Handles indexing and retrieval of WooCommerce products.
 *
 * @since      1.0.0
 */
class WooCommerce_Indexer {

    /**
     * Knowledge Base instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Knowledge_Base    $knowledge_base    The knowledge base instance.
     */
    private $knowledge_base;

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->knowledge_base = new Knowledge_Base();
        $this->logger = new Logger();
        
        // Add hooks for automatic updates
        add_action('woocommerce_update_product', array($this, 'update_product_index'), 10, 2);
        add_action('before_delete_post', array($this, 'remove_from_index'));
    }

    /**
     * Index all WooCommerce products.
     *
     * @since    1.0.0
     * @param    bool    $force    Whether to force reindexing of all products.
     * @return   array    Results with count of indexed items.
     */
    public function index_all_products($force = false) {
        $this->logger->info('Starting complete product indexing with force=' . ($force ? 'true' : 'false'));
        
        $results = array(
            'indexed' => 0,
            'skipped' => 0,
            'errors' => 0,
        );
        
        if (!function_exists('wc_get_products')) {
            $this->logger->error('WooCommerce not active or wc_get_products not available');
            return $results;
        }
        
        try {
            $args = array(
                'status' => 'publish',
                'limit' => -1,
                'return' => 'objects',
            );
            
            $products = wc_get_products($args);
            $this->logger->info('Found ' . count($products) . ' products to index');
            
            foreach ($products as $product) {
                try {
                    $indexed = $this->index_single_product($product, $force);
                    if ($indexed) {
                        $results['indexed']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error indexing product #' . $product->get_id() . ': ' . $e->getMessage());
                    $results['errors']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('General error in index_all_products: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            $results['errors']++;
            $results['error'] = $e->getMessage();
        }
        
        $this->logger->info('Product indexing completed: ' . json_encode($results));
        return $results;
    }

    /**
     * Index a single product.
     *
     * @since    1.0.0
     * @param    WC_Product    $product    The product to index.
     * @param    bool          $force      Whether to force reindexing.
     * @return   bool          Whether the product was indexed.
     */
    public function index_single_product($product, $force = false) {
        if (!is_a($product, 'WC_Product')) {
            $this->logger->error('Invalid product object provided to index_single_product');
            return false;
        }
        
        $product_id = $product->get_id();
        $this->logger->info('Indexing product #' . $product_id . ' with force=' . ($force ? 'true' : 'false'));
        
        if ($product->get_status() !== 'publish') {
            $this->logger->info('Product #' . $product_id . ' is not published, skipping');
            return false;
        }
        
        // Check last indexed timestamp
        $last_indexed = get_post_meta($product_id, '_conversaai_last_indexed', true);
        $modified_time = strtotime($product->get_date_modified());
        
        if (!$force && $last_indexed && $last_indexed >= $modified_time) {
            $this->logger->info('Product #' . $product_id . ' already indexed and unchanged, skipping');
            return false;
        }
        
        try {
            // Get product data
            $product_data = $this->get_product_data($product);
            if (empty($product_data['name']) && empty($product_data['description'])) {
                $this->logger->warning('Product #' . $product_id . ' has no valid name or description, skipping');
                return false;
            }
            
            // Generate knowledge base entries
            $entries = $this->generate_product_entries($product, $product_data);
            
            // Get existing entries to update or delete
            $existing_entries = $this->get_existing_entries($product_id);
            
            $added = 0;
            foreach ($entries as $entry) {
                // Validate metadata before proceeding
                if (empty($entry['metadata']) || !json_decode($entry['metadata'])) {
                    $this->logger->error('Invalid metadata for product #' . $product_id . ', type: ' . $entry['entry_type']);
                    continue;
                }
                
                $exists = false;
                foreach ($existing_entries as $key => $existing) {
                    if (isset($existing['metadata'])) {
                        $metadata = json_decode($existing['metadata'], true);
                        if (is_array($metadata) && isset($metadata['entry_type']) && $metadata['entry_type'] === $entry['entry_type']) {
                            $this->logger->info('Updating existing entry for product #' . $product_id . ', type: ' . $entry['entry_type']);
                            $success = $this->knowledge_base->update_entry($existing['id'], array(
                                'question' => $entry['question'],
                                'answer' => $entry['answer'],
                                'metadata' => $entry['metadata'],
                            ));
                            if ($success === false) {
                                $this->logger->error('Failed to update entry for product #' . $product_id . ', type: ' . $entry['entry_type']);
                            }
                            unset($existing_entries[$key]);
                            $exists = true;
                            break;
                        }
                    }
                }
                
                if (!$exists) {
                    $this->logger->info('Adding new entry for product #' . $product_id . ', type: ' . $entry['entry_type']);
                    $entry_id = $this->knowledge_base->add_entry(
                        $entry['question'],
                        $entry['answer'],
                        'woocommerce_product',
                        $entry['confidence'],
                        true,
                        $entry['metadata']
                    );
                    if ($entry_id) {
                        $added++;
                    } else {
                        $this->logger->error('Failed to add entry for product #' . $product_id . ', type: ' . $entry['entry_type']);
                    }
                }
            }
            
            // Delete obsolete entries
            foreach ($existing_entries as $existing) {
                $this->logger->info('Deleting obsolete entry #' . $existing['id'] . ' for product #' . $product_id);
                $success = $this->knowledge_base->delete_entry($existing['id']);
                if ($success === false) {
                    $this->logger->error('Failed to delete entry #' . $existing['id'] . ' for product #' . $product_id);
                }
            }
            
            // Update indexed timestamp
            update_post_meta($product_id, '_conversaai_last_indexed', time());
            $this->logger->info('Product #' . $product_id . ' indexed successfully, added ' . $added . ' entries');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error indexing product #' . $product_id . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return false;
        }
    }

    /**
     * Update the product index when a product is saved.
     *
     * @since    1.0.0
     * @param    int         $product_id    The product ID.
     * @param    WC_Product  $product       The product object.
     */
    public function update_product_index($product_id, $product) {
        $this->logger->info('Updating index for product #' . $product_id);
        $this->index_single_product($product, true);
    }

    /**
     * Remove a product from the index when it's deleted.
     *
     * @since    1.0.0
     * @param    int    $product_id    The product ID.
     */
    public function remove_from_index($product_id) {
        if (get_post_type($product_id) !== 'product') {
            return;
        }
        
        // Get all entries for this product
        $existing_entries = $this->get_existing_entries($product_id);
        
        // Delete all entries
        foreach ($existing_entries as $entry) {
            $this->logger->info('Removing entry #' . $entry['id'] . ' for deleted product #' . $product_id);
            $success = $this->knowledge_base->delete_entry($entry['id']);
            if ($success === false) {
                $this->logger->error('Failed to delete entry #' . $entry['id'] . ' for product #' . $product_id);
            }
        }
        
        // Remove metadata
        delete_post_meta($product_id, '_conversaai_last_indexed');
        $this->logger->info('Removed product #' . $product_id . ' from index');
    }

    /**
     * Get product data for indexing.
     *
     * @since    1.0.0
     * @param    WC_Product    $product    The product object.
     * @return   array         The product data.
     */
    private function get_product_data($product) {
        $data = array(
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'permalink' => get_permalink($product->get_id()),
        );
        
        // Clean descriptions
        if (!empty($data['description'])) {
            $data['description'] = wp_strip_all_tags($data['description'], true);
            $data['description'] = trim(preg_replace('/\s+/', ' ', $data['description']));
        }
        
        if (!empty($data['short_description'])) {
            $data['short_description'] = wp_strip_all_tags($data['short_description'], true);
            $data['short_description'] = trim(preg_replace('/\s+/', ' ', $data['short_description']));
        }

        // Clean product name
        if (!empty($data['name'])) {
            $data['name'] = wp_strip_all_tags($data['name'], true);
            $data['name'] = str_replace('// -this', '', $data['name']);
            $data['name'] = trim($data['name']);
        }
        
        return $data;
    }

    /**
     * Generate knowledge base entries for a product.
     *
     * @since    1.0.0
     * @param    WC_Product    $product        The product object.
     * @param    array         $product_data   The product data.
     * @return   array         The generated entries.
     */
    private function generate_product_entries($product, $product_data) {
        $entries = array();
        $product_id = $product->get_id();
        $name = !empty($product_data['name']) ? $product_data['name'] : 'Untitled Product';
        
        // Get display settings with defaults
        $display_settings = get_option('conversaai_pro_woocommerce_display', array());
        $defaults = array(
            'product_question' => 'What is the product "%s"?',
            'product_detail_question' => 'Can you describe the product "%s" in detail?'
        );
        $display_settings = wp_parse_args($display_settings, $defaults);
        
        // Debug log the question formats being used
        $this->logger->info('Using question formats: ' . print_r(array(
            'product_question' => $display_settings['product_question'],
            'product_detail_question' => $display_settings['product_detail_question']
        ), true));
        
        // Basic product information entry
        $entries[] = array(
            'question' => sprintf($display_settings['product_question'], $name),
            'answer' => $this->format_product_summary($product, $product_data),
            'confidence' => 0.9,
            'entry_type' => 'basic_info',
            'metadata' => $this->prepare_metadata($product_id, 'basic_info'),
        );
        
        // Full description entry
        if (!empty($product_data['description'])) {
            $entries[] = array(
                'question' => sprintf($display_settings['product_detail_question'], $name),
                'answer' => $product_data['description'],
                'confidence' => 0.85,
                'entry_type' => 'full_description',
                'metadata' => $this->prepare_metadata($product_id, 'full_description'),
            );
        }
        
        return $entries;
    }

    /**
     * Format product summary.
     *
     * @since    1.0.0
     * @param    WC_Product    $product        The product object.
     * @param    array         $product_data   The product data.
     * @return   string        The formatted summary.
     */
    private function format_product_summary($product, $product_data) {
        $name = !empty($product_data['name']) ? $product_data['name'] : 'Untitled Product';

        // Get display settings - make sure we're using the correct option name
        $display_settings = get_option('conversaai_pro_woocommerce_display', array());

        // Apply defaults for any missing settings
        $defaults = array(
            'product_intro' => '"%s" is a WooCommerce product.',
            'product_price' => 'Priced at %s.',
            'product_link' => 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.',
            'link_color' => '#4c66ef',
            'show_categories' => true,
            'categories_format' => 'Product categories: %s.'
        );
        
        // Merge with defaults to ensure all keys exist
        $display_settings = wp_parse_args($display_settings, $defaults);
        
        // Debug log the settings being used
        $this->logger->info('Using WooCommerce display settings: ' . print_r($display_settings, true));
        
        // Format the product introduction - make sure to handle sprintf formatting properly
        $summary = sprintf($display_settings['product_intro'], $name);
        
        // Add description if available
        if (!empty($product_data['short_description'])) {
            $summary .= ' ' . wp_trim_words($product_data['short_description'], 50, '...');
        } elseif (!empty($product_data['description'])) {
            $summary .= ' ' . wp_trim_words($product_data['description'], 50, '...');
        }
        
        // Add price if available
        if (!empty($product_data['price'])) {
            $summary .= ' ' . sprintf($display_settings['product_price'], wc_price($product_data['price']));
        }
        
        // Add SKU if available
        if (!empty($product_data['sku'])) {
            $summary .= ' ' . sprintf(__('SKU: %s.', 'conversaai-pro-wp'), esc_html($product_data['sku']));
        }
        
        // Add categories if enabled
        if (!empty($display_settings['show_categories'])) {
            $categories = array();
            $terms = get_the_terms($product->get_id(), 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = $term->name;
                }
                
                if (!empty($categories)) {
                    $summary .= ' ' . sprintf($display_settings['categories_format'], implode(', ', $categories));
                }
            }
        }
        
        // Add permalink with custom link format
        $summary .= ' ' . sprintf(
            $display_settings['product_link'],
            esc_url($product_data['permalink']),
            esc_attr($display_settings['link_color'])
        );
        
        return trim($summary);
    }

    /**
     * Get existing knowledge base entries for a product.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @return   array     The existing entries.
     */
    private function get_existing_entries($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Modified: Add checks for non-empty metadata to avoid JSON_EXTRACT errors
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE 
            metadata IS NOT NULL AND 
            metadata != '' AND 
            JSON_EXTRACT(metadata, '$.product_id') = %d AND
            JSON_EXTRACT(metadata, '$.source') = %s",
            $product_id,
            'woocommerce_product'
        );
        
        $entries = $wpdb->get_results($query, ARRAY_A);
        
        if ($entries !== null && $wpdb->last_error === '') {
            return $entries ?: array();
        }
        
        // Fallback: Fetch entries with valid JSON only
        $this->logger->warning('JSON_EXTRACT failed for product #' . $product_id . ', using PHP fallback. Error: ' . $wpdb->last_error);
        $entries = array();
        $valid_entries = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE 
            metadata IS NOT NULL AND 
            metadata != '' AND 
            metadata LIKE '%\"source\":\"woocommerce_product\"%'",
            ARRAY_A
        );
        
        foreach ($valid_entries as $entry) {
            $metadata = json_decode($entry['metadata'], true);
            if (is_array($metadata) &&
                isset($metadata['product_id']) && $metadata['product_id'] == $product_id &&
                isset($metadata['source']) && $metadata['source'] == 'woocommerce_product') {
                $entries[] = $entry;
            }
        }
        
        return $entries;
    }

    /**
     * Prepare metadata for a knowledge base entry.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @param    string    $entry_type    The entry type.
     * @return   string    JSON encoded metadata.
     */
    private function prepare_metadata($product_id, $entry_type) {
        $metadata = array(
            'product_id' => (int) $product_id,
            'source' => 'woocommerce_product',
            'entry_type' => sanitize_text_field($entry_type),
            'indexed_at' => time(),
        );
        
        $encoded = wp_json_encode($metadata);
        if ($encoded === false) {
            $this->logger->error('Failed to encode metadata for product #' . $product_id . ', type: ' . $entry_type);
            return '{}'; // Modified: Return empty JSON object instead of empty string
        }
        
        return $encoded;
    }
}