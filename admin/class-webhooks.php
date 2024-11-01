<?php

namespace TOT\Admin;

use TOT\tot_debugger;

class Webhooks {

    public function __construct() {
    }

    public function register_wordpress_hooks()
    {
        add_action('widgets_init', tot_add_query_params_closure('authorization'));
        add_action('tot_webhook_success', array($this, 'webhook_success_logger'), 99, 3);
        add_action('tot_webhook_rejected', array($this, 'webhook_rejected_logger'), 99, 2);

        // This had to be moved to later processing so that our Checkout_Bridge was initialized.
        add_action('wp', array($this, 'process_route_from_wp'), 99);
    }

    public function process_route_from_wp()
    {
        // tot_log_in_debug_log('debug:webhook', 'default_process_webhook');
        $route = apply_filters('tot_webhooks_route', 'token-of-trust/webhooks');
        $this->process_webhook($route);
    }

    public function process_webhook($webhook_route)
    {
        $current_route = $_SERVER['REQUEST_URI'];
        $full_webhook_route = site_url() . '/' . $webhook_route;

        if (!$this->is_tot_webhook($current_route, $full_webhook_route)) {
            return;
        }
	    // tot_debugger::inst()->log('process_webhook', $current_route);
        $this->do_process_webhook();
    }

// TODO - consider moving webhooks into proper Wordpress REST api.
//
//    add_filter( 'rest_url_prefix', array($this, 'tot_api_slug'));
//    add_action('rest_api_init', array($this, 'register_rest_route'));
//    public function tot_api_slug( $slug ) {
//        return null;
//    }
//    public function register_rest_route()
//    {
//        $route = apply_filters('tot_webhooks_route', '/webhooks');
//        register_rest_route('token-of-trust', $route, array(
//            'methods' => 'POST',
//            'callback' => array($this, 'do_process_webhook')
//        ));
//    }

    public function do_process_webhook() {

        if (!$this->validate_request()) {
            $this->reject_request();
        } else {
            $this->successful_request();
        }
    }

    public function reject_request() {
        $input = $this->get_post_body();
        $body = json_decode($input);
        do_action('tot_webhook_rejected', $body, $input);
        wp_send_json(json_decode('{}'), 400);
    }

    public function successful_request()
    {
        $input = $this->get_post_body();
        $body = json_decode($input);
        do_action('tot_webhook_success', $this->get_name($body), $body, $input);
        wp_send_json(json_decode('{}'), 200);
    }

    public function get_name($body)
    {
        if (isset($body->content->sentWebhookBody->name)) {
            return $body->content->sentWebhookBody->name;
        }

        if (isset($body->name)) {
            return $body->name;
        }
    }

    public function validate_request()
    {
        if (($_SERVER['REQUEST_METHOD'] !== 'POST')) {
            return false;
        }

        $given_signature = $this->get_auth_header();

        if (!isset($given_signature)) {
            return false;
        }

        $input = $this->get_post_body();
        if (!isset($input)) {
            return false;
        }

        $body = json_decode($input);
        if ($body == null) {
            return false;
        }

        $expected_signature = $this->create_signature($input);
        if (!isset($expected_signature) || $expected_signature !== $given_signature) {
            $this->webhook_rejected_logger($body, $input);
            return false;
        }

        return true;
    }

    public function create_signature($input)
    {
        if (isset($input)) {
            // Using sanitize_text_field here because the header is sanitize along the way and we're checking for a match.
            $hmacKey = $this->get_hmac_key();
            if (empty($hmacKey)) {
	            tot_debugger::inst()->log("Empty hmacKey!", 'Empty hmacKey!', 'error');
            } else {
                $hmac = base64_encode(hash_hmac('sha1', $input, $hmacKey, true));
                if (!empty($hmac)) {
                    return sanitize_text_field($hmac);
                }
            }
        }

        return null;
    }

