<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Ensure the Mailchimp API library is loaded
function np_get_mailchimp_instance() {
    $api_key = get_option('mailchimp_api_key', '');
    if (!$api_key) {
        np_log_error('Mailchimp API key is missing in settings.');
        return new WP_Error('mailchimp_error', 'Mailchimp API key is not set.');
    }

    return new MailchimpAPI($api_key);  // Placeholder for Mailchimp SDK
}

// Function to create and send a Mailchimp campaign
function np_create_campaign($campaign_data) {
    $mailchimp = np_get_mailchimp_instance();
    if (is_wp_error($mailchimp)) {
        return $mailchimp;
    }

    $list_id = get_option('mailchimp_list_id', '');
    $from_name = get_option('mailchimp_from_name', 'Your Newsletter Name');
    $reply_to = get_option('mailchimp_reply_to', '');

    if (!$list_id || !$from_name || !$reply_to) {
        np_log_error('Mailchimp list ID, From Name, or Reply-to email is missing.');
        return new WP_Error('campaign_error', 'Mailchimp list ID, From Name, or Reply-to email is missing.');
    }

    $campaign_options = [
        'type' => 'regular',
        'recipients' => ['list_id' => $list_id],
        'settings' => [
            'subject_line' => $campaign_data['subject_line'],
            'title' => $campaign_data['title'],
            'from_name' => $from_name,
            'reply_to' => $reply_to,
            'template_id' => $campaign_data['template_id']  // optional
        ]
    ];

    $response = $mailchimp->createCampaign($campaign_options);
    if (isset($response['status']) && $response['status'] === 'error') {
        np_log_error('Failed to create Mailchimp campaign: ' . $response['detail']);
        return new WP_Error('mailchimp_campaign_error', 'Failed to create campaign: ' . $response['detail']);
    }

    $content = ['html' => $campaign_data['content_html']];
    $response = $mailchimp->setCampaignContent($response['id'], $content);

    if (isset($response['status']) && $response['status'] === 'error') {
        np_log_error('Failed to set Mailchimp campaign content: ' . $response['detail']);
        return new WP_Error('mailchimp_content_error', 'Failed to set campaign content: ' . $response['detail']);
    }

    return $response;
}

// Function to send a Mailchimp campaign
function np_send_campaign($campaign_id) {
    $mailchimp = np_get_mailchimp_instance();
    if (is_wp_error($mailchimp)) {
        return $mailchimp;
    }

    $response = $mailchimp->sendCampaign($campaign_id);
    if (isset($response['status']) && $response['status'] === 'error') {
        np_log_error('Failed to send Mailchimp campaign: ' . $response['detail']);
        return new WP_Error('mailchimp_send_error', 'Failed to send campaign: ' . $response['detail']);
    }

    return true;
}
