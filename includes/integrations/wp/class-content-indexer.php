<?php
/**
 * WordPress content indexer.
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
 * Content indexer class.
 *
 * Handles indexing and retrieval of WordPress content (posts, pages).
 *
 * @since      1.0.0
 */
class Content_Indexer {

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
        add_action('save_post', array($this, 'update_post_index'), 10, 3);
        add_action('delete_post', array($this, 'remove_from_index'));
        
        // Schedule regular full indexing
        if (!wp_next_scheduled('conversaai_pro_content_reindex')) {
            wp_schedule_event(time(), 'daily', 'conversaai_pro_content_reindex');
        }
        add_action('conversaai_pro_content_reindex', array($this, 'index_all_content'));
    }

    /**
     * Index all WordPress content (posts, pages).
     *
     * @since    1.0.0
     * @param    bool    $force    Whether to force reindexing of all content.
     * @return   array    Results with count of indexed items.
     */
    public function index_all_content($force = false, $specific_post_types = null) {
        $this->logger->info('Starting complete content indexing with force=' . ($force ? 'true' : 'false'));
        
        $results = array(
            'indexed' => 0,
            'skipped' => 0,
            'errors' => 0,
        );
        
        try {
            // Get indexing settings
            $settings = get_option('conversaai_pro_indexing_settings', array(
                'post_types' => array('page', 'post'),
                'auto_index' => true,
                'exclude_categories' => array(),
            ));
            
            // Use the specific post types if provided, otherwise fall back to settings
            $post_types_to_index = $specific_post_types !== null ? $specific_post_types : $settings['post_types'];
            
            // Log which post types we're indexing
            $this->logger->info('Indexing post types: ' . implode(', ', $post_types_to_index));
            
            $args = array(
                'post_type' => $post_types_to_index,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'no_found_rows' => true,
            );
            
            if (!empty($settings['exclude_categories'])) {
                $args['category__not_in'] = $settings['exclude_categories'];
            }
            
            $posts = get_posts($args);
            $this->logger->info('Found ' . count($posts) . ' posts to index');
            
            foreach ($posts as $post) {
                try {
                    if (!is_a($post, 'WP_Post')) {
                        $this->logger->warning('Invalid post object: ' . print_r($post, true));
                        $results['skipped']++;
                        continue;
                    }
                    
                    $indexed = $this->index_single_post($post, $force);
                    if ($indexed) {
                        $results['indexed']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error indexing post #' . $post->ID . ': ' . $e->getMessage());
                    $results['errors']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('General error in index_all_content: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            $results['errors']++;
            $results['error'] = $e->getMessage();
        }
        
        $this->logger->info('Content indexing completed: ' . json_encode($results));
        return $results;
    }

    /**
     * Index a single post.
     *
     * @since    1.0.0
     * @param    WP_Post    $post     The post to index.
     * @param    bool       $force    Whether to force reindexing.
     * @return   bool       Whether the post was indexed.
     */
    public function index_single_post($post, $force = false) {
        if (!is_a($post, 'WP_Post')) {
            $this->logger->error('Invalid post object provided to index_single_post');
            return false;
        }
        
        $post_id = $post->ID;
        $this->logger->info('Indexing post #' . $post_id . ' (' . $post->post_type . ') with force=' . ($force ? 'true' : 'false'));
        
        if ($post->post_status !== 'publish') {
            $this->logger->info('Post #' . $post_id . ' is not published, skipping');
            return false;
        }
        
        // Check last indexed timestamp
        $last_indexed = get_post_meta($post_id, '_conversaai_last_indexed', true);
        $modified_time = strtotime($post->post_modified);
        
        if (!$force && $last_indexed && $last_indexed >= $modified_time) {
            $this->logger->info('Post #' . $post_id . ' already indexed and unchanged, skipping');
            return false;
        }
        
        try {
            // Clean content to remove floating elements and theme data
            $content = $this->clean_content($post->post_content);
            if (empty($content) && empty($post->post_title)) {
                $this->logger->warning('Post #' . $post_id . ' has no valid content or title, skipping');
                return false;
            }
            
            // Generate knowledge base entries
            $entries = $this->generate_post_entries($post, $content);
            
            // Get existing entries to update or delete
            $existing_entries = $this->get_existing_entries($post_id);
            
            $added = 0;
            foreach ($entries as $entry) {
                // Validate metadata before proceeding
                if (empty($entry['metadata']) || !json_decode($entry['metadata'])) {
                    $this->logger->error('Invalid metadata for post #' . $post_id . ', type: ' . $entry['entry_type']);
                    continue;
                }
                
                $exists = false;
                foreach ($existing_entries as $key => $existing) {
                    if (isset($existing['metadata'])) {
                        $metadata = json_decode($existing['metadata'], true);
                        if (is_array($metadata) && isset($metadata['entry_type']) && $metadata['entry_type'] === $entry['entry_type']) {
                            $this->logger->info('Updating existing entry for post #' . $post_id . ', type: ' . $entry['entry_type']);
                            $success = $this->knowledge_base->update_entry($existing['id'], array(
                                'question' => $entry['question'],
                                'answer' => $entry['answer'],
                                'metadata' => $entry['metadata'],
                            ));
                            if ($success === false) {
                                $this->logger->error('Failed to update entry for post #' . $post_id . ', type: ' . $entry['entry_type']);
                            }
                            unset($existing_entries[$key]);
                            $exists = true;
                            break;
                        }
                    }
                }
                
                if (!$exists) {
                    $this->logger->info('Adding new entry for post #' . $post_id . ', type: ' . $entry['entry_type']);
                    $entry_id = $this->knowledge_base->add_entry(
                        $entry['question'],
                        $entry['answer'],
                        'wp_content',
                        $entry['confidence'],
                        true,
                        $entry['metadata']
                    );
                    if ($entry_id) {
                        $added++;
                    } else {
                        $this->logger->error('Failed to add entry for post #' . $post_id . ', type: ' . $entry['entry_type']);
                    }
                }
            }
            
            // Delete obsolete entries
            foreach ($existing_entries as $existing) {
                $this->logger->info('Deleting obsolete entry #' . $existing['id'] . ' for post #' . $post_id);
                $success = $this->knowledge_base->delete_entry($existing['id']);
                if ($success === false) {
                    $this->logger->error('Failed to delete entry #' . $existing['id'] . ' for post #' . $post_id);
                }
            }
            
            // Update indexed timestamp
            update_post_meta($post_id, '_conversaai_last_indexed', time());
            $this->logger->info('Post #' . $post_id . ' indexed successfully, added ' . $added . ' entries');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error indexing post #' . $post_id . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return false;
        }
    }

    /**
     * Update the post index when a post is saved.
     *
     * @since    1.0.0
     * @param    int      $post_id    The post ID.
     * @param    WP_Post  $post       The post object.
     * @param    bool     $update     Whether this is an update.
     */
    public function update_post_index($post_id, $post, $update) {
        // Avoid autosaves and revisions
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Check if post type is indexable
        $settings = get_option('conversaai_pro_indexing_settings', array(
            'post_types' => array('page', 'post'),
        ));
        
        if (!in_array($post->post_type, $settings['post_types'], true)) {
            return;
        }
        
        $this->logger->info('Updating index for post #' . $post_id);
        $this->index_single_post($post, true);
    }

    /**
     * Remove a post from the index when it's deleted.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function remove_from_index($post_id) {
        // Get all entries for this post
        $existing_entries = $this->get_existing_entries($post_id);
        
        // Delete all entries
        foreach ($existing_entries as $entry) {
            $this->logger->info('Removing entry #' . $entry['id'] . ' for deleted post #' . $post_id);
            $success = $this->knowledge_base->delete_entry($entry['id']);
            if ($success === false) {
                $this->logger->error('Failed to delete entry #' . $entry['id'] . ' for post #' . $post_id);
            }
        }
        
        // Remove metadata
        delete_post_meta($post_id, '_conversaai_last_indexed');
        $this->logger->info('Removed post #' . $post_id . ' from index');
    }

    /**
     * Clean post content to remove floating elements and theme data.
     *
     * @since    1.0.0
     * @param    string    $content    The raw post content.
     * @return   string    The cleaned content.
     */
    private function clean_content($content) {
        // Remove shortcodes
        $content = strip_shortcodes($content);
        
        // Remove inline styles and scripts
        $content = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/i', '', $content);
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $content);
        
        // Remove floating elements (widgets, sidebars, menus)
        $content = preg_replace('/<div\b[^>]*class="[^"]*(widget|sidebar|menu)[^"]*"[^>]*>.*?<\/div>/i', '', $content);
        
        // Remove theme-specific classes (e.g., theme-container, template-part)
        $content = preg_replace('/<[^>]+class="[^"]*(theme|template)[^"]*"[^>]*>/i', '', $content);
        
        // Remove extra HTML and clean whitespace
        $content = wp_strip_all_tags($content, true);
        $content = trim(preg_replace('/\s+/', ' ', $content));
        
        return $content;
    }

    /**
     * Generate knowledge base entries for a post.
     *
     * @since    1.0.0
     * @param    WP_Post    $post      The post object.
     * @param    string     $content   The cleaned post content.
     * @return   array      The generated entries.
     */
    private function generate_post_entries($post, $content) {
        $entries = array();
        $post_id = $post->ID;
        
        // Basic post information entry
        $title = !empty($post->post_title) ? $post->post_title : 'Untitled ' . ucfirst($post->post_type);
        $entries[] = array(
            'question' => sprintf(__('What is the %s titled "%s" about?', 'conversaai-pro-wp'), $post->post_type, $title),
            'answer' => $this->format_post_summary($post, $content),
            'confidence' => 0.9,
            'entry_type' => 'basic_info',
            'metadata' => $this->prepare_metadata($post_id, 'basic_info', $post->post_type),
        );
        
        // Full content entry
        if (!empty($content)) {
            $entries[] = array(
                'question' => sprintf(__('Can you describe the %s titled "%s" in detail?', 'conversaai-pro-wp'), $post->post_type, $title),
                'answer' => $content,
                'confidence' => 0.85,
                'entry_type' => 'full_content',
                'metadata' => $this->prepare_metadata($post_id, 'full_content', $post->post_type),
            );
        }
        
        return $entries;
    }

    /**
     * Format post summary.
     *
     * @since    1.0.0
     * @param    WP_Post    $post      The post object.
     * @param    string     $content   The cleaned post content.
     * @return   string     The formatted summary.
     */
    private function format_post_summary($post, $content) {
        $title = !empty($post->post_title) ? $post->post_title : 'Untitled ' . ucfirst($post->post_type);
        $summary = sprintf(__('%s is a %s.', 'conversaai-pro-wp'), $title, $post->post_type);
        
        // Add categories if post type supports them
        if (post_type_supports($post->post_type, 'categories')) {
            $categories = get_the_category($post->ID);
            if (!empty($categories) && !is_wp_error($categories)) {
                $category_names = wp_list_pluck($categories, 'name');
                $summary .= ' ' . sprintf(__('It belongs to the %s categories.', 'conversaai-pro-wp'), implode(', ', $category_names));
            }
        }
        
        // Add excerpt or trimmed content
        if (!empty($content)) {
            $summary .= ' ' . wp_trim_words($content, 50, '...');
        } elseif (!empty($post->post_excerpt)) {
            $summary .= ' ' . wp_trim_words($post->post_excerpt, 50, '...');
        }
        
        // Add permalink
        $summary .= ' ' . sprintf(__('View it at %s.', 'conversaai-pro-wp'), get_permalink($post->ID));
        
        return trim($summary);
    }

    /**
     * Get existing knowledge base entries for a post.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @return   array     The existing entries.
     */
    private function get_existing_entries($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        // Modified: Add checks for non-empty metadata to avoid JSON_EXTRACT errors
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE 
            metadata IS NOT NULL AND 
            metadata != '' AND 
            JSON_EXTRACT(metadata, '$.post_id') = %d AND
            JSON_EXTRACT(metadata, '$.source') = %s",
            $post_id,
            'wp_content'
        );
        
        $entries = $wpdb->get_results($query, ARRAY_A);
        
        if ($entries !== null && $wpdb->last_error === '') {
            return $entries ?: array();
        }
        
        // Fallback: Fetch entries with valid JSON only
        $this->logger->warning('JSON_EXTRACT failed for post #' . $post_id . ', using PHP fallback. Error: ' . $wpdb->last_error);
        $entries = array();
        $valid_entries = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE 
            metadata IS NOT NULL AND 
            metadata != '' AND 
            metadata LIKE '%\"source\":\"wp_content\"%'",
            ARRAY_A
        );
        
        foreach ($valid_entries as $entry) {
            $metadata = json_decode($entry['metadata'], true);
            if (is_array($metadata) &&
                isset($metadata['post_id']) && $metadata['post_id'] == $post_id &&
                isset($metadata['source']) && $metadata['source'] == 'wp_content') {
                $entries[] = $entry;
            }
        }
        
        return $entries;
    }

    /**
     * Prepare metadata for a knowledge base entry.
     *
     * @since    1.0.0
     * @param    int       $post_id      The post ID.
     * @param    string    $entry_type   The entry type.
     * @param    string    $post_type    The post type.
     * @return   string    JSON encoded metadata.
     */
    private function prepare_metadata($post_id, $entry_type, $post_type) {
        $metadata = array(
            'post_id' => (int) $post_id,
            'source' => 'wp_content',
            'entry_type' => sanitize_text_field($entry_type),
            'post_type' => sanitize_text_field($post_type),
            'indexed_at' => time(),
        );
        
        $encoded = wp_json_encode($metadata);
        if ($encoded === false) {
            $this->logger->error('Failed to encode metadata for post #' . $post_id . ', type: ' . $entry_type);
            return '{}'; // Modified: Return empty JSON object instead of empty string
        }
        
        return $encoded;
    }
}