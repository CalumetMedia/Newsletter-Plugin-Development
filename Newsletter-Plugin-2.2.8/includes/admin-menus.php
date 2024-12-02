<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Add the main menu and submenus
 */
add_action('admin_menu', 'newsletter_setup_menu');
function newsletter_setup_menu() {
    // Main menu page with a fancy envelope icon
    add_menu_page(
        'Newsletter',                   // Page title
        'Newsletter v2.2.8',              // Menu title
        'manage_options',               // Capability
        'newsletter-settings',          // Menu slug
        'newsletter_all_settings_page', // Callback function
        'dashicons-email-alt2',         // Icon (Dashicons envelope icon)
        22                              // Position
    );

    // Submenu for Settings
    add_submenu_page(
        'newsletter-settings',
        'Settings',
        'Settings',
        'manage_options',
        'newsletter-settings',
        'newsletter_all_settings_page'
    );

    // Dynamically add submenu items for each newsletter for the "stories" page
    $newsletters = get_option('newsletter_list', []);
    foreach ($newsletters as $newsletter_slug => $newsletter_name) {
        // Create a unique menu slug
        $menu_slug = 'newsletter-stories-' . $newsletter_slug;

        // Add submenu page
        add_submenu_page(
            'newsletter-settings',
            esc_html($newsletter_name) . ' Stories', // Page title
            esc_html($newsletter_name),              // Menu title
            'manage_options',
            $menu_slug,
            function() use ($newsletter_slug) {        // Callback function to load the stories page
                newsletter_stories_page($newsletter_slug);
            }
        );
    }
}

/**
 * Callback functions for each page
 */
function newsletter_all_settings_page() {
    include NEWSLETTER_PLUGIN_DIR . 'admin/newsletter-settings-tabs.php';
}

function newsletter_stories_page($newsletter_slug) {
    include NEWSLETTER_PLUGIN_DIR . 'admin/newsletter-stories.php';
}
?>
