<?php
/**
 * Knowledge Base import/export functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/db
 */

namespace ConversaAI_Pro_WP\DB;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Knowledge_Base;

/**
 * Knowledge Base Import/Export class.
 *
 * Handles importing and exporting knowledge base entries.
 *
 * @since      1.0.0
 */
class KB_Import_Export {

    /**
     * Export knowledge base entries to CSV.
     *
     * @since    1.0.0
     * @param    array     $args    Optional. Query arguments for filtering entries.
     * @return   string    The CSV data.
     */
    public function export_to_csv($args = array()) {
        $kb = new Knowledge_Base();
        $entries = $kb->get_entries($args);
        
        if (empty($entries)) {
            return '';
        }
        
        // Define CSV columns
        $columns = array(
            'id', 'question', 'answer', 'topic', 
            'confidence', 'approved', 'usage_count', 
            'created_at', 'updated_at'
        );
        
        // Create output buffer for CSV
        $output = fopen('php://temp', 'r+');
        
        // Add header row
        fputcsv($output, $columns);
        
        // Add data rows
        foreach ($entries as $entry) {
            fputcsv($output, $entry);
        }
        
        // Get the contents
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Import knowledge base entries from CSV.
     *
     * @since    1.0.0
     * @param    string    $csv_data    The CSV data to import.
     * @param    array     $options     Optional. Import options.
     * @return   array     Results with counts of imported, skipped, and error entries.
     */
    public function import_from_csv($csv_data, $options = array()) {
        $defaults = array(
            'skip_header' => true,
            'update_existing' => false,
            'approved_by_default' => false,
        );
        
        $options = wp_parse_args($options, $defaults);
        $kb = new Knowledge_Base();
        
        // Results tracking
        $results = array(
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => array(),
        );
        
        // Parse CSV
        $lines = explode("\n", $csv_data);
        if (empty($lines)) {
            return $results;
        }
        
        // Skip header if requested
        if ($options['skip_header']) {
            array_shift($lines);
        }
        
        // Process each line
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $row = str_getcsv($line);
            
            // Basic validation - need at least question and answer
            if (count($row) < 2 || empty($row[1]) || empty($row[2])) {
                $results['errors']++;
                $results['error_details'][] = "Line " . ($line_num + 1) . ": Missing required fields";
                continue;
            }
            
            // Check if entry already exists (by exact question match)
            $existing_entries = $kb->get_entries(array(
                'question_exact' => $row[1],
                'limit' => 1,
            ));
            
            // If entry exists and we're not updating, skip
            if (!empty($existing_entries) && !$options['update_existing']) {
                $results['skipped']++;
                continue;
            }
            
            // Extract fields (using array positions corresponding to the export format)
            $id = isset($row[0]) ? intval($row[0]) : 0;
            $question = wp_unslash($row[1]);  // Unslash to prevent double escaping
            $answer = wp_unslash($row[2]);    // Unslash to prevent double escaping
            $topic = isset($row[3]) ? wp_unslash($row[3]) : '';
            $confidence = isset($row[4]) ? floatval($row[4]) : 0.5;
            $approved = isset($row[5]) ? (bool)$row[5] : $options['approved_by_default'];
            
            // If updating existing entry
            if (!empty($existing_entries) && $options['update_existing']) {
                $existing_id = $existing_entries[0]['id'];
                $update_data = array(
                    'question' => $question,
                    'answer' => $answer,
                    'topic' => $topic,
                    'confidence' => $confidence,
                    'approved' => $approved ? 1 : 0,
                );
                
                $success = $kb->update_entry($existing_id, $update_data);
                if ($success) {
                    $results['imported']++;
                } else {
                    $results['errors']++;
                    $results['error_details'][] = "Line " . ($line_num + 1) . ": Failed to update existing entry";
                }
            } else {
                // Add new entry
                $result = $kb->add_entry($question, $answer, $topic, $confidence, $approved);
                if ($result) {
                    $results['imported']++;
                } else {
                    $results['errors']++;
                    $results['error_details'][] = "Line " . ($line_num + 1) . ": Failed to add entry";
                }
            }
        }
        
        return $results;
    }

    /**
     * Export knowledge base entries to JSON.
     *
     * @since    1.0.0
     * @param    array     $args    Optional. Query arguments for filtering entries.
     * @return   string    The JSON data.
     */
    public function export_to_json($args = array()) {
        $kb = new Knowledge_Base();
        $entries = $kb->get_entries($args);
        
        return json_encode($entries, JSON_PRETTY_PRINT);
    }

    /**
     * Import knowledge base entries from JSON.
     *
     * @since    1.0.0
     * @param    string    $json_data    The JSON data to import.
     * @param    array     $options      Optional. Import options.
     * @return   array     Results with counts of imported, skipped, and error entries.
     */
    public function import_from_json($json_data, $options = array()) {
        $defaults = array(
            'update_existing' => false,
            'approved_by_default' => false,
        );
        
        $options = wp_parse_args($options, $defaults);
        $kb = new Knowledge_Base();
        
        // Results tracking
        $results = array(
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => array(),
        );
        
        // Parse JSON
        $entries = json_decode($json_data, true);
        if (empty($entries) || !is_array($entries)) {
            $results['errors']++;
            $results['error_details'][] = "Invalid JSON data";
            return $results;
        }
        
        // Process each entry
        foreach ($entries as $index => $entry) {
            // Basic validation - need at least question and answer
            if (empty($entry['question']) || empty($entry['answer'])) {
                $results['errors']++;
                $results['error_details'][] = "Entry " . ($index + 1) . ": Missing required fields";
                continue;
            }
            
            // Check if entry already exists (by exact question match)
            $existing_entries = $kb->get_entries(array(
                'question_exact' => $entry['question'],
                'limit' => 1,
            ));
            
            // If entry exists and we're not updating, skip
            if (!empty($existing_entries) && !$options['update_existing']) {
                $results['skipped']++;
                continue;
            }
            
            // Unslash data to prevent double escaping
            $question = wp_unslash($entry['question']);
            $answer = wp_unslash($entry['answer']);
            $topic = isset($entry['topic']) ? wp_unslash($entry['topic']) : '';
            $confidence = isset($entry['confidence']) ? floatval($entry['confidence']) : 0.5;
            $approved = isset($entry['approved']) ? (bool)$entry['approved'] : $options['approved_by_default'];
            
            // If updating existing entry
            if (!empty($existing_entries) && $options['update_existing']) {
                $existing_id = $existing_entries[0]['id'];
                $update_data = array(
                    'question' => $question,
                    'answer' => $answer,
                    'topic' => $topic,
                    'confidence' => $confidence,
                    'approved' => $approved ? 1 : 0,
                );
                
                $success = $kb->update_entry($existing_id, $update_data);
                if ($success) {
                    $results['imported']++;
                } else {
                    $results['errors']++;
                    $results['error_details'][] = "Entry " . ($index + 1) . ": Failed to update existing entry";
                }
            } else {
                // Add new entry
                $result = $kb->add_entry($question, $answer, $topic, $confidence, $approved);
                if ($result) {
                    $results['imported']++;
                } else {
                    $results['errors']++;
                    $results['error_details'][] = "Entry " . ($index + 1) . ": Failed to add entry";
                }
            }
        }
        
        return $results;
    }
}