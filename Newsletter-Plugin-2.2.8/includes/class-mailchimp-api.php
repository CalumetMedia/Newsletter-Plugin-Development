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

    /**
     * Create a new Mailchimp campaign with custom subject line and campaign name
     *
     * @param string $newsletter_slug
     * @param string $subject_line
     * @param string $campaign_name
     * @return array|WP_Error
     */
public function create_campaign($newsletter_slug, $subject_line = '', $campaign_name = '') {
    $list_id = get_option('mailchimp_list_id');
    if (empty($list_id)) {
        return new WP_Error('missing_list', 'Mailchimp List ID is not set.');
    }

    // Get the global Mailchimp settings
    $from_name = get_option('mailchimp_from_name');
    $reply_to = get_option('mailchimp_reply_to');

    if (empty($from_name) || empty($reply_to)) {
        return new WP_Error('missing_settings', 'Missing required settings for campaign creation');
    }

    $payload = [
        'type' => 'regular',
        'recipients' => [
            'list_id' => $list_id
        ],
        'settings' => [
            'subject_line' => $subject_line ?: 'Newsletter ' . date('Y-m-d'),
            'title' => $campaign_name ?: 'Newsletter ' . date('Y-m-d'),
            'from_name' => $from_name,
            'reply_to' => $reply_to,
            'to_name' => '*|FNAME|*',
            'authenticate' => true,
            'auto_footer' => true,
            'inline_css' => true
        ]
    ];

    return $this->make_request('campaigns', 'POST', $payload);
}

    public function set_campaign_content($campaign_id, $html_content) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }

        return $this->make_request("campaigns/$campaign_id/content", 'PUT', [
            'html' => $html_content
        ]);
    }

    public function send_campaign($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }

        return $this->make_request("campaigns/$campaign_id/actions/send", 'POST');
    }

    /**
     * Retrieve newsletter settings including subject_line and campaign_name
     *
     * @param string $newsletter_slug
     * @return array
     */
    private function get_newsletter_settings($newsletter_slug) {
        return [
            'subject_line' => get_option("newsletter_subject_line_$newsletter_slug", ''),
            'campaign_name' => get_option("newsletter_campaign_name_$newsletter_slug", ''),
            'from_name' => get_option("newsletter_from_name_$newsletter_slug", 'Your Name'),
            'reply_to' => get_option("newsletter_reply_to_$newsletter_slug", 'your-email@example.com')
        ];
    }

    private function make_request($endpoint, $method = 'GET', $payload = null) {
        if (!$this->api_key) {
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

        $response = wp_remote_request($this->api_endpoint . $endpoint, $args);

        if (is_wp_error($response)) {
            np_log_error('Mailchimp API Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            np_log_error('Mailchimp API Error Response: ' . print_r($body, true));
            return new WP_Error(
                'api_error',
                isset($body['detail']) ? $body['detail'] : 'Unknown API error',
                $body
            );
        }

        return $body;
    }
}
?>
