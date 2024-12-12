<?php
/**
 * Plugin Name:       Chimpr v2.3
 * Description:       Campaign editing, scheduling and basic automation.
 * Version:           2.3
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
define('NEWSLETTER_PLUGIN_VERSION', '2.3');

/**
 * Enqueue scripts for editor pages
 */
function my_newsletter_enqueue_editor_scripts($hook) {
    if (strpos($hook, 'newsletter-stories') === false) {
        return;
    }

    // Enqueue WP Editor assets
    wp_enqueue_editor();

    // Media management
    wp_enqueue_media();

    // Editor styles
    wp_enqueue_style('editor-buttons');

    // Enqueue main admin JS
    wp_enqueue_script(
        'newsletter-editor-js',
        NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-admin.js',
        array('jquery', 'editor', 'quicktags'),
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    wp_localize_script('newsletter-editor-js', 'newsletterData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'newsletterSlug' => 'default',
        'nonceLoadPosts' => wp_create_nonce('load_posts'),
        'nonceSaveBlocks' => wp_create_nonce('save_blocks'),
        'nonceMailchimp' => wp_create_nonce('mailchimp'),
        'nonceGeneratePreview' => wp_create_nonce('generate_preview'),
        'blockLabel' => __('Block', 'chimpr-newsletter'),
        'blockTitleLabel' => __('Block Title', 'chimpr-newsletter'),
        'blockTypeLabel' => __('Block Type', 'chimpr-newsletter'),
        'templateLabel' => __('Template', 'chimpr-newsletter'),
        'selectCategoryLabel' => __('Select Category', 'chimpr-newsletter'),
        'selectCategoryOption' => __('-- Select a Category --', 'chimpr-newsletter'),
        'contentLabel' => __('Content', 'chimpr-newsletter'),
        'selectCategoryPrompt' => __('Please select a category.', 'chimpr-newsletter'),
        'removeBlockLabel' => __('Remove Block', 'chimpr-newsletter'),
        'blocksSavedMessage' => __('Blocks have been saved successfully.', 'chimpr-newsletter'),
        'availableTemplates' => array(),
        'categories' => array(),
    ));

    // Enqueue additional JS files
    wp_enqueue_script('newsletter-editor', NEWSLETTER_PLUGIN_URL . 'assets/js/editor.js', array('jquery', 'newsletter-editor-js'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-dates', NEWSLETTER_PLUGIN_URL . 'assets/js/dates.js', array('jquery', 'newsletter-editor'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-block-manager', NEWSLETTER_PLUGIN_URL . 'assets/js/block-manager.js', array('jquery', 'newsletter-dates'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-ajax-operations', NEWSLETTER_PLUGIN_URL . 'assets/js/ajax-operations.js', array('jquery', 'newsletter-block-manager'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-preview', NEWSLETTER_PLUGIN_URL . 'assets/js/preview.js', array('jquery', 'newsletter-ajax-operations'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-schedule', NEWSLETTER_PLUGIN_URL . 'assets/js/schedule.js', array('jquery', 'newsletter-preview'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-events', NEWSLETTER_PLUGIN_URL . 'assets/js/events.js', array('jquery', 'newsletter-schedule'), NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-main', NEWSLETTER_PLUGIN_URL . 'assets/js/main.js', array('jquery', 'newsletter-events'), NEWSLETTER_PLUGIN_VERSION, true);
}
add_action('admin_enqueue_scripts', 'my_newsletter_enqueue_editor_scripts');

function initialize_tinymce_on_page() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'newsletter-stories') !== false) {
        add_filter('user_can_richedit', '__return_true');
        wp_enqueue_editor();
    }
}
add_action('admin_init', 'initialize_tinymce_on_page');

/**
 * Handle Add Newsletter Form Early (Before Output)
 */
add_action('admin_init', 'handle_add_newsletter_form');
function handle_add_newsletter_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_newsletter_submit'])) {
        // Verify nonce for security
        if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'add_newsletter_action')) {
            set_transient('newsletter_add_error', __('Security check failed. Please try again.', 'newsletter'), 30);
            return;
        }

        if (empty($_POST['new_newsletter_name'])) {
            set_transient('newsletter_add_error', __('Newsletter Name is required.', 'newsletter'), 30);
            return;
        }

        $newsletter_list = get_option('newsletter_list', []);
        $newsletter_name = sanitize_text_field($_POST['new_newsletter_name']);
        $newsletter_id   = sanitize_title($newsletter_name);

        if (isset($newsletter_list[$newsletter_id])) {
            set_transient('newsletter_add_error', __('A newsletter with this name already exists.', 'newsletter'), 30);
            return;
        }

        // Add the new newsletter
        $newsletter_list[$newsletter_id] = $newsletter_name;
        update_option('newsletter_list', $newsletter_list);

        // Redirect now
        wp_redirect(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_id));
        exit;
    }
}

/**
 * Include Necessary Files
 */
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-menus.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-settings.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-scripts.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax-handlers.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/utilities.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/mailchimp-integration.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/class-mailchimp-api.php';
require_once NEWSLETTER_PLUGIN_DIR . 'admin/admin-page-campaigns.php';

// Include the new cron settings page
require_once NEWSLETTER_PLUGIN_DIR . 'includes/newsletter-cron-settings.php';

