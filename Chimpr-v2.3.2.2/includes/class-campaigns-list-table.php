<?php
// Make sure this code runs within WP Admin context, for instance in a plugin page callback.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once 'includes/class-mailchimp-api.php'; // Adjust path as needed

$mailchimp_api = new Newsletter_Mailchimp_API();
$campaigns = $mailchimp_api->get_campaigns();

if (is_wp_error($campaigns)) {
    echo '<div class="error"><p>Error fetching campaigns: ' . esc_html($campaigns->get_error_message()) . '</p></div>';
    return;
}

// Debug: print the entire campaigns array
echo '<pre>';
print_r($campaigns);
echo '</pre>';

$draft_scheduled_campaigns = [];
$sent_campaigns = [];

foreach ($campaigns as $c) {
    // Debug log each campaign to the error log
    error_log("Campaign Data: " . print_r($c, true));

    // Possible statuses: 'save', 'schedule', 'sending', 'sent'
    if (in_array($c['status'], ['save', 'schedule'])) {
        $draft_scheduled_campaigns[] = $c;
    } elseif ($c['status'] === 'sent') {
        $sent_campaigns[] = $c;
    }
}

// Function to get a styled status label
function get_status_label($status) {
    switch ($status) {
        case 'save': // draft
            return '<span style="background:yellow;color:black;padding:2px 4px;border-radius:3px;">Draft</span>';
        case 'schedule': // scheduled
            return '<span style="background:green;color:white;padding:2px 4px;border-radius:3px;">Scheduled</span>';
        default:
            return esc_html(ucfirst($status));
    }
}

