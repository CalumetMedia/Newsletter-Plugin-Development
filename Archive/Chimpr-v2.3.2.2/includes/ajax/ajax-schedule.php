<?php
// includes/ajax/ajax-schedule.php
if (!defined('ABSPATH')) exit;

function schedule_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    
    // Get the stored next scheduled timestamp
    $next_scheduled_timestamp = get_transient("next_scheduled_timestamp_$newsletter_slug");
    if (!$next_scheduled_timestamp) {
        // Fallback to calculating it
        $send_days = get_option("newsletter_send_days_$newsletter_slug", []);
        $send_time = get_option("newsletter_send_time_$newsletter_slug", '');
        
        error_log("Calculating schedule time from settings:");
        error_log("Send days: " . print_r($send_days, true));
        error_log("Send time: " . $send_time);
        
        if (empty($send_days) || empty($send_time)) {
            wp_send_json_error('Newsletter scheduling settings not configured');
            return;
        }

        $tz = wp_timezone();
        $now_local = new DateTime('now', $tz);
        $today_day = strtolower($now_local->format('l'));
        
        $send_today = DateTime::createFromFormat('Y-m-d H:i', $now_local->format('Y-m-d') . ' ' . $send_time, $tz);
        
        if (in_array($today_day, array_map('strtolower', $send_days)) && $send_today > $now_local) {
            $next_scheduled_timestamp = $send_today->getTimestamp();
        } else {
            for ($i = 1; $i <= 7; $i++) {
                $candidate = clone $now_local;
                $candidate->modify('+' . $i . ' days');
                $candidate_day = strtolower($candidate->format('l'));
                
                if (in_array($candidate_day, array_map('strtolower', $send_days))) {
                    $candidate_send_time = DateTime::createFromFormat('Y-m-d H:i', $candidate->format('Y-m-d') . ' ' . $send_time, $tz);
                    $next_scheduled_timestamp = $candidate_send_time->getTimestamp();
                    break;
                }
            }
        }
    }
    
    error_log("Using timestamp for scheduling: " . $next_scheduled_timestamp);
    error_log("Local time for scheduling: " . wp_date('Y-m-d H:i:s', $next_scheduled_timestamp));
    
    if (empty($next_scheduled_timestamp)) {
        wp_send_json_error('Could not determine next schedule time');
        return;
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();

        // Create campaign
        $subject_line = get_option("newsletter_subject_line_{$newsletter_slug}", '');
        $newsletter_list = get_option('newsletter_list', []);
        $newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : 'Newsletter';
        $campaign_name = $newsletter_name . ' - ' . wp_date('F j, Y', $next_scheduled_timestamp);

        $create_response = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
        if (is_wp_error($create_response)) {
            error_log("Campaign creation failed: " . $create_response->get_error_message());
            wp_send_json_error('Error creating campaign: ' . $create_response->get_error_message());
            return;
        }

        $campaign_id = $create_response['id'] ?? '';
        if (empty($campaign_id)) {
            error_log("No campaign ID returned from Mailchimp");
            wp_send_json_error('Campaign creation failed - no ID returned');
            return;
        }

        // Generate and set content
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        ob_start();
        include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php';
        $html_content = ob_get_clean();

        $set_content_response = $mailchimp->set_campaign_content($campaign_id, $html_content);
        if (is_wp_error($set_content_response)) {
            error_log("Setting content failed: " . $set_content_response->get_error_message());
            wp_send_json_error('Error setting campaign content: ' . $set_content_response->get_error_message());
            return;
        }

        // Schedule using the correct timestamp
        $schedule_response = $mailchimp->schedule_campaign($campaign_id, $next_scheduled_timestamp);
        if (is_wp_error($schedule_response)) {
            error_log("Scheduling failed: " . $schedule_response->get_error_message());
            wp_send_json_error('Error scheduling campaign: ' . $schedule_response->get_error_message());
            return;
        }

        $formatted_time = wp_date('F j, Y \a\t g:i a', $next_scheduled_timestamp);
        wp_send_json_success([
            'message' => "Campaign scheduled successfully for $formatted_time",
            'campaign_id' => $campaign_id
        ]);

    } catch (Exception $e) {
        error_log("Schedule exception: " . $e->getMessage());
        wp_send_json_error('Error processing schedule: ' . $e->getMessage());
    }
}

add_action('wp_ajax_schedule_mailchimp_campaign', 'schedule_mailchimp_campaign');