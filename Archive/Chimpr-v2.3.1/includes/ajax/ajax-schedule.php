<?php
// includes/ajax/ajax-schedule.php
if (!defined('ABSPATH')) exit;

function schedule_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';
    $debug_timezone = isset($_POST['debug_timezone']) ? sanitize_text_field($_POST['debug_timezone']) : '';
    $debug_offset = isset($_POST['debug_offset']) ? intval($_POST['debug_offset']) : 0;

    error_log('Schedule Debug - Start');
    error_log('Client Timezone: ' . $debug_timezone);
    error_log('Client Offset: ' . $debug_offset);
    error_log('Input DateTime: ' . $schedule_datetime);
    error_log('WP Timezone: ' . wp_timezone_string());
    error_log('PHP Default Timezone: ' . date_default_timezone_get());

    $site_timezone = wp_timezone();

    try {
        // Convert input to local DateTime
        $dt = new DateTime($schedule_datetime, $site_timezone);
        error_log('Initial Parse in Site TZ: ' . $dt->format('Y-m-d H:i:s T'));

        $original_time = $dt->format('Y-m-d H:i:s T');

        // Convert to UTC for Mailchimp
        $dt->setTimezone(new DateTimeZone('UTC'));
        $schedule_datetime_utc = $dt->format('Y-m-d\TH:i:s\Z');
        error_log('UTC Time for Mailchimp: ' . $schedule_datetime_utc);

        $dt->setTimezone($site_timezone); // back to local for logging
        error_log('Final Local Time: ' . $dt->format('Y-m-d H:i:s T'));

        $mailchimp = new Newsletter_Mailchimp_API();

        // Always create a new campaign for scheduling
        $subject_line = get_option("newsletter_subject_line_{$newsletter_slug}", '');
        $newsletter_list = get_option('newsletter_list', []);
        $newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : 'Newsletter';
        $campaign_name = $newsletter_name . ' - ' . wp_date('F j, Y');

        $create_response = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
        if (is_wp_error($create_response)) {
            wp_send_json_error('Error creating campaign: ' . $create_response->get_error_message());
        }

        if (empty($create_response['id'])) {
            wp_send_json_error('No campaign ID returned from Mailchimp on creation.');
        }

        $campaign_id = $create_response['id'];

        // Generate campaign content
        ob_start();
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php';
        $html_content = ob_get_clean();

        // Set campaign content
        $set_content_response = $mailchimp->set_campaign_content($campaign_id, $html_content);
        if (is_wp_error($set_content_response)) {
            wp_send_json_error('Error setting campaign content: ' . $set_content_response->get_error_message());
        }
        if (isset($set_content_response['status']) && $set_content_response['status'] == 'error') {
            wp_send_json_error('Error setting campaign content: ' . (isset($set_content_response['detail']) ? $set_content_response['detail'] : 'Unknown error'));
        }

        // Schedule the campaign
        $response = $mailchimp->schedule_campaign($campaign_id, $schedule_datetime_utc);
        error_log('Mailchimp Response: ' . print_r($response, true));

        if (is_wp_error($response)) {
            wp_send_json_error('Error scheduling campaign: ' . $response->get_error_message());
        }
        if (isset($response['status']) && $response['status'] == 'error') {
            wp_send_json_error('Error scheduling campaign: ' . (isset($response['detail']) ? $response['detail'] : 'Unknown error'));
        }

        wp_send_json_success([
            'message' => 'Campaign scheduled successfully.',
            'debug' => [
                'original_time' => $original_time,
                'utc_time' => $schedule_datetime_utc,
                'final_local' => $dt->format('Y-m-d H:i:s T')
            ]
        ]);

    } catch (Exception $e) {
        error_log('Schedule Error: ' . $e->getMessage());
        wp_send_json_error('Error processing schedule time: ' . $e->getMessage());
    }
}

add_action('wp_ajax_schedule_mailchimp_campaign', 'schedule_mailchimp_campaign');