// Output the Draft/Scheduled table
if (!empty($draft_scheduled_campaigns)) {
    echo '<h2>Draft & Scheduled Newsletters</h2>';
    echo '<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width:100px;">Status</th>';
    echo '<th>Title</th>';
    echo '<th>Subject</th>';
    echo '<th>Created At</th>';
    echo '<th>Scheduled Time</th>';
    echo '<th>From Name</th>';
    echo '<th>Reply To</th>';
    echo '<th>List ID</th>';
    echo '<th>Archive URL</th>';
    echo '<th>Campaign ID</th>';
    echo '<th style="width:160px;">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $tz_string = get_option('timezone_string');
    if (empty($tz_string)) {
        $tz_string = 'UTC';
    }

    foreach ($draft_scheduled_campaigns as $c) {
        $status_label = get_status_label($c['status']);

        $create_time = new DateTime($c['create_time'], new DateTimeZone('UTC'));
        $create_time->setTimezone(new DateTimeZone($tz_string));

        $scheduled_time = '-';
        if (!empty($c['settings']['send_time'])) {
            $send_time = new DateTime($c['settings']['send_time'], new DateTimeZone('UTC'));
            $send_time->setTimezone(new DateTimeZone($tz_string));
            $scheduled_time = $send_time->format('F j, Y g:i a');
        }

        $title        = !empty($c['settings']['title']) ? $c['settings']['title'] : '';
        $subject      = !empty($c['settings']['subject_line']) ? $c['settings']['subject_line'] : '';
        $from_name    = !empty($c['settings']['from_name']) ? $c['settings']['from_name'] : '';
        $reply_to     = !empty($c['settings']['reply_to']) ? $c['settings']['reply_to'] : '';
        $list_id      = !empty($c['recipients']['list_id']) ? $c['recipients']['list_id'] : '';
        $archive_url  = !empty($c['archive_url']) ? $c['archive_url'] : '';
        $campaign_id  = isset($c['id']) ? $c['id'] : '';

        $datacenter = $mailchimp_api->get_datacenter();
        $edit_url = 'https://' . $datacenter . '.admin.mailchimp.com/campaigns/edit?id=' . urlencode($c['web_id']);
        $delete_url = add_query_arg([
            'page'        => $_REQUEST['page'],
            'action'      => 'delete_campaign',
            'campaign_id' => $campaign_id
        ], admin_url('admin.php'));

        echo '<tr>';
        echo '<td>' . $status_label . '</td>';
        echo '<td>' . esc_html($title) . '</td>';
        echo '<td>' . esc_html($subject) . '</td>';
        echo '<td>' . esc_html($create_time->format('F j, Y g:i a')) . '</td>';
        echo '<td>' . esc_html($scheduled_time) . '</td>';
        echo '<td>' . esc_html($from_name) . '</td>';
        echo '<td>' . esc_html($reply_to) . '</td>';
        echo '<td>' . esc_html($list_id) . '</td>';
        echo '<td><a href="' . esc_url($archive_url) . '" target="_blank">View</a></td>';
        echo '<td>' . esc_html($campaign_id) . '</td>';
        echo '<td>';
        echo '<a class="button button-primary" href="' . esc_url($edit_url) . '" target="_blank">Edit</a> ';
        echo '<a class="button button-secondary" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Are you sure?\')">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

// Output the Sent Newsletters table
if (!empty($sent_campaigns)) {
    echo '<h2>Sent Newsletters</h2>';
    echo '<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Status</th>';
    echo '<th>Title</th>';
    echo '<th>Subject</th>';
    echo '<th>Created At</th>';
    echo '<th>Send Time</th>';
    echo '<th>From Name</th>';
    echo '<th>Reply To</th>';
    echo '<th>List ID</th>';
    echo '<th>Emails Sent</th>';
    echo '<th>Archive URL</th>';
    echo '<th>Report Summary</th>';
    echo '<th>Campaign ID</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($sent_campaigns as $c) {
        $status_label = esc_html(ucfirst($c['status']));

        $create_time = new DateTime($c['create_time'], new DateTimeZone('UTC'));
        $create_time->setTimezone(new DateTimeZone($tz_string));

        $send_time = '-';
        if (!empty($c['send_time'])) {
            $st = new DateTime($c['send_time'], new DateTimeZone('UTC'));
            $st->setTimezone(new DateTimeZone($tz_string));
            $send_time = $st->format('F j, Y g:i a');
        }

        $title        = !empty($c['settings']['title']) ? $c['settings']['title'] : '';
        $subject      = !empty($c['settings']['subject_line']) ? $c['settings']['subject_line'] : '';
        $from_name    = !empty($c['settings']['from_name']) ? $c['settings']['from_name'] : '';
        $reply_to     = !empty($c['settings']['reply_to']) ? $c['settings']['reply_to'] : '';
        $list_id      = !empty($c['recipients']['list_id']) ? $c['recipients']['list_id'] : '';
        $archive_url  = !empty($c['archive_url']) ? $c['archive_url'] : '';
        $emails_sent  = !empty($c['emails_sent']) ? $c['emails_sent'] : 0;
        $campaign_id  = isset($c['id']) ? $c['id'] : '';

        $report_summary = '';
        if (!empty($c['report_summary'])) {
            $opens = isset($c['report_summary']['opens']) ? $c['report_summary']['opens'] : 0;
            $unique_opens = isset($c['report_summary']['unique_opens']) ? $c['report_summary']['unique_opens'] : 0;
            $open_rate = isset($c['report_summary']['open_rate']) ? ($c['report_summary']['open_rate'] * 100) . '%' : 'N/A';
            $clicks = isset($c['report_summary']['clicks']) ? $c['report_summary']['clicks'] : 0;
            $subscriber_clicks = isset($c['report_summary']['subscriber_clicks']) ? $c['report_summary']['subscriber_clicks'] : 0;
            $click_rate = isset($c['report_summary']['click_rate']) ? ($c['report_summary']['click_rate'] * 100) . '%' : 'N/A';

            $report_summary = "Opens: $opens ($unique_opens unique), Open Rate: $open_rate, Clicks: $clicks ($subscriber_clicks unique), Click Rate: $click_rate";
        }

        echo '<tr>';
        echo '<td>' . $status_label . '</td>';
        echo '<td>' . esc_html($title) . '</td>';
        echo '<td>' . esc_html($subject) . '</td>';
        echo '<td>' . esc_html($create_time->format('F j, Y g:i a')) . '</td>';
        echo '<td>' . esc_html($send_time) . '</td>';
        echo '<td>' . esc_html($from_name) . '</td>';
        echo '<td>' . esc_html($reply_to) . '</td>';
        echo '<td>' . esc_html($list_id) . '</td>';
        echo '<td>' . intval($emails_sent) . '</td>';
        echo '<td><a href="' . esc_url($archive_url) . '" target="_blank">View</a></td>';
        echo '<td>' . esc_html($report_summary) . '</td>';
        echo '<td>' . esc_html($campaign_id) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

if (empty($draft_scheduled_campaigns) && empty($sent_campaigns)) {
    echo '<p>No campaigns found.</p>';
}
