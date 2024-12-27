<?php
// includes/ajax-handlers.php
if (!defined('ABSPATH')) {
    exit;
}

// Include helper functions
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';

// Include each AJAX handler file
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-load-block-posts.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-generate-preview.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-save-blocks.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-schedule.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-mailchimp.php';

function schedule_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : 0;
    
    // Add more detailed logging
    error_log("Schedule attempt - Full POST data: " . print_r($_POST, true));
    error_log("Schedule attempt - Raw timestamp: " . $_POST['timestamp']);
    error_log("Schedule attempt - Parsed timestamp: $timestamp");
    error_log("Schedule attempt - Newsletter slug: $newsletter_slug");
    
    // Validate newsletter slug
    if (empty($newsletter_slug)) {
        wp_send_json_error('Newsletter slug is required');
        return;
    }

    // Validate timestamp more strictly
    if (empty($timestamp) || $timestamp <= 0) {
        wp_send_json_error('Invalid timestamp provided: ' . $timestamp);
        return;
    }

    try {
        // Validate timestamp is in the future
        $current_time = current_time('timestamp');
        if ($timestamp <= $current_time) {
            error_log("Schedule failed - Timestamp is in the past");
            wp_send_json_error('Schedule time must be in the future');
            return;
        }

        // Create DateTime object in site's timezone
        $site_timezone = wp_timezone();
        $dt = new DateTime("@$timestamp");
        $dt->setTimezone($site_timezone);
        
        // Convert to UTC for Mailchimp
        $dt->setTimezone(new DateTimeZone('UTC'));
        $schedule_datetime_utc = $dt->format('Y-m-d\TH:i:s\Z');

        error_log("Processing schedule - Local time: " . wp_date('Y-m-d H:i:s', $timestamp));
        error_log("Processing schedule - UTC time: $schedule_datetime_utc");

        $mailchimp = new Newsletter_Mailchimp_API();

        // Create campaign
        $subject_line = get_option("newsletter_subject_line_{$newsletter_slug}", '');
        $newsletter_list = get_option('newsletter_list', []);
        $newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : 'Newsletter';
        $campaign_name = $newsletter_name . ' - ' . wp_date('F j, Y', $timestamp);

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

        // Schedule the campaign
        $schedule_response = $mailchimp->schedule_campaign($campaign_id, $schedule_datetime_utc);
        if (is_wp_error($schedule_response)) {
            error_log("Scheduling failed: " . $schedule_response->get_error_message());
            wp_send_json_error('Error scheduling campaign: ' . $schedule_response->get_error_message());
            return;
        }

        $formatted_time = wp_date('F j, Y \a\t g:i a', $timestamp);
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
