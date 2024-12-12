<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Add the main menu and submenus
 */
add_action('admin_menu', 'newsletter_setup_menu');
function newsletter_setup_menu() {
    // Main menu page - defaults to the dashboard tab in newsletter_all_settings_page()
    add_menu_page(
        'Newsletter',               // Page title
        'Chimpr v2.3',          // Menu title
        'manage_options',           // Capability
        'newsletter-settings',      // Menu slug
        'newsletter_all_settings_page', // Callback
        'dashicons-email-alt',      // Icon
        22                          // Position
    );

    // Rename "Newsletters" to "Dashboard"
    add_submenu_page(
        'newsletter-settings',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'newsletter-all-settings',
        'newsletter_all_settings_page'
    );

    // Dynamically add submenu items for each newsletter for the "stories" page
    $newsletters = get_option('newsletter_list', []);
    foreach ($newsletters as $newsletter_slug => $newsletter_name) {
        add_submenu_page(
            'newsletter-settings',
            esc_html($newsletter_name) . ' Stories', // Page title
            esc_html($newsletter_name),              // Menu title
            'manage_options',
            'newsletter-stories-' . $newsletter_slug,
            function() use ($newsletter_slug) {
                newsletter_stories_page($newsletter_slug);
            }
        );
    }

    // Campaigns submenu
    add_submenu_page(
        'newsletter-settings',
        'Campaigns',
        'Campaigns',
        'manage_options',
        'mailchimp-campaigns',
        'np_render_campaigns_page'
    );

    // Templates submenu
    add_submenu_page(
        'newsletter-settings',
        'Templates',
        'Templates',
        'manage_options',
        'newsletter-templates',
        'newsletter_templates_page'
    );

    // Mailchimp submenu
    add_submenu_page(
        'newsletter-settings',
        'Mailchimp',
        'Mailchimp',
        'manage_options',
        'newsletter-mailchimp-settings',
        'newsletter_mailchimp_settings_page'
    );

    // Cron Settings submenu
    add_submenu_page(
        'newsletter-settings',
        'Automation Cron Settings',
        'Automation Cron Settings',
        'manage_options',
        'newsletter-cron-settings',
        array('Newsletter_Cron_Settings', 'render_settings_page')
    );

    // Remove the duplicate submenu that matches the main menu name
    remove_submenu_page('newsletter-settings', 'newsletter-settings');
}

/**
 * Callback functions for each page
 */

// The main settings page will now default to the dashboard tab
function newsletter_all_settings_page() {
    if (!isset($_GET['tab'])) {
        $_GET['tab'] = 'dashboard';
    }
    include NEWSLETTER_PLUGIN_DIR . 'admin/newsletter-settings-tabs.php';
}

function newsletter_stories_page($newsletter_slug) {
    include NEWSLETTER_PLUGIN_DIR . 'admin/newsletter-stories.php';
}

function newsletter_mailchimp_settings_page() {
    include NEWSLETTER_PLUGIN_DIR . 'admin/mailchimp-settings.php';
}

function newsletter_templates_page() {
    include NEWSLETTER_PLUGIN_DIR . 'admin/templates.php';
}
