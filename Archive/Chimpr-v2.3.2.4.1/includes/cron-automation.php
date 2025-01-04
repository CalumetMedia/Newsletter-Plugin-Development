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
       // Existing cron setup
       if (!wp_next_scheduled('newsletter_automated_send')) {
           wp_schedule_event(time() + (10 * YEAR_IN_SECONDS), 'daily', 'newsletter_automated_send');
       }

       add_action('newsletter_automated_send', array($this, 'process_automated_send'));
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
       foreach ($newsletters as $newsletter_slug => $newsletter_name) {
           // Skip ad-hoc newsletters
           if (get_option("newsletter_is_ad_hoc_$newsletter_slug", 0)) {
               continue;
           }

           $send_days = get_option("newsletter_send_days_$newsletter_slug", []);
           $send_time = get_option("newsletter_send_time_$newsletter_slug", '');

           if (empty($send_days) || empty($send_time)) {
               continue;
           }

           // Check next send time for this newsletter
           $today = strtolower($now->format('l'));
           if (!in_array($today, array_map('strtolower', $send_days))) {
               continue;
           }

           $time_parts = explode(':', $send_time);
           if (count($time_parts) !== 2) {
               continue;
           }

           $send_dt = (clone $now)->setTime((int)$time_parts[0], (int)$time_parts[1], 0);
           
           if ($send_dt > $now && $send_dt <= $cutoff) {
               $candidate_newsletters[$newsletter_slug] = [
                   'name' => $newsletter_name,
                   'local_dt' => $send_dt,
                   'timestamp' => $send_dt->getTimestamp()
               ];
           }
       }

       $mailchimp = new Newsletter_Mailchimp_API();

       // Schedule campaigns for all candidate newsletters
       foreach ($candidate_newsletters as $newsletter_slug => $newsletter_data) {
           // Fire pre-send action for PDF generation
           do_action('pre_newsletter_automated_send', $newsletter_slug);
           
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
               do_action('post_newsletter_automated_send', $newsletter_slug);
               continue;
           }

           $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
           if (empty($content)) {
               error_log("Failed to generate content for $newsletter_slug. Skipping...");
               do_action('post_newsletter_automated_send', $newsletter_slug);
               continue;
           }

           // Check and handle existing campaign
           $stored_campaign_id = get_option("newsletter_mailchimp_campaign_id_$newsletter_slug", '');
           if (!empty($stored_campaign_id)) {
               $campaign_info = $mailchimp->get_campaign_info($stored_campaign_id);
               if (!is_wp_error($campaign_info)) {
                   if (isset($campaign_info['status']) && $campaign_info['status'] === 'scheduled') {
                       error_log("Campaign ID {$stored_campaign_id} for $newsletter_slug is still scheduled. Skipping duplicate.");
                       do_action('post_newsletter_automated_send', $newsletter_slug);
                       continue;
                   }
               } else {
                   error_log("Error checking campaign {$stored_campaign_id}: " . $campaign_info->get_error_message());
               }
           }

           // Create and schedule campaign
           $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
           if (is_wp_error($campaign)) {
               error_log("Failed to create campaign for $newsletter_slug: " . $campaign->get_error_message());
               do_action('post_newsletter_automated_send', $newsletter_slug);
               continue;
           }

           // Add PDF URL to content if PDF was generated  
           if (get_option("newsletter_opt_into_pdf_$newsletter_slug", false)) {
               $pdf_url = get_option("newsletter_current_pdf_url_$newsletter_slug");
               if ($pdf_url) {
                   $content .= sprintf(
                       '<div style="margin-top: 20px; text-align: center;">
                           <a href="%s" style="display: inline-block; padding: 10px 20px; background: #d65d23; color: #ffffff; text-decoration: none; border-radius: 3px;">
                               Download PDF Version
                           </a>
                       </div>',
                       esc_url($pdf_url)
                   );
               }
           }

           $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
           if (is_wp_error($content_result)) {
               error_log("Failed to set content for campaign {$campaign['id']}: " . $content_result->get_error_message());
               do_action('post_newsletter_automated_send', $newsletter_slug);
               continue;
           }

           $utc_schedule = $newsletter_time_utc->format('Y-m-d\TH:i:s\Z');
           $schedule_result = $mailchimp->schedule_campaign($campaign['id'], $utc_schedule);
           if (is_wp_error($schedule_result)) {
               error_log("Failed to schedule campaign {$campaign['id']} for $newsletter_slug: " . $schedule_result->get_error_message());
               do_action('post_newsletter_automated_send', $newsletter_slug);
               continue;
           }

           // Store the campaign ID for next run
           update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);

           error_log("Successfully scheduled campaign {$campaign['id']} for $newsletter_slug with name '$campaign_name' at local time {$newsletter_time_local->format('Y-m-d H:i:s T')} (UTC: $utc_schedule).");

           // Fire post-send action for cleanup
           do_action('post_newsletter_automated_send', $newsletter_slug);
       }
   }
}