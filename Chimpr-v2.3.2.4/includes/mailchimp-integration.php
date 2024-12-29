<?php

if (!defined('ABSPATH')) exit;

require_once NEWSLETTER_PLUGIN_DIR . 'includes/class-mailchimp-api.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/utilities.php';

function np_create_campaign($newsletter_slug) {
    $mailchimp = new Newsletter_Mailchimp_API();
 

    // Create campaign
    $campaign = $mailchimp->create_campaign($newsletter_slug);
    if (is_wp_error($campaign)) {
        np_log_error('Failed to create campaign: ' . $campaign->get_error_message());
        return $campaign;
    }


    // Get newsletter content
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $template_id = get_option("newsletter_template_id_$newsletter_slug", 'default');
    $content = newsletter_generate_preview_content($newsletter_slug, $template_id, $blocks);

    // Set campaign content
    $result = $mailchimp->set_campaign_content($campaign['id'], $content);
    if (is_wp_error($result)) {
        np_log_error('Failed to set campaign content: ' . $result->get_error_message());
        return $result;
    }

    return $campaign;
}

function np_send_campaign($campaign_id) {
    $mailchimp = new Newsletter_Mailchimp_API();
    return $mailchimp->send_campaign($campaign_id);
}