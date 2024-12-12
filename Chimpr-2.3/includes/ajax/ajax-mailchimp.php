<?php
// includes/ajax/ajax-mailchimp.php
if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler to Create Mailchimp Campaign
 */
function create_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';

    if (empty($newsletter_slug)) {
        wp_send_json_error('Newsletter slug is required.');
    }

    $subject_line = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);

    if (empty($blocks)) {
        wp_send_json_error('No content blocks found for newsletter.');
    }

    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        wp_send_json_error('Failed to generate newsletter content.');
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);

        if (is_wp_error($campaign)) {
            wp_send_json_error($campaign->get_error_message());
        }

        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            wp_send_json_error($content_result->get_error_message());
        }

        update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);

        wp_send_json_success([
            'campaign_id' => $campaign['id'],
            'web_id' => $campaign['web_id'],
            'message' => __('Campaign created successfully.', 'newsletter'),
        ]);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_mailchimp_campaign', 'create_mailchimp_campaign');

/**
 * AJAX Handler to Create and Schedule a New Mailchimp Campaign
 */
function create_and_schedule_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';

    if (empty($newsletter_slug) || empty($schedule_datetime)) {
        wp_send_json_error('Newsletter slug and schedule datetime are required.');
    }

    $site_timezone = wp_timezone();
    $local_dt = new DateTime($schedule_datetime, $site_timezone);
    $local_dt->setTimezone(new DateTimeZone('UTC'));
    $utc_schedule = $local_dt->format('Y-m-d\TH:i:s\Z');

    $subject_line  = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);

    if (empty($blocks)) {
        wp_send_json_error('No content blocks found for newsletter.');
    }

    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        wp_send_json_error('Failed to generate newsletter content.');
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
        if (is_wp_error($campaign)) {
            wp_send_json_error($campaign->get_error_message());
        }

        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            wp_send_json_error($content_result->get_error_message());
        }

        update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);

        $schedule_result = $mailchimp->schedule_campaign($campaign['id'], $utc_schedule);
        if (is_wp_error($schedule_result)) {
            wp_send_json_error($schedule_result->get_error_message());
        }

        wp_send_json_success([
            'message' => 'Campaign created and scheduled successfully.',
            'campaign_id' => $campaign['id']
        ]);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_and_schedule_campaign', 'create_and_schedule_campaign');

/**
 * AJAX Handler to Send Test Email
 */
function send_test_email() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';

    if (empty($test_email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $content = newsletter_generate_preview_content($newsletter_slug, $blocks);

        $campaign = $mailchimp->create_campaign($newsletter_slug, "Test - " . date('Y-m-d H:i:s'), "Test Campaign");
        if (is_wp_error($campaign)) {
            wp_send_json_error(['message' => $campaign->get_error_message()]);
        }

        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            wp_send_json_error(['message' => $content_result->get_error_message()]);
        }

        $test_result = $mailchimp->send_test_email($campaign['id'], $test_email);
        if (is_wp_error($test_result)) {
            wp_send_json_error(['message' => $test_result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Test email sent successfully']);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_send_test_email', 'send_test_email');

/**
 * AJAX Handler to Send the Campaign Immediately (SEND NOW)
 */
function send_now_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';

    if (empty($newsletter_slug)) {
        wp_send_json_error('Newsletter slug is required.');
    }

    $mailchimp = new Newsletter_Mailchimp_API();

    $subject_line  = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');
    $blocks        = get_option("newsletter_blocks_$newsletter_slug", []);

    if (empty($blocks)) {
        wp_send_json_error('No content blocks found for newsletter.');
    }

    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        wp_send_json_error('Failed to generate newsletter content.');
    }

    $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
    if (is_wp_error($campaign)) {
        wp_send_json_error($campaign->get_error_message());
    }

    $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
    if (is_wp_error($content_result)) {
        wp_send_json_error($content_result->get_error_message());
    }

    $send_result = $mailchimp->send_campaign($campaign['id']);
    if (is_wp_error($send_result)) {
        wp_send_json_error($send_result->get_error_message());
    }

    wp_send_json_success('Campaign created and sent successfully.');
}
add_action('wp_ajax_send_now_campaign', 'send_now_campaign');

add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete_campaign' && isset($_GET['campaign_id'])) {
        $api = new Newsletter_Mailchimp_API();
        $api->delete_campaign($_GET['campaign_id']);
        // Redirect or add an admin notice as needed
    }
});

