<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Register settings for the subpages
 */
add_action('admin_init', 'newsletter_register_settings');
function newsletter_register_settings() {
    register_setting('newsletter_add_newsletter_group', 'newsletter_list');
    register_setting('newsletter_add_newsletter_group', 'newsletter_templates'); // Added registration for templates
    register_setting('newsletter_add_newsletter_group', 'newsletter_default_template'); // If needed
    // Register other settings as needed
}
?>
