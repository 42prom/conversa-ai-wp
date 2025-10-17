<?php
/**
 * Plugin-wide constants
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// General plugin constants
if (!defined('CONVERSAAI_PRO_NAME')) {
    define('CONVERSAAI_PRO_NAME', 'ConversaAI Pro for WP');
}

// Database related constants
if (!defined('CONVERSAAI_PRO_DB_VERSION')) {
    define('CONVERSAAI_PRO_DB_VERSION', '1.0.0');
}
if (!defined('CONVERSAAI_PRO_CONVERSATIONS_TABLE')) {
    define('CONVERSAAI_PRO_CONVERSATIONS_TABLE', 'conversaai_pro_conversations');
}
if (!defined('CONVERSAAI_PRO_KNOWLEDGE_TABLE')) {
    define('CONVERSAAI_PRO_KNOWLEDGE_TABLE', 'conversaai_pro_knowledge');
}
if (!defined('CONVERSAAI_PRO_ANALYTICS_TABLE')) {
    define('CONVERSAAI_PRO_ANALYTICS_TABLE', 'conversaai_pro_analytics');
}

// Feature flags (for easy enabling/disabling during development)
if (!defined('CONVERSAAI_PRO_ENABLE_WOOCOMMERCE')) {
    define('CONVERSAAI_PRO_ENABLE_WOOCOMMERCE', true);
}
if (!defined('CONVERSAAI_PRO_ENABLE_WHATSAPP')) {
    define('CONVERSAAI_PRO_ENABLE_WHATSAPP', false);
}
if (!defined('CONVERSAAI_PRO_ENABLE_MESSENGER')) {
    define('CONVERSAAI_PRO_ENABLE_MESSENGER', false);
}
if (!defined('CONVERSAAI_PRO_ENABLE_INSTAGRAM')) {
    define('CONVERSAAI_PRO_ENABLE_INSTAGRAM', false);
}
if (!defined('CONVERSAAI_PRO_ENABLE_LEARNING')) {
    define('CONVERSAAI_PRO_ENABLE_LEARNING', true);
}

// Default settings
if (!defined('CONVERSAAI_PRO_DEFAULT_AI_PROVIDER')) {
    define('CONVERSAAI_PRO_DEFAULT_AI_PROVIDER', 'openai');
}
if (!defined('CONVERSAAI_PRO_DEFAULT_MODEL')) {
    define('CONVERSAAI_PRO_DEFAULT_MODEL', 'gpt-3.5-turbo');
}
if (!defined('CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD')) {
    define('CONVERSAAI_PRO_DEFAULT_CONFIDENCE_THRESHOLD', 0.85);
}
if (!defined('CONVERSAAI_PRO_DEFAULT_TEMPERATURE')) {
    define('CONVERSAAI_PRO_DEFAULT_TEMPERATURE', 0.7);
}
if (!defined('CONVERSAAI_PRO_DEFAULT_MAX_TOKENS')) {
    define('CONVERSAAI_PRO_DEFAULT_MAX_TOKENS', 1024);
}

// Cache settings
if (!defined('CONVERSAAI_PRO_CACHE_DURATION')) {
    define('CONVERSAAI_PRO_CACHE_DURATION', 86400); // 24 hours in seconds
}