// Include and instantiate cron automation logic
require_once NEWSLETTER_PLUGIN_DIR . 'includes/cron-automation.php';
$newsletter_cron = new Newsletter_Cron_Automation();

// Initialize Mailchimp API
$mailchimp = new Newsletter_Mailchimp_API();

/**
 * Plugin Activation
 */
function newsletter_plugin_activate() {
    if (get_option('newsletter_list') === false) {
        update_option('newsletter_list', ['default' => 'Default Newsletter']);
    }

    if (get_option('newsletter_templates') === false) {
        update_option('newsletter_templates', []);
    }
}

function newsletter_check_version() {
    $current_version = get_option('newsletter_plugin_version');
    if ($current_version === false || version_compare($current_version, NEWSLETTER_PLUGIN_VERSION, '<')) {
        $newsletter_list = get_option('newsletter_list', []);
        if (empty($newsletter_list)) {
            update_option('newsletter_list', ['default' => 'Default Newsletter']);
        }

        update_option('newsletter_plugin_version', NEWSLETTER_PLUGIN_VERSION);
    }
}
add_action('plugins_loaded', 'newsletter_check_version');

register_activation_hook(__FILE__, 'newsletter_plugin_activate');

/**
 * Deactivation Hook
 */
function newsletter_plugin_deactivate() {
    error_log("Deactivation Hook: Plugin deactivated.");
}
register_deactivation_hook(__FILE__, 'newsletter_plugin_deactivate');

/**
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'newsletter_plugin_uninstall');
function newsletter_plugin_uninstall() {
    global $wpdb;

    // Delete newsletter blocks
    $newsletter_blocks = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_blocks_%'");
    foreach ($newsletter_blocks as $block) {
        delete_option($block->option_name);
        error_log("Uninstall Hook: Deleted option '{$block->option_name}'.");
    }

    // Delete newsletter templates
    $newsletter_templates = get_option('newsletter_templates', []);
    foreach ($newsletter_templates as $template_id => $template) {
        delete_option("newsletter_template_content_{$template_id}");
        error_log("Uninstall Hook: Deleted template content for '{$template_id}'.");
    }

    // Delete newsletter categories
    $newsletter_categories = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_categories_%'");
    foreach ($newsletter_categories as $category) {
        delete_option($category->option_name);
        error_log("Uninstall Hook: Deleted option '{$category->option_name}'.");
    }

    // Delete main plugin options
    delete_option('newsletter_list');
    delete_option('newsletter_default_template');
    delete_option('newsletter_templates');
    error_log("Uninstall Hook: Deleted 'newsletter_list', 'newsletter_default_template', and 'newsletter_templates' options.");
}

/**
 * Initialize the Plugin
 */
function newsletter_plugin_init() {
    load_plugin_textdomain('chimpr-newsletter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    error_log("Plugin Init: Text domain loaded.");
}
add_action('plugins_loaded', 'newsletter_plugin_init');

/**
 * Admin Notices
 */
function newsletter_admin_notices() {
    if (isset($_GET['newsletter_saved']) && $_GET['newsletter_saved'] == 'true') {
        echo '<div class="notice notice-success is-dismissible">
                <p>' . esc_html__('Newsletter blocks have been successfully saved.', 'chimpr-newsletter') . '</p>
             </div>';
    }

    if (isset($_GET['message']) && $_GET['message'] == 'invalid_slug') {
        echo '<div class="notice notice-error is-dismissible">
                <p>' . esc_html__('Invalid newsletter slug provided.', 'chimpr-newsletter') . '</p>
             </div>';
    }
}
add_action('admin_notices', 'newsletter_admin_notices');

/**
 * Shortcode for Newsletter Preview
 */
function newsletter_preview_shortcode($atts) {
    $atts = shortcode_atts([
        'newsletter_slug' => 'default',
    ], $atts, 'newsletter_preview');

    $newsletter_slug = sanitize_text_field($atts['newsletter_slug']);
    $newsletter_list = get_option('newsletter_list', []);
    if (!array_key_exists($newsletter_slug, $newsletter_list)) {
        error_log("Shortcode Validation: Invalid newsletter slug provided: '{$newsletter_slug}'.");
        return __('Invalid newsletter slug provided.', 'chimpr-newsletter');
    }

    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $newsletter_html = newsletter_generate_preview_content($newsletter_slug, $blocks);

    return $newsletter_html;
}
add_shortcode('newsletter_preview', 'newsletter_preview_shortcode');

/**
 * Handle pausing a scheduled campaign before edit
 */
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'pause_and_edit' && !empty($_GET['campaign_id']) && !empty($_GET['web_id'])) {
        $mailchimp_api = new Newsletter_Mailchimp_API();
        $campaign_id = sanitize_text_field($_GET['campaign_id']);
        $web_id = sanitize_text_field($_GET['web_id']);

        // Attempt to unschedule (pause) the campaign
        $pause_response = $mailchimp_api->unschedule_campaign($campaign_id);

        if (!is_wp_error($pause_response)) {
            // Redirect to Mailchimp edit page
            $datacenter = $mailchimp_api->get_datacenter();
            $edit_url = 'https://' . $datacenter . '.admin.mailchimp.com/campaigns/edit?id=' . urlencode($web_id);
            wp_redirect($edit_url);
            exit;
        } else {
            wp_die('Unable to pause this campaign. Please try again.');
        }
    }
});
