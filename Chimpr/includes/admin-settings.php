<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Register settings for the subpages
 */
add_action('admin_init', 'newsletter_register_settings');
function newsletter_register_settings() {
    register_setting('newsletter_add_newsletter_group', 'newsletter_list');
    register_setting('newsletter_add_newsletter_group', 'newsletter_templates');
    register_setting('newsletter_add_newsletter_group', 'newsletter_default_template');
}

function save_newsletter_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $newsletter_slug = sanitize_text_field($_POST['newsletter_slug'] ?? '');
    
    $send_time = sanitize_text_field($_POST['send_time'] ?? '');
    error_log("Saving send time for $newsletter_slug: " . $send_time);
    update_option("newsletter_send_time_$newsletter_slug", $send_time);
    
    $send_days = array_map('sanitize_text_field', $_POST['send_days'] ?? []);
    error_log("Saving send days for $newsletter_slug: " . print_r($send_days, true));
    update_option("newsletter_send_days_$newsletter_slug", $send_days);
}
?>