<?php
if (!defined('ABSPATH')) exit;

class Newsletter_Cron_Automation {
    const LOOKAHEAD_MINUTES = 60; // 1 hour

    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Ensure the event is registered with WP's cron system so `wp cron event run` can work
        // Schedule it far in the future so it never runs automatically by normal WP cron
        if (!wp_next_scheduled('newsletter_automated_send')) {
            wp_schedule_event(time() + (10 * YEAR_IN_SECONDS), 'daily', 'newsletter_automated_send');
        }

        add_action('newsletter_automated_send', array($this, 'process_automated_send'));
    }

    private function get_next_scheduled_timestamp($slug) {
        $send_days = get_option("newsletter_send_days_$slug", []);
        $send_time = get_option("newsletter_send_time_$slug", '');
        if (empty($send_days) || empty($send_time)) {
            return null;
        }

        $tz = wp_timezone();
        $now_local = new DateTime('now', $tz);
        $today_day = strtolower($now_local->format('l'));

        $send_today = DateTime::createFromFormat('Y-m-d H:i', $now_local->format('Y-m-d') . ' ' . $send_time, $tz);

        if (in_array($today_day, array_map('strtolower', $send_days)) && $send_today > $now_local) {
            return $send_today->getTimestamp();
        }

        // Otherwise, find the next valid day
        for ($i = 1; $i <= 7; $i++) {
            $candidate = clone $now_local;
            $candidate->modify('+' . $i . ' days');
            $candidate_day = strtolower($candidate->format('l'));

            if (in_array($candidate_day, array_map('strtolower', $send_days))) {
                $candidate_send_time = DateTime::createFromFormat('Y-m-d H:i', $candidate->format('Y-m-d') . ' ' . $send_time, $tz);
                return $candidate_send_time->getTimestamp();
            }
        }

        return null;
    }

    public function process_automated_send() {
        $lookahead_minutes = self::LOOKAHEAD_MINUTES;
        $tz = wp_timezone();
        $now = new DateTime('now', $tz);
        $cutoff = (clone $now)->modify("+{$lookahead_minutes} minutes");

        $newsletters = get_option('newsletter_list', []);
        if (empty($newsletters)) {
            error_log("No newsletters found. Aborting automated send.");
            return;
        }

        $candidate_newsletters = [];
        foreach ($newsletters as $slug => $name) {
            $is_ad_hoc = get_option("newsletter_is_ad_hoc_{$slug}", 0);
            if ($is_ad_hoc) {
                continue; // skip ad-hoc
            }

            $next_scheduled_timestamp = $this->get_next_scheduled_timestamp($slug);
            if (!$next_scheduled_timestamp) {
                continue;
            }

            $next_run_local = (new DateTime('@' . $next_scheduled_timestamp, $tz))->setTimezone($tz);

            if ($next_run_local > $now && $next_run_local <= $cutoff) {
                $candidate_newsletters[$slug] = [
                    'name'      => $name,
                    'timestamp' => $next_scheduled_timestamp,
                    'local_dt'  => $next_run_local
                ];
            }
        }

        if (empty($candidate_newsletters)) {
            error_log("No newsletters scheduled in the next {$lookahead_minutes} minutes. Aborting automated send.");
            return;
        }

        $mailchimp = new Newsletter_Mailchimp_API();

        // Schedule campaigns for all candidate newsletters
        foreach ($candidate_newsletters as $newsletter_slug => $newsletter_data) {
            $newsletter_time_local = $newsletter_data['local_dt'];
            $newsletter_name = $newsletter_data['name'];
            $next_scheduled_timestamp = $newsletter_data['timestamp'];

            $newsletter_time_utc = clone $newsletter_time_local;
            $newsletter_time_utc->setTimezone(new DateTimeZone('UTC'));

            $send_date_display = wp_date('F j, Y', $next_scheduled_timestamp);
            $campaign_name = $newsletter_name . ' - ' . $send_date_display;
            $subject_line  = get_option("newsletter_subject_line_$newsletter_slug", "Newsletter ($newsletter_slug)");

            $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
            if (empty($blocks)) {
                error_log("No blocks found for $newsletter_slug. Skipping...");
                continue;
            }

            $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
            if (empty($content)) {
                error_log("Failed to generate content for $newsletter_slug. Skipping...");
                continue;
            }

            // Check previously scheduled campaign by ID
            $stored_campaign_id = get_option("newsletter_mailchimp_campaign_id_$newsletter_slug", '');
            if (!empty($stored_campaign_id)) {
                $campaign_info = $mailchimp->get_campaign_info($stored_campaign_id);
                if (!is_wp_error($campaign_info)) {
                    if (isset($campaign_info['status']) && $campaign_info['status'] === 'scheduled') {
                        error_log("Campaign ID {$stored_campaign_id} for $newsletter_slug is still scheduled. Skipping duplicate.");
                        continue;
                    }
                } else {
                    error_log("Error checking campaign {$stored_campaign_id}: " . $campaign_info->get_error_message());
                }
            }

            // Create and schedule new campaign
            $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
            if (is_wp_error($campaign)) {
                error_log("Failed to create campaign for $newsletter_slug: " . $campaign->get_error_message());
                continue;
            }

            $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
            if (is_wp_error($content_result)) {
                error_log("Failed to set content for campaign {$campaign['id']}: " . $content_result->get_error_message());
                continue;
            }

            $utc_schedule = $newsletter_time_utc->format('Y-m-d\TH:i:s\Z');
            $schedule_result = $mailchimp->schedule_campaign($campaign['id'], $utc_schedule);
            if (is_wp_error($schedule_result)) {
                error_log("Failed to schedule campaign {$campaign['id']} for $newsletter_slug: " . $schedule_result->get_error_message());
                continue;
            }

            // Store the campaign ID for next run
            update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);

            error_log("Successfully scheduled campaign {$campaign['id']} for $newsletter_slug with name '$campaign_name' at local time {$newsletter_time_local->format('Y-m-d H:i:s T')} (UTC: $utc_schedule).");
        }
    }
}
