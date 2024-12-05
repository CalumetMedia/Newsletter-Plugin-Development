<?php
if (!defined('ABSPATH')) exit;

class Newsletter_Mailchimp_API {
    private $api_key;
    private $api_endpoint = 'https://[dc].api.mailchimp.com/3.0/';
    private $datacenter;

    public function __construct() {
        $this->api_key = get_option('mailchimp_api_key', '');
        if ($this->api_key) {
            $this->datacenter = substr(strstr($this->api_key, '-'), 1);
            $this->api_endpoint = str_replace('[dc]', $this->datacenter, $this->api_endpoint);
        }
    }

    public function validate_connection() {
        return $this->make_request('ping');
    }

    public function create_campaign($newsletter_slug, $subject_line, $campaign_name) {
        error_log("Creating campaign for slug: $newsletter_slug");
        
        $list_id = get_option('mailchimp_list_id');
        if (empty($list_id)) {
            error_log('Mailchimp List ID missing');
            return new WP_Error('missing_list', 'Mailchimp List ID is not set.');
        }

        // Get global from_name if newsletter specific is empty
        $from_name = get_option('mailchimp_from_name');
        if (empty($from_name)) {
            $from_name = get_option("newsletter_from_name_$newsletter_slug", get_bloginfo('name'));
        }

        // Get reply_to email
        $reply_to = get_option("newsletter_reply_to_$newsletter_slug", '');
        if (empty($reply_to)) {
            $reply_to = get_option('mailchimp_reply_to', get_option('admin_email'));
        }

        $payload = [
            'type' => 'regular',
            'recipients' => [
                'list_id' => $list_id
            ],
            'settings' => [
                'subject_line' => !empty($subject_line) ? $subject_line : 'Newsletter ' . date('Y-m-d'),
                'title' => !empty($campaign_name) ? $campaign_name : 'Newsletter ' . date('Y-m-d'),
                'from_name' => $from_name,
                'reply_to' => $reply_to,
                'to_name' => '*|FNAME|*',
                'authenticate' => true,
                'auto_footer' => false,
                'inline_css' => true
            ]
        ];

        error_log('Mailchimp API Payload: ' . print_r($payload, true));
        
        $response = $this->make_request('campaigns', 'POST', $payload);
        
        if (is_wp_error($response)) {
            error_log('Campaign creation error: ' . $response->get_error_message());
        } else {
            error_log('Campaign created successfully: ' . print_r($response, true));
        }
        
        return $response;
    }

    public function set_campaign_content($campaign_id, $html_content) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }

        $payload = [
            'html' => $html_content
        ];

        error_log('Setting content for campaign: ' . $campaign_id);
        return $this->make_request("campaigns/$campaign_id/content", 'PUT', $payload);
    }

public function send_test_email($campaign_id, $test_email) {
    if (empty($campaign_id) || empty($test_email)) {
        return new WP_Error('missing_params', 'Campaign ID and test email are required');
    }
    
    $payload = [
        'test_emails' => [$test_email],
        'send_type' => 'html'
    ];

    $response = $this->make_request("campaigns/$campaign_id/actions/test", 'POST', $payload);
    
    // Delete the test campaign after sending
    $this->make_request("campaigns/$campaign_id", 'DELETE');
    
    return $response;
}

    public function send_campaign($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }
        return $this->make_request("campaigns/$campaign_id/actions/send", 'POST');
    }

    private function make_request($endpoint, $method = 'GET', $payload = null) {
        if (!$this->api_key) {
            error_log('Mailchimp API key not set');
            return new WP_Error('no_api_key', 'Mailchimp API key is not set');
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->api_key),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];

        if ($payload !== null) {
            $args['body'] = json_encode($payload);
        }

        error_log("Making request to: " . $this->api_endpoint . $endpoint);
        error_log("Request args: " . print_r($args, true));

        $response = wp_remote_request($this->api_endpoint . $endpoint, $args);

        if (is_wp_error($response)) {
            error_log('WP Remote Request Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        error_log("Response code: $code");
        error_log("Response body: " . print_r($body, true));

        if ($code >= 400) {
            $error_message = isset($body['detail']) ? $body['detail'] : 'Unknown API error';
            error_log("API Error ($code): $error_message");
            if (isset($body['errors'])) {
                error_log("Validation errors: " . print_r($body['errors'], true));
            }
            return new WP_Error('api_error', $error_message, $body);
        }

        return $body;
    }
}