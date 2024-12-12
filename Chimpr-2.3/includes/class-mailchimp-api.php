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

    public function get_datacenter() {
        return $this->datacenter;
    }

    public function validate_connection() {
        return $this->make_request('ping');
    }

    public function create_campaign($newsletter_slug, $subject_line, $campaign_name) {
        $list_id = get_option('mailchimp_list_id');
        if (empty($list_id)) {
            return new WP_Error('missing_list', 'Mailchimp List ID is not set.');
        }

        $newsletter_from_name = get_option("newsletter_from_name_$newsletter_slug", '');
        $newsletter_reply_to  = get_option("newsletter_reply_to_$newsletter_slug", '');

        $global_from_name = get_option('mailchimp_from_name', '');
        $global_reply_to  = get_option('mailchimp_reply_to', '');

        if (!empty($newsletter_from_name)) {
            $from_name = $newsletter_from_name;
        } elseif (!empty($global_from_name)) {
            $from_name = $global_from_name;
        } else {
            $from_name = get_bloginfo('name');
        }

        if (!empty($newsletter_reply_to)) {
            $reply_to = $newsletter_reply_to;
        } elseif (!empty($global_reply_to)) {
            $reply_to = $global_reply_to;
        } else {
            $reply_to = get_option('admin_email');
        }

        if (empty($from_name)) {
            return new WP_Error('missing_from_name', 'From name is missing. Check your Mailchimp/Newsletter settings.');
        }

        if (empty($reply_to)) {
            return new WP_Error('missing_reply_to', 'Reply-to email is missing. Check your Mailchimp/Newsletter settings.');
        }

        $recipients = ['list_id' => $list_id];
        $target_tags = get_option("newsletter_target_tags_$newsletter_slug", []);

        if (!empty($target_tags)) {
            $recipients['segment_opts'] = [
                'saved_segment_id' => intval($target_tags[0])
            ];
        }

        $payload = [
            'type' => 'regular',
            'recipients' => $recipients,
            'settings' => [
                'subject_line' => !empty($subject_line) ? $subject_line : 'Newsletter ' . date('Y-m-d'),
                'title'        => !empty($campaign_name) ? $campaign_name : 'Newsletter ' . date('Y-m-d'),
                'from_name'    => $from_name,
                'reply_to'     => $reply_to,
                'to_name'      => '*|FNAME|*',
                'authenticate' => true,
                'auto_footer'  => false,
                'inline_css'   => true
            ]
        ];

        $response = $this->make_request('campaigns', 'POST', $payload);
        return $response;
    }

    public function set_campaign_content($campaign_id, $html_content) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }

        $payload = ['html' => $html_content];
        return $this->make_request("campaigns/$campaign_id/content", 'PUT', $payload);
    }

    public function send_test_email($campaign_id, $test_email) {
        if (empty($campaign_id) || empty($test_email)) {
            return new WP_Error('missing_params', 'Campaign ID and test email are required');
        }

        $payload = [
            'test_emails' => [$test_email],
            'send_type'   => 'html'
        ];

        $response = $this->make_request("campaigns/$campaign_id/actions/test", 'POST', $payload);
        $this->make_request("campaigns/$campaign_id", 'DELETE');
        return $response;
    }

    public function get_list_tags($list_id) {
        if (empty($list_id)) {
            return new WP_Error('missing_list', 'List ID is required');
        }
        return $this->make_request("lists/$list_id/tag-search");
    }

    public function send_campaign($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }
        return $this->make_request("campaigns/$campaign_id/actions/send", 'POST');
    }

    public function schedule_campaign($campaign_id, $schedule_datetime) {
        if (empty($campaign_id) || empty($schedule_datetime)) {
            return new WP_Error('missing_params', 'Campaign ID and schedule datetime are required');
        }

        error_log('Scheduling campaign with datetime: ' . $schedule_datetime);

        $payload = [
            'schedule_time' => $schedule_datetime
        ];

        $response = $this->make_request("campaigns/$campaign_id/actions/schedule", 'POST', $payload);
        error_log('Mailchimp schedule response: ' . print_r($response, true));
        return $response;
    }

    public function get_segment_name($list_id, $segment_id) {
        if (empty($list_id) || empty($segment_id)) {
            return null;
        }

        $endpoint = "lists/$list_id/segments/$segment_id";
        $response = $this->make_request($endpoint, 'GET');

        if (is_wp_error($response)) {
            return null; 
        }

        return isset($response['name']) ? $response['name'] : null;
    }

    public function get_campaigns() {
        $all_campaigns = [];
        $offset = 0;
        $count = 100; // Fetch 100 at a time. Adjust if needed.
        while (true) {
            $response = $this->make_request('campaigns?count=' . $count . '&offset=' . $offset, 'GET');
            if (is_wp_error($response)) {
                return $response;
            }

            if (!isset($response['campaigns']) || empty($response['campaigns'])) {
                break;
            }

            $all_campaigns = array_merge($all_campaigns, $response['campaigns']);

            if (count($response['campaigns']) < $count) {
                break;
            }

            $offset += $count;
        }
        return $all_campaigns;
    }

    public function delete_campaign($campaign_id) {
        return $this->make_request("campaigns/$campaign_id", 'DELETE');
    }

    public function unschedule_campaign($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign_id', 'No campaign ID provided.');
        }
        return $this->make_request("campaigns/$campaign_id/actions/unschedule", 'POST');
    }

    private function make_request($endpoint, $method = 'GET', $payload = null) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Mailchimp API key is not set');
        }

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->api_key),
                'Content-Type'  => 'application/json'
            ],
            'timeout' => 30
        ];

        if ($payload !== null) {
            $args['body'] = json_encode($payload);
        }

        $response = wp_remote_request($this->api_endpoint . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            $error_message = isset($body['detail']) ? $body['detail'] : 'Unknown API error';
            return new WP_Error('api_error', $error_message, $body);
        }

        return $body;
    }
}
