<?php
/**
 * Database schema definitions.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/db
 */

namespace ConversaAI_Pro_WP\DB;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Database schema class.
 *
 * Defines and manages the database schema for the plugin.
 *
 * @since      1.0.0
 */
class Schema {

    /**
     * Get the schema for the conversations table.
     *
     * @since    1.0.0
     * @return   string    The SQL schema.
     */
    public static function get_conversations_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        return "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            channel varchar(50) NOT NULL DEFAULT 'webchat',
            messages longtext NOT NULL,
            metadata longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY channel (channel)
        ) $charset_collate;";
    }

    /**
     * Get the schema for the knowledge base table.
     *
     * @since    1.0.0
     * @return   string    The SQL schema.
     */
    public static function get_knowledge_base_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        
        return "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            answer longtext NOT NULL,
            topic varchar(100) DEFAULT NULL,
            confidence float DEFAULT 0.5,
            approved tinyint(1) DEFAULT 0,
            `metadata` longtext,
            usage_count int DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY topic (topic),
            FULLTEXT KEY question_idx (question)
        ) $charset_collate;";
    }

    /**
     * Get the schema for the analytics table.
     *
     * @since    1.0.0
     * @return   string    The SQL schema.
     */
    public static function get_analytics_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        return "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            conversation_count int DEFAULT 0,
            message_count int DEFAULT 0,
            ai_request_count int DEFAULT 0,
            kb_answer_count int DEFAULT 0,
            successful_conversation_count int DEFAULT 0,
            source varchar(50) DEFAULT NULL,
            metadata longtext,
            PRIMARY KEY  (id),
            UNIQUE KEY date_source (date, source)
        ) $charset_collate;";
    }

    /**
     * Create all database tables.
     *
     * @since    1.0.0
     */
    public static function create_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta(self::get_conversations_schema());
        dbDelta(self::get_knowledge_base_schema());
        dbDelta(self::get_analytics_schema());
    }

    /**
     * Database tables exist and match the schema.
     *
     * @since    1.0.0
     * @return   bool    True if all tables are correct, false otherwise.
     */
    public static function verify_tables() {
        global $wpdb;

        $tables = [
            CONVERSAAI_PRO_CONVERSATIONS_TABLE => [
                'id' => 'bigint(20)',
                'session_id' => 'varchar(255)',
                'user_id' => 'bigint(20)',
                'channel' => 'varchar(50)',
                'messages' => 'longtext',
                'metadata' => 'longtext',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
            ],
            CONVERSAAI_PRO_KNOWLEDGE_TABLE => [
                'id' => 'bigint(20)',
                'question' => 'text',
                'answer' => 'longtext',
                'topic' => 'varchar(100)',
                'confidence' => 'float',
                'approved' => 'tinyint(1)',
                'metadata' => 'longtext',
                'usage_count' => 'int',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
            ],
            CONVERSAAI_PRO_ANALYTICS_TABLE => [
                'id' => 'bigint(20)',
                'date' => 'date',
                'conversation_count' => 'int',
                'message_count' => 'int',
                'ai_request_count' => 'int',
                'kb_answer_count' => 'int',
                'successful_conversation_count' => 'int',
                'source' => 'varchar(50)',
                'metadata' => 'longtext',
            ],
        ];

        foreach ($tables as $table_name => $columns) {
            $full_table = $wpdb->prefix . $table_name;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table'") !== $full_table) {
                return false;
            }

            $db_columns = $wpdb->get_results("DESCRIBE $full_table");
            $column_names = array_column($db_columns, 'Field');
            foreach ($columns as $col => $type) {
                if (!in_array($col, $column_names)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Drop all database tables (for uninstall or testing).
     *
     * @since    1.0.0
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE,
            $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE,
            $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE,
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Verify and fix database tables if needed.
     *
     * @since    1.0.0
     */
    public static function verify_and_fix_tables() {
        global $wpdb;
        
        // Check if tables exist
        $conversations_table = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        $knowledge_table = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
        $analytics_table = $wpdb->prefix . CONVERSAAI_PRO_ANALYTICS_TABLE;
        
        $tables_exist = true;
        
        // Check each table
        if ($wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") != $conversations_table) {
            $tables_exist = false;
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$knowledge_table'") != $knowledge_table) {
            $tables_exist = false;
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") != $analytics_table) {
            $tables_exist = false;
        }
        
        // If any table is missing, recreate all tables
        if (!$tables_exist) {
            error_log('ConversaAI Pro: Missing database tables, recreating...');
            self::create_tables();
            return true;
        }
        
        return false;
    }
}