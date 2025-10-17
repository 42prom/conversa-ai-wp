<?php
/**
 * API Request utility class.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/utils
 */

namespace ConversaAI_Pro_WP\Utils;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * API Request utility class.
 *
 * Handles HTTP requests to external APIs.
 *
 * @since      1.0.0
 */
class API_Request {

    /**
     * Default request timeout.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $timeout    The request timeout in seconds.
     */
    private $timeout = 30;

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Utils\Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * Initialize the class.
     * 
     * @since    1.0.0
     */
    public function __construct() {
        $this->logger = new Logger();
    }

    /**
     * Make a GET request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @param    array     $params    Optional. Query parameters.
     * @return   array|\WP_Error    The response or WP_Error on failure.
     */
    public function get($url, $headers = array(), $params = array()) {
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
            'sslverify' => apply_filters('conversaai_pro_api_sslverify', true),
        );
        
        $this->logger->info('Making GET request', array(
            'url' => $url,
            'timeout' => $this->timeout,
        ));
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('GET request failed', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
        } else {
            $this->logger->info('GET request successful', array(
                'url' => $url,
                'status' => wp_remote_retrieve_response_code($response),
            ));
        }
        
        $response = $this->check_rate_limits($response);
    
        return $response;
    }

    /**
     * Make a POST request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    mixed     $data      The data to send.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|\WP_Error    The response or WP_Error on failure.
     */
    public function post($url, $data, $headers = array()) {
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => is_array($data) || is_object($data) ? wp_json_encode($data) : $data,
            'sslverify' => apply_filters('conversaai_pro_api_sslverify', true),
        );
        
        // Set default Content-Type if not provided
        if (!isset($headers['Content-Type'])) {
            $args['headers']['Content-Type'] = 'application/json';
        }
        
        $this->logger->info('Making POST request', array(
            'url' => $url,
            'timeout' => $this->timeout,
        ));
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('POST request failed', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
        } else {
            $this->logger->info('POST request successful', array(
                'url' => $url,
                'status' => wp_remote_retrieve_response_code($response),
            ));
        }
        
        $response = $this->check_rate_limits($response);
    
        return $response;
    }

    /**
     * Make a PUT request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    mixed     $data      The data to send.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|\WP_Error    The response or WP_Error on failure.
     */
    public function put($url, $data, $headers = array()) {
        $args = array(
            'method' => 'PUT',
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => is_array($data) || is_object($data) ? wp_json_encode($data) : $data,
            'sslverify' => apply_filters('conversaai_pro_api_sslverify', true),
        );
        
        // Set default Content-Type if not provided
        if (!isset($headers['Content-Type'])) {
            $args['headers']['Content-Type'] = 'application/json';
        }
        
        $this->logger->info('Making PUT request', array(
            'url' => $url,
            'timeout' => $this->timeout,
        ));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('PUT request failed', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
        } else {
            $this->logger->info('PUT request successful', array(
                'url' => $url,
                'status' => wp_remote_retrieve_response_code($response),
            ));
        }
        
        return $response;
    }

    /**
     * Make a DELETE request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|\WP_Error    The response or WP_Error on failure.
     */
    public function delete($url, $headers = array()) {
        $args = array(
            'method' => 'DELETE',
            'timeout' => $this->timeout,
            'headers' => $headers,
            'sslverify' => apply_filters('conversaai_pro_api_sslverify', true),
        );
        
        $this->logger->info('Making DELETE request', array(
            'url' => $url,
            'timeout' => $this->timeout,
        ));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('DELETE request failed', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
        } else {
            $this->logger->info('DELETE request successful', array(
                'url' => $url,
                'status' => wp_remote_retrieve_response_code($response),
            ));
        }
        
        return $response;
    }

    /**
     * Set the request timeout.
     *
     * @since    1.0.0
     * @param    int    $timeout    The timeout in seconds.
     */
    public function set_timeout($timeout) {
        $this->timeout = (int) $timeout;
    }

    /**
     * Parse API response and handle errors.
     *
     * @since    1.0.0
     * @param    array|\WP_Error    $response    The API response.
     * @return   array    The parsed response data.
     */
    public function parse_response($response) {
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Try to decode JSON response
        $data = json_decode($body, true);
        
        // Check for JSON decoding errors
        if ($body && json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Invalid JSON response',
                'status' => $status_code,
                'raw_body' => $body,
            );
        }
        
        // Check for API errors
        if ($status_code >= 400) {
            $error_message = isset($data['error']['message']) ? 
                $data['error']['message'] : 
                'API error. Status code: ' . $status_code;
                
            return array(
                'success' => false,
                'error' => $error_message,
                'status' => $status_code,
                'data' => $data,
            );
        }
        
        return array(
            'success' => true,
            'status' => $status_code,
            'data' => $data,
        );
    }

    /**
     * Check for rate limits and apply backoff strategy if needed.
     *
     * @since    1.0.0
     * @param    array|\WP_Error    $response    The API response.
     * @return   array|\WP_Error    The original response.
     */
    private function check_rate_limits($response) {
        if (is_wp_error($response)) {
            return $response;
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        // Check for rate limit headers from Facebook/Meta
        if (isset($headers['x-app-usage']) || isset($headers['x-business-use-case-usage'])) {
            // Parse usage data
            $app_usage = json_decode($headers['x-app-usage'] ?? '{}', true);
            $business_usage = json_decode($headers['x-business-use-case-usage'] ?? '{}', true);
            
            // Log usage data
            $this->logger->info('API Usage metrics', [
                'app_usage' => $app_usage,
                'business_usage' => $business_usage
            ]);
            
            // Check app usage limits
            if (isset($app_usage['call_count']) && $app_usage['call_count'] > 80) {
                // Approaching 80% of limit, apply delay
                $this->logger->warning('Approaching API rate limits', ['usage' => $app_usage]);
                sleep(2); // Simple backoff
            }
            
            // Check business usage limits
            if (!empty($business_usage)) {
                foreach ($business_usage as $business_type => $usage) {
                    if (isset($usage['call_count']) && $usage['call_count'] > 80) {
                        $this->logger->warning('Approaching business API rate limits', [
                            'business_type' => $business_type,
                            'usage' => $usage
                        ]);
                        sleep(3); // Longer backoff for business rate limits
                    }
                }
            }
        }
        
        // Check for 429 Too Many Requests status code
        if ($status_code === 429) {
            $this->logger->error('Rate limit exceeded', [
                'status' => $status_code,
                'headers' => $headers
            ]);
            
            // Get retry-after header if available
            $retry_after = isset($headers['retry-after']) ? intval($headers['retry-after']) : 60;
            
            // Apply backoff (but cap at 5 minutes to avoid excessive waiting)
            $backoff = min($retry_after, 300);
            $this->logger->info("Rate limited. Backing off for {$backoff} seconds");
            
            // Sleep for the backoff period
            sleep($backoff);
        }
        
        return $response;
    }
}