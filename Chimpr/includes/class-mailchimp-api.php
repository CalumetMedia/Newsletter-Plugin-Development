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
        $this->log("Creating campaign", [
            'slug' => $newsletter_slug,
            'subject' => $subject_line,
            'name' => $campaign_name
        ]);
        
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
        $target_tag = get_option("newsletter_target_tag_$newsletter_slug", '');

        if (!empty($target_tag)) {
            $recipients['segment_opts'] = [
                'saved_segment_id' => intval($target_tag)
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
            return new WP_Error('missing_list_id', 'List ID is required.');
        }

        // Debug the list ID
        error_log('Fetching tags for list ID: ' . $list_id);

        // Get all segments for the list - removed type filter to get all segments
        $response = $this->make_request("lists/$list_id/segments", 'GET', [
            'fields' => 'segments.id,segments.name,segments.type,segments.member_count,total_items',
            'count' => 100,
            'offset' => 0
        ]);

        if (is_wp_error($response)) {
            error_log('Mailchimp API Error fetching tags: ' . $response->get_error_message());
        } else {
            error_log('Mailchimp API Response: ' . print_r($response, true));
        }

        return $response;
    }

    public function send_campaign($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', 'Campaign ID is required');
        }
        return $this->make_request("campaigns/$campaign_id/actions/send", 'POST');
    }

    public function schedule_campaign($campaign_id, $timestamp) {
        $this->log("Scheduling campaign", [
            'campaign_id' => $campaign_id,
            'timestamp' => $timestamp,
            'formatted_local' => wp_date('Y-m-d H:i:s', $timestamp),
        ]);
        
        if (empty($campaign_id) || empty($timestamp)) {
            return new WP_Error('missing_params', 'Campaign ID and timestamp are required');
        }

        try {
            // Create from Unix timestamp
            $local_dt = new DateTime("@$timestamp");
            $local_dt->setTimezone(wp_timezone());
            
            // Debug logging
            error_log('Scheduling with local time: ' . $local_dt->format('Y-m-d H:i:s T'));

            // Convert to UTC for Mailchimp API
            $utc_dt = clone $local_dt;
            $utc_dt->setTimezone(new DateTimeZone('UTC'));
            $utc_schedule_time = $utc_dt->format('Y-m-d\TH:i:s\Z');

            error_log('UTC Schedule Time: ' . $utc_schedule_time);

            $payload = [
                'schedule_time' => $utc_schedule_time
            ];

            $response = $this->make_request("campaigns/$campaign_id/actions/schedule", 'POST', $payload);
            error_log('Mailchimp Schedule Response: ' . print_r($response, true));
            
            return $response;
        } catch (Exception $e) {
            error_log('Schedule Campaign Error: ' . $e->getMessage());
            return new WP_Error('schedule_error', $e->getMessage());
        }
    }

    public function get_upcoming_scheduled_campaigns($minutes_ahead = 75) {
        $response = $this->make_request('campaigns', 'GET', [
            'status' => 'schedule',
            'sort_field' => 'schedule_time',
            'sort_dir' => 'ASC'
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $tz = wp_timezone();
        $now = new DateTime('now', $tz);
        $cutoff = (clone $now)->modify("+{$minutes_ahead} minutes");

        $upcoming = [];
        if (!empty($response['campaigns'])) {
            foreach ($response['campaigns'] as $campaign) {
                if (!empty($campaign['settings']['schedule_time'])) {
                    // Convert UTC schedule time to local
                    $schedule_time = new DateTime($campaign['settings']['schedule_time']);
                    $schedule_time->setTimezone($tz);
                    
                    if ($schedule_time > $now && $schedule_time <= $cutoff) {
                        $campaign['settings']['schedule_time'] = $schedule_time->format('Y-m-d H:i:s');
                        $upcoming[] = $campaign;
                    }
                }
            }
        }

        return $upcoming;
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

    public function get_campaigns($offset = 0, $count = 10, $status = null) {
        $endpoint = 'campaigns?count=' . $count . '&offset=' . $offset;
        
        if ($status) {
            // Handle multiple statuses (e.g., 'save,schedule,paused')
            $statuses = explode(',', $status);
            if (count($statuses) > 1) {
                // For multiple statuses, we need to join them with commas for Mailchimp API
                $endpoint .= '&status=' . implode(',', array_map('trim', $statuses));
            } else {
                $endpoint .= '&status=' . $status;
            }
        }
        
        error_log('Mailchimp API Request: ' . $endpoint); // Debug log
        
        $response = $this->make_request($endpoint, 'GET');
        if (is_wp_error($response)) {
            error_log('Mailchimp API Error: ' . $response->get_error_message());
            return $response;
        }

        error_log('Mailchimp API Response: ' . print_r($response, true)); // Debug log

        return [
            'campaigns' => isset($response['campaigns']) ? $response['campaigns'] : [],
            'total_items' => isset($response['total_items']) ? $response['total_items'] : 0
        ];
    }

    public function get_campaign_count($status = null) {
        $endpoint = 'campaigns?count=1&offset=0';
        if ($status) {
            // Handle multiple statuses
            $statuses = explode(',', $status);
            if (count($statuses) > 1) {
                // For multiple statuses, we need to join them with commas
                $endpoint .= '&status=' . implode(',', array_map('trim', $statuses));
            } else {
                $endpoint .= '&status=' . $status;
            }
        }
        
        $response = $this->make_request($endpoint, 'GET');
        if (is_wp_error($response)) {
            return 0;
        }
        
        return isset($response['total_items']) ? $response['total_items'] : 0;
    }

    public function get_campaign_info($campaign_id) {
        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign_id', 'No campaign ID provided.');
        }

        $response = $this->make_request("campaigns/$campaign_id", 'GET');
        return $response;
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

    public function get_list_templates() {
        return $this->make_request('templates', 'GET', [
            'type' => 'user',
            'sort_field' => 'name',
            'sort_dir' => 'ASC'
        ]);
    }

    private function make_request($endpoint, $method = 'GET', $payload = null) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Mailchimp API key is not set');
        }

        $method = strtoupper($method);

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->api_key),
                'Content-Type'  => 'application/json'
            ],
            'timeout' => 30
        ];

        // Handle GET requests with payload as query parameters
        if ($payload !== null) {
            if ($method === 'GET') {
                $endpoint .= '?' . http_build_query($payload);
            } else {
                $args['body'] = json_encode($payload);
            }
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

    private function log($message, $data = null) {
        $log = "[Mailchimp API] $message";
        if ($data !== null) {
            $log .= " | Data: " . print_r($data, true);
        }
        error_log($log);
    }
}
