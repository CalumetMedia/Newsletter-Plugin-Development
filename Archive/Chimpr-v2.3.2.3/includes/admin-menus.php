<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('admin_menu', 'newsletter_setup_menu');
function newsletter_setup_menu() {
    add_menu_page(
        'Newsletter',
        'Chimpr v2.3.2.3',
        'manage_options',
        'newsletter-settings',
        'newsletter_all_settings_page',
        'dashicons-email-alt',
        22
    );

    add_submenu_page(
        'newsletter-settings',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'newsletter-all-settings',
        'newsletter_all_settings_page'
    );

    add_submenu_page(
        'newsletter-settings',
        'Newsletters',
        '—————',
        'manage_options',
        'newsletter-divider-start',
        '__return_empty_string'
    );

    $newsletters = get_option('newsletter_list', []);
    foreach ($newsletters as $newsletter_slug => $newsletter_name) {
        add_submenu_page(
            'newsletter-settings',
            esc_html($newsletter_name) . ' Stories',
            esc_html($newsletter_name),
            'manage_options',
            'newsletter-stories-' . $newsletter_slug,
            function() use ($newsletter_slug) {
                newsletter_stories_page($newsletter_slug);
            }
        );
    }

    add_submenu_page(
        'newsletter-settings',
        '',
        '—————',
        'manage_options',
        'newsletter-divider-end',
        '__return_empty_string'
    );

    add_submenu_page(
        'newsletter-settings',
        'Campaigns',
        'Campaigns',
        'manage_options',
        'mailchimp-campaigns',
        'np_render_campaigns_page'
    );

    add_submenu_page(
        'newsletter-settings',
        'Templates',
        'Templates',
        'manage_options',
        'newsletter-templates',
        'newsletter_templates_page'
    );

    add_submenu_page(
        'newsletter-settings',
        'Mailchimp',
        'Mailchimp',
        'manage_options',
        'newsletter-mailchimp-settings',
        'newsletter_mailchimp_settings_page'
    );

    // Renamed from "Automation Cron Settings" to "Cron Settings"
    add_submenu_page(
        'newsletter-settings',
        'Cron Settings',
        'Cron Settings',
        'manage_options',
        'newsletter-cron-settings',
        array('Newsletter_Cron_Settings', 'render_settings_page')
    );

    // PDF Settings page re-added
    add_submenu_page(
        'newsletter-settings',
        'PDF Settings',
        'PDF Settings',
        'manage_options',
        'wr-pdf-settings',
        'wr_pdf_settings_page'
    );

    remove_submenu_page('newsletter-settings', 'newsletter-settings');
}

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
