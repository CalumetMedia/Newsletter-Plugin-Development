<?php
// includes/ajax/ajax-schedule.php
if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler to Schedule Mailchimp Campaign with a "Newsletter Name - Date" format.
 */
function schedule_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';

    // Get the newsletter list and the friendly name of the current newsletter
    $newsletter_list = get_option('newsletter_list', []);
    $newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : 'Newsletter';

    // First, determine the final schedule date/time before creating the campaign
    $site_timezone = wp_timezone();
    if (empty($schedule_datetime)) {
        // Use the default schedule
        $send_days = get_option("newsletter_send_days_$newsletter_slug", []);
        $send_time = get_option("newsletter_send_time_$newsletter_slug", '');

        if (empty($send_days) || empty($send_time)) {
            wp_send_json_error('No default schedule found. Please set a schedule in the newsletter settings.');
            return;
        }

        $current_time = current_time('timestamp');
        $current_hour = date('H:i', $current_time);
        $schedule_time = date('H:i', strtotime($send_time));

        $next_date = null;
        for ($i = 0; $i < 7; $i++) {
            $check_timestamp = strtotime("+$i days", $current_time);
            $check_day = strtolower(date('l', $check_timestamp));

            if (in_array($check_day, array_map('strtolower', $send_days), true)) {
                if ($i === 0 && $current_hour >= $schedule_time) {
                    continue;
                }

                $date_str = date('Y-m-d', $check_timestamp) . ' ' . $schedule_time;
                $local_dt = DateTime::createFromFormat('Y-m-d H:i', $date_str, $site_timezone);
                if ($local_dt && $local_dt->getTimestamp() > time()) {
                    $next_date = $local_dt->getTimestamp();
                    break;
                }
            }
        }

        if (!$next_date) {
            wp_send_json_error('Could not determine the next scheduled date from defaults.');
            return;
        }

        if ($next_date < (time() + 300)) {
            wp_send_json_error('The computed schedule time is too soon. Please adjust your default schedule.');
            return;
        }

        $utc_dt = new DateTime('@' . $next_date);
        $utc_dt->setTimezone(new DateTimeZone('UTC'));
        $schedule_datetime = $utc_dt->format('Y-m-d\TH:i:s\Z');
    } else {
        try {
            $local_dt = new DateTime($schedule_datetime, $site_timezone);
            
            if ($local_dt->getTimestamp() < (time() + 300)) {
                wp_send_json_error('Selected schedule time must be at least 5 minutes in the future.');
                return;
            }

            $local_dt->setTimezone(new DateTimeZone('UTC'));
            $schedule_datetime = $local_dt->format('Y-m-d\TH:i:s\Z');
        } catch (Exception $e) {
            wp_send_json_error('Invalid schedule date/time format provided.');
            return;
        }
    }

    // Convert UTC schedule time back to local for display
    $utc_schedule = new DateTime($schedule_datetime, new DateTimeZone('UTC'));
    $utc_schedule->setTimezone($site_timezone);
    $formatted_date = $utc_schedule->format('M j Y');

    // Campaign name format: "Newsletter Name - Dec 2 2024"
    $campaign_name = $newsletter_name . ' - ' . $formatted_date;

    // Get subject line or use default
    $default_title = 'Newsletter ' . date('Y-m-d H:i:s');
    $subject_line = get_option("newsletter_subject_line_$newsletter_slug", $default_title);

    // Generate content
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $html_content = newsletter_generate_preview_content($newsletter_slug, $blocks);

    $mailchimp = new Newsletter_Mailchimp_API();

    // Create campaign
    $create_result = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
    if (is_wp_error($create_result)) {
        wp_send_json_error('Error creating campaign: ' . $create_result->get_error_message());
        return;
    }

    if (empty($create_result['id'])) {
        wp_send_json_error('Error: Created campaign does not have an ID.');
        return;
    }

    $campaign_id = $create_result['id'];
    update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign_id);

    // Set content
    $content_result = $mailchimp->set_campaign_content($campaign_id, $html_content);
    if (is_wp_error($content_result)) {
        wp_send_json_error('Error setting campaign content: ' . $content_result->get_error_message());
        return;
    }

    // Schedule
    $schedule_result = $mailchimp->schedule_campaign($campaign_id, $schedule_datetime);
    if (is_wp_error($schedule_result)) {
        error_log('Mailchimp Schedule Error: ' . print_r($schedule_result, true));
        wp_send_json_error('Mailchimp Error: ' . $schedule_result->get_error_message() . ' Debug: ' . json_encode($schedule_result->get_error_data()));
        return;
    }

    // Try to get error details if available
    if (isset($schedule_result['detail'])) {
        error_log('Mailchimp API Error Detail: ' . print_r($schedule_result, true));
        wp_send_json_error('Mailchimp Error: ' . $schedule_result['detail']);
        return;
    }

    wp_send_json_success([
        'message' => 'Campaign scheduled successfully.',
        'scheduled_time' => $schedule_datetime,
        'campaign_name' => $campaign_name
    ]);
}

add_action('wp_ajax_schedule_mailchimp_campaign', 'schedule_mailchimp_campaign');