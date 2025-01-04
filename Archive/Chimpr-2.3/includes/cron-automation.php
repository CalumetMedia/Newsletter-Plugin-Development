<?php
if (!defined('ABSPATH')) exit;

class Newsletter_Cron_Automation {
    public function __construct() {
        add_action('init', array($this, 'schedule_events'));
        add_action('newsletter_automated_send', array($this, 'process_automated_send'));
    }

    public function schedule_events() {
        $use_wp_cron = get_option('newsletter_use_wp_cron', false);

        // If WP-Cron is disabled, clear the scheduled event if it exists
        $timestamp = wp_next_scheduled('newsletter_automated_send');
        if ($timestamp && !$use_wp_cron) {
            wp_unschedule_event($timestamp, 'newsletter_automated_send');
        }

        // If WP-Cron is enabled, schedule the event if not already scheduled (hourly)
        if ($use_wp_cron && !wp_next_scheduled('newsletter_automated_send')) {
            wp_schedule_event(time(), 'hourly', 'newsletter_automated_send');
        }
    }

    public function process_automated_send() {
        // Check if a campaign is scheduled in the next 75 minutes
        $mailchimp = new Newsletter_Mailchimp_API();
        $upcoming_campaigns = $mailchimp->get_upcoming_scheduled_campaigns(75);

        if (!empty($upcoming_campaigns)) {
            // If there is already a newsletter scheduled, do nothing
            return;
        }

        // If none scheduled, check newsletters for a send time that falls now
        $newsletters = get_option('newsletter_list', []);
        $current_time = current_time('H:i');
        $current_day = strtolower(current_time('l'));

        // Find a newsletter that should be scheduled now
        foreach ($newsletters as $newsletter_slug => $newsletter_name) {
            // Check manual schedule override first
            $use_manual_schedule = get_option("newsletter_use_manual_schedule_$newsletter_slug", '0');
            if ($use_manual_schedule === '1') {
                $manual_date = get_option("newsletter_manual_schedule_date_$newsletter_slug", '');
                $manual_time = get_option("newsletter_manual_schedule_time_$newsletter_slug", '');
                if ($manual_date === date('Y-m-d') && $manual_time === $current_time) {
                    // Instead of sending now, schedule it right away for current time
                    $this->schedule_newsletter($newsletter_slug, current_time('timestamp'));
                    // Clear manual schedule after scheduling
                    delete_option("newsletter_manual_schedule_date_$newsletter_slug");
                    delete_option("newsletter_manual_schedule_time_$newsletter_slug");
                    update_option("newsletter_use_manual_schedule_$newsletter_slug", '0');
                    return; 
                }
                continue;
            }

            // Skip if ad-hoc
            if (get_option("newsletter_is_ad_hoc_$newsletter_slug", 0)) {
                continue;
            }

            $send_time = get_option("newsletter_send_time_$newsletter_slug", '');
            $send_days = get_option("newsletter_send_days_$newsletter_slug", []);
            
            // If today's a valid send day and the time matches
            if ($send_time === $current_time && in_array($current_day, array_map('strtolower', $send_days))) {
                // Schedule the newsletter
                // We'll schedule it right now for immediate send via Mailchimp scheduling API
                $this->schedule_newsletter($newsletter_slug, current_time('timestamp'));
                return;
            }
        }
    }

    private function schedule_newsletter($newsletter_slug, $timestamp) {
        $mailchimp = new Newsletter_Mailchimp_API();
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        
        if (empty($blocks)) {
            error_log("No content blocks found for newsletter: $newsletter_slug");
            return;
        }

        // Generate newsletter content
        $content = newsletter_generate_preview_content($newsletter_slug, $blocks);

        // Fetch subject line and campaign name from settings
        $subject_line = get_option("newsletter_subject_line_$newsletter_slug", '');
        $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');

        try {
            $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
            if (!is_wp_error($campaign)) {
                $mailchimp->set_campaign_content($campaign['id'], $content);

                // Schedule  campaign to start now (or at the current time)
                // If you want it to start slightly in future, adjust the timestamp
                // For immediate scheduling, use current time or a few minutes from now
                $schedule_time = gmdate('Y-m-d H:i:s', $timestamp); 
                $mailchimp->schedule_campaign($campaign['id'], $schedule_time);

                error_log("Newsletter $newsletter_slug scheduled successfully for $schedule_time");
            } else {
                error_log("Failed to create campaign for newsletter $newsletter_slug: " . $campaign->get_error_message());
            }
        } catch (Exception $e) {
            error_log("Failed to schedule newsletter $newsletter_slug: " . $e->getMessage());
        }
    }
}
