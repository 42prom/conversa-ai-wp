<?php
/**
 * Interface for AI providers.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/ai
 */

namespace ConversaAI_Pro_WP\Integrations\AI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Interface for AI providers.
 *
 * Defines the methods that all AI providers must implement.
 *
 * @since      1.0.0
 */
interface AI_Provider {

    /**
     * Initialize the provider with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Provider-specific settings.
     */
    public function initialize($settings);

    /**
     * Process a user query and generate a response.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Response data with message and other info.
     */
    public function process_query($query, $conversation_history = array());

    /**
     * Get available models for this provider.
     *
     * @since    1.0.0
     * @return   array    List of available models.
     */
    public function get_available_models();

    /**
     * Check if the provider is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the provider is available.
     */
    public function is_available();

    /**
     * Get the provider's name.
     *
     * @since    1.0.0
     * @return   string   The provider name.
     */
    public function get_name();

    /**
     * Process a user query with additional context and generate a response.
     *
     * @since    1.0.0
     * @param    string    $query                User's query text.
     * @param    string    $context              Additional context for the query.
     * @param    array     $conversation_history    Previous conversation messages.
     * @return   array     Response data with message and other info.
     */
    public function process_query_with_context($query, $context, $conversation_history = array());
}