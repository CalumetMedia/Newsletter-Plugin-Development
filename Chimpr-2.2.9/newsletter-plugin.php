<?php
/**
 * Plugin Name:       Chimpr v2.2.9
 * Description:       Customing in-block options, renaming advertising to HTML block. 
 * Version:           2.2.9
 * Author:            Jon Stewart
 * Text Domain:       chimpr-newsletter
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Define Plugin Constants
 */
define('NEWSLETTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEWSLETTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEWSLETTER_PLUGIN_VERSION', '2.2.9');

/**
 * Include Necessary Files
 */
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-menus.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-settings.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-scripts.php'; // Updated path
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax-handlers.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/utilities.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/mailchimp-integration.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/class-mailchimp-api.php';
$mailchimp = new Newsletter_Mailchimp_API();

// Include helpers if needed
// include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php'; // Already included in ajax-handlers.php and newsletter-stories.php

/**
 * Activation Hook
 */
function newsletter_plugin_activate() {
    // Initialize empty newsletter list if not already set
    if (!get_option('newsletter_list')) {
        update_option('newsletter_list', []);
        error_log("Activation Hook: Empty newsletter list initialized.");
    }

    // Initialize empty templates list if not already set
    if (!get_option('newsletter_templates')) {
        update_option('newsletter_templates', []);
        error_log("Activation Hook: Empty templates list initialized.");
    }
}
register_activation_hook(__FILE__, 'newsletter_plugin_activate');

/**
 * Deactivation Hook
 */
function newsletter_plugin_deactivate() {
    // Actions to perform upon plugin deactivation
    // For example, clean up temporary data or reset certain options
    error_log("Deactivation Hook: Plugin deactivated.");
}
register_deactivation_hook(__FILE__, 'newsletter_plugin_deactivate');

/**
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'newsletter_plugin_uninstall');

function newsletter_plugin_uninstall() {
    // Actions to perform upon plugin uninstallation
    // For example, delete all plugin data from the database
    global $wpdb;

    // Delete newsletter blocks based on slugs
    $newsletter_blocks = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_blocks_%'");
    foreach ($newsletter_blocks as $block) {
        delete_option($block->option_name);
        error_log("Uninstall Hook: Deleted option '{$block->option_name}'.");
    }

    // Delete newsletter templates based on slugs
    $newsletter_templates = get_option('newsletter_templates', []);
    foreach ($newsletter_templates as $template_id => $template) {
        delete_option("newsletter_template_content_{$template_id}");
        error_log("Uninstall Hook: Deleted template content for '{$template_id}'.");
    }

    // Delete newsletter categories based on slugs
    $newsletter_categories = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_categories_%'");
    foreach ($newsletter_categories as $category) {
        delete_option($category->option_name);
        error_log("Uninstall Hook: Deleted option '{$category->option_name}'.");
    }

    // Delete newsletter_list and newsletter_default_template options
    delete_option('newsletter_list');
    delete_option('newsletter_default_template');
    delete_option('newsletter_templates');
    error_log("Uninstall Hook: Deleted 'newsletter_list', 'newsletter_default_template', and 'newsletter_templates' options.");

    // Optionally, delete other plugin-related data
}

/**
 * Initialize the Plugin
 */
function newsletter_plugin_init() {
    // Load plugin text domain for translations
    load_plugin_textdomain('newsletter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    error_log("Plugin Init: Text domain loaded.");
}
add_action('plugins_loaded', 'newsletter_plugin_init');

/**
 * Admin Notices (Optional)
 * Display admin notices for plugin updates or errors
 */
function newsletter_admin_notices() {
    // Example: Display a notice upon successful block save
    if (isset($_GET['newsletter_saved']) && $_GET['newsletter_saved'] == 'true') {
        echo '<div class="notice notice-success is-dismissible">
                <p>' . esc_html__('Newsletter blocks have been successfully saved.', 'newsletter') . '</p>
             </div>';
    }

    // Display a notice for invalid slugs
    if (isset($_GET['message']) && $_GET['message'] == 'invalid_slug') {
        echo '<div class="notice notice-error is-dismissible">
                <p>' . esc_html__('Invalid newsletter slug provided.', 'newsletter') . '</p>
             </div>';
    }

    // Add more notices as needed
}
add_action('admin_notices', 'newsletter_admin_notices');

/**
 * Shortcode for Newsletter Preview (Updated to use default template exclusively)
 * Allows embedding a preview within admin pages or elsewhere
 */
function newsletter_preview_shortcode($atts) {
    $atts = shortcode_atts([
        'newsletter_slug' => 'default', // Default to 'default' if not provided
    ], $atts, 'newsletter_preview');

    $newsletter_slug = sanitize_text_field($atts['newsletter_slug']);

    // Validate the newsletter_slug
    $newsletter_list = get_option('newsletter_list', []);
    if (!array_key_exists($newsletter_slug, $newsletter_list)) {
        error_log("Shortcode Validation: Invalid newsletter slug provided: '{$newsletter_slug}'.");
        return __('Invalid newsletter slug provided.', 'newsletter');
    }

    // Generate preview content using the helper function (correct parameters)
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $newsletter_html = newsletter_generate_preview_content($newsletter_slug, $blocks);

    return $newsletter_html;
}
add_shortcode('newsletter_preview', 'newsletter_preview_shortcode');

/**
 * Register AJAX Actions
 */
// Load Block Posts
//add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts'); // Already defined in ajax-handlers.php

// Generate Preview
//add_action('wp_ajax_generate_preview', 'newsletter_generate_preview'); // Already defined in ajax-handlers.php

// Save Newsletter Blocks
//add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission'); // Already defined in ajax-handlers.php

?>