    public function get_hmac_key()
    {
        $tot_keys = tot_get_keys();
        if (!is_wp_error($tot_keys) && isset($tot_keys)) {
            $is_production = tot_is_production();
            $keys = ($is_production && isset($tot_keys['live'])) ? $tot_keys['live'] :
                (isset($tot_keys['test']) ? $tot_keys['test'] : null);
            $webhooks = isset($keys['webhooks']) ? $keys['webhooks'] : null;
            if (!empty($webhooks)) {
                $json_decoded = json_decode($webhooks);
                $hmacKey = ($json_decoded && isset($json_decoded->hmacKey)) ? $json_decoded->hmacKey : null;
                if (empty($hmacKey)) {
                    $this->webhook_log_message('Missing hmacKey - cannot validate webhook request.', $webhooks);
                }
                return $hmacKey;
            }
        }

        return null;
    }

    public function get_auth_header()
    {
        $auth = null;

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $auth = $headers['Authorization'];
            } else if (isset($headers['authorization'])) {
                $auth = $headers['authorization'];
            }
        }
        if ($auth === null && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($auth === null) {
            $authorization_in_query = get_query_var('authorization', null);
            if (isset($authorization_in_query)) {
                $auth = $authorization_in_query;
            }
        }

        $auth = !empty($auth) ? sanitize_text_field($auth) : null;
        return $auth;
    }

    public function get_post_body()
    {
        return @file_get_contents('php://input');
    }

    public function webhook_success_logger($webhook_name, $webhook_body, $raw)
    {
        $json = json_encode($webhook_body, JSON_PRETTY_PRINT);
        $hook_log = get_option("tot_hook_log", array());
        $expected_signature = $this->create_signature($raw);

        $entry = array(
            'timestamp' => current_time('mysql'),
            'body' => substr($json, 0, apply_filters('tot_webhook_log_item_max_length', 3000)),
            'type' => 'success',
            'expected_signature' => $expected_signature,
            'given_signature' => $this->get_auth_header()
        );

        // tot_log_in_debug_log('webhook_success_logger:adding', array(
        //    '$entry' => $entry,
        // ));

        array_unshift($hook_log, $entry);
        $hook_log = array_slice($hook_log, 0, apply_filters('tot_webhook_log_max_length', 50));

        update_option("tot_hook_log", $hook_log, false);
    }

    function webhook_rejected_logger($webhook_body, $raw)
    {

        $json = json_encode($webhook_body, JSON_PRETTY_PRINT);
        $hook_log = get_option("tot_hook_log", array());
        $expected_signature = $this->create_signature($raw);

        array_unshift($hook_log, array(
            'timestamp' => current_time('mysql'),
            'body' => substr($json, 0, apply_filters('tot_webhook_log_item_max_length', 3000)),
            'type' => 'error',
            'expected_signature' => $expected_signature,
            'given_signature' => $this->get_auth_header()
        ));

        $hook_log = array_slice($hook_log, 0, apply_filters('tot_webhook_log_max_length', 50));

        update_option("tot_hook_log", $hook_log, false);

    }

    function webhook_log_message($message, $data)
    {
        $hook_log = get_option("tot_hook_log", array());

        array_unshift($hook_log, array(
            'timestamp' => current_time('mysql'),
            'status_message' => $message,
            'body' => $data,
            'type' => 'info'
        ));

        $hook_log = array_slice($hook_log, 0, apply_filters('tot_webhook_log_max_length', 50));

        update_option("tot_hook_log", $hook_log, false);

    }

    /**
     * @param $current_route
     * @param $site_url
     * @return bool
     */
    public function is_tot_webhook($current_route, $site_url)
    {
        $normalized_current_url = tot_normalize_url_path($current_route);
        $url_parse = wp_parse_url($normalized_current_url);
        $normalized_current_url = isset($url_parse['path']) ? $url_parse['path'] : '';

        $normalized_site_url = tot_normalize_url_path($site_url);
        $is_webhook = $normalized_current_url === $normalized_site_url;
        return $is_webhook;
    }

}
