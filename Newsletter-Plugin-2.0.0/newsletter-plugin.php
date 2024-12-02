<?php
/**
 * Plugin Name: Newsletter Automator v2.0
 * Description: First iteration of base code that focuses on adding, deleting, and editing newsletters.
 * Version: 2.0
 * Author: Jon Stewart
 * Company: Calumet Media
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

define('NP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include necessary files
require_once NP_PLUGIN_PATH . 'includes/settings-page.php';
require_once NP_PLUGIN_PATH . 'includes/utilities.php';
require_once NP_PLUGIN_PATH . 'includes/post-selection.php';
require_once NP_PLUGIN_PATH . 'includes/mailchimp-integration.php';

// Register main menu and submenus for newsletters and settings
add_action('admin_menu', 'cnp_setup_menu_v20');

function cnp_setup_menu_v20() {
    // Main menu page
    add_menu_page(
        'Newsletter v2.0',                // Page title
        'Newsletter v2.0',                // Menu title
        'publish_posts',                  // Capability
        'custom-newsletters-v20',         // Menu slug (unique to version 2.0)
        'cnp_main_page_v20',              // Callback function
        'dashicons-email-alt',            // Icon
        20                                // Position - adjust this value as needed
    );

    // Submenu item for Settings
    add_submenu_page(
        'custom-newsletters-v20',         // Parent slug
        'Settings',                       // Page title
        'Settings',                       // Menu title
        'manage_options',                 // Capability
        'custom-newsletter-settings-v20', // Menu slug
        'cnp_settings_page_v20'           // Callback function (Updated)
    );

    // Load newsletter editor for each existing newsletter
    $newsletter_ids = get_option('newsletter_ids', []);
    foreach ($newsletter_ids as $newsletter_id) {
        $newsletter_name = get_option("newsletter_{$newsletter_id}_name", 'Unnamed Newsletter');
        add_submenu_page(
            'custom-newsletters-v20',                     // Parent slug
            $newsletter_name,                             // Page title
            $newsletter_name,                             // Menu title
            'publish_posts',                              // Capability
            "custom-newsletter-{$newsletter_id}-v20",     // Menu slug
            function() use ($newsletter_id) {            // Callback function
                cnp_newsletter_editor_page_v20($newsletter_id);
            }
        );
    }
}

// Main Dashboard Page for Newsletter v2.0
function cnp_main_page_v20() {
    echo '<div class="wrap"><h1>Newsletter v2.0 Dashboard</h1>';

    echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column column-title">Newsletter Name</th>
                    <th class="manage-column column-title">Send Days</th>
                    <th class="manage-column column-title">Send Time</th>
                    <th class="manage-column column-title">Categories</th>
                    <th class="manage-column column-title">Actions</th>
                    <th class="manage-column column-title">Patch Notes</th>
                </tr>
            </thead>
            <tbody>';

    $newsletter_ids = get_option('newsletter_ids', []);
    foreach ($newsletter_ids as $newsletter_id) {
        $name = get_option("newsletter_{$newsletter_id}_name", 'Unnamed');
        $send_days = implode(', ', get_option("newsletter_{$newsletter_id}_send_days", []));
        $send_time = get_option("newsletter_{$newsletter_id}_send_time", 'Not Set');
        $categories = get_option("newsletter_{$newsletter_id}_categories", []);
        $category_names = empty($categories) ? 'None' : implode(', ', array_map(function($id) {
            $cat = get_category($id);
            return $cat ? $cat->name : '';
        }, $categories));

        echo '<tr>
                <td>' . esc_html($name) . '</td>
                <td>' . esc_html($send_days) . '</td>
                <td>' . esc_html($send_time) . '</td>
                <td>' . esc_html($category_names) . '</td>
                <td>
                    <a href="' . esc_url(admin_url('admin.php?page=custom-newsletter-settings-v20#' . $newsletter_id . '-tab')) . '" class="button">Edit Settings</a>
                    <a href="' . esc_url(admin_url('admin.php?page=custom-newsletter-' . $newsletter_id . '-v20')) . '" class="button">Schedule</a>
                </td>
                <td>Version 2.0 - See latest updates below</td>
              </tr>';
    }

    echo '</tbody></table>';

    echo '<h2>Patch Notes - Version 2.0</h2>';
    echo '<p><strong>Newsletter Automator v2.0</strong><br>
          <em>Author:</em> Jon Stewart<br>
          <em>Publisher:</em> Calumet Media</p>';
    echo '<ul>
            <li><strong>Plugin Overhaul and Renaming</strong>: Converted the baseline plugin to "Newsletter Automator" for general use.</li>
            <li><strong>Modular Settings Structure</strong>: Added a Settings Page with tabs for Mailchimp configuration and newsletter management.</li>
            <li><strong>Ad Hoc Sending Option</strong>: Allows disabling of scheduled date/time for any newsletter.</li>
            <li><strong>Dynamic Newsletter Tabs</strong>: Users can add or delete newsletters as needed with dedicated tabs under Settings.</li>
            <li><strong>Dashboard Tab Summaries</strong>: A summary section displays each newsletter’s name, scheduled send days, send time, and categories.</li>
            <li><strong>Mailchimp Integration</strong>: Configurable Mailchimp API keys and list IDs directly on the settings page.</li>
          </ul>';

    echo '<h2>Past Patches</h2>';
    echo '<p>No past patches available yet. Future updates will be listed here.</p>';
    echo '</div>';
}
