<?php
/**
 * Plugin Name:       Chimpr v2.3.1
 * Description:       Fixing automation
 * Version:           2.3.1
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
define('NEWSLETTER_PLUGIN_VERSION', '2.3.1');

/**
 * Enqueue scripts for editor pages
 */
function my_newsletter_enqueue_editor_scripts($hook) {
    if (strpos($hook, 'newsletter-stories') === false) {
        return;
    }

    wp_enqueue_editor();
    wp_enqueue_media();
    wp_enqueue_style('editor-buttons');

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

    // Enqueue all javascript files
    $scripts = array(
        'newsletter-editor' => array('jquery', 'newsletter-editor-js'),
        'newsletter-dates' => array('jquery', 'newsletter-editor'),
        'newsletter-block-manager' => array('jquery', 'newsletter-dates'),
        'newsletter-ajax-operations' => array('jquery', 'newsletter-block-manager'),
        'newsletter-preview' => array('jquery', 'newsletter-ajax-operations'),
        'newsletter-schedule' => array('jquery', 'newsletter-preview'),
        'newsletter-events' => array('jquery', 'newsletter-schedule'),
        'newsletter-main' => array('jquery', 'newsletter-events'),
        'newsletter-pdf-admin' => array('jquery', 'newsletter-main')
    );

    foreach ($scripts as $script => $deps) {
        wp_enqueue_script(
            $script,
            NEWSLETTER_PLUGIN_URL . "assets/js/$script.js",
            $deps,
            NEWSLETTER_PLUGIN_VERSION,
            true
        );
    }
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
 * Include required files
 */
// Core includes
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-menus.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-settings.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/admin-scripts.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax-handlers.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/utilities.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/mailchimp-integration.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/class-mailchimp-api.php';
require_once NEWSLETTER_PLUGIN_DIR . 'admin/admin-page-campaigns.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/newsletter-cron-settings.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/cron-automation.php';

// PDF related includes
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf-settings.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/pdf-functions.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-generator.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-controller.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-mailchimp.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-admin.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-templates.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-security.php';
require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-logger.php';

/**
 * Initialize components
 */
add_action('plugins_loaded', array('Newsletter_Cron_Automation', 'init'));
$mailchimp = new Newsletter_Mailchimp_API();

// Initialize PDF components
new Newsletter_PDF_Controller();
new Newsletter_PDF_Security();
new Newsletter_PDF_Admin();
new Newsletter_PDF_Templates();
$pdf_logger = new Newsletter_PDF_Logger();

/**
 * Plugin activation/deactivation hooks
 */
function newsletter_plugin_activate() {
    // Basic setup
    if (get_option('newsletter_list') === false) {
        update_option('newsletter_list', ['default' => 'Default Newsletter']);
    }

    if (get_option('newsletter_templates') === false) {
        update_option('newsletter_templates', []);
    }

    // PDF directory setup
    $upload_dir = wp_upload_dir();
    $secure_dir = $upload_dir['basedir'] . '/secure';
    if (!file_exists($secure_dir)) {
        wp_mkdir_p($secure_dir);
    }

    // Schedule cron events
    if (!wp_next_scheduled('newsletter_automated_send')) {
        wp_schedule_event(time() + (10 * YEAR_IN_SECONDS), 'daily', 'newsletter_automated_send');
    }

    if (!wp_next_scheduled('newsletter_pdf_cleanup')) {
        wp_schedule_event(time(), 'daily', 'newsletter_pdf_cleanup');
    }

    // Flush rewrite rules for PDF security
    flush_rewrite_rules();
}

function newsletter_plugin_deactivate() {
    wp_clear_scheduled_hook('newsletter_automated_send');
    wp_clear_scheduled_hook('newsletter_pdf_cleanup');
    flush_rewrite_rules();
    error_log("Deactivation Hook: Plugin deactivated.");
}

function newsletter_plugin_uninstall() {
    global $wpdb;

    // Clean up newsletter blocks
    $newsletter_blocks = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_blocks_%'");
    foreach ($newsletter_blocks as $block) {
        delete_option($block->option_name);
        error_log("Uninstall Hook: Deleted option '{$block->option_name}'.");
    }

    // Clean up templates
    $newsletter_templates = get_option('newsletter_templates', []);
    foreach ($newsletter_templates as $template_id => $template) {
        delete_option("newsletter_template_content_{$template_id}");
        error_log("Uninstall Hook: Deleted template content for '{$template_id}'.");
    }

    // Clean up categories
    $newsletter_categories = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_categories_%'");
    foreach ($newsletter_categories as $category) {
        delete_option($category->option_name);
        error_log("Uninstall Hook: Deleted option '{$category->option_name}'.");
    }

    // Clean up PDF related options
    $pdf_options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'newsletter_pdf_%'");
    foreach ($pdf_options as $option) {
        delete_option($option->option_name);
    }

    // Clean up main options
    delete_option('newsletter_list');
    delete_option('newsletter_default_template');
    delete_option('newsletter_templates');

    // Clean up PDF files
    $upload_dir = wp_upload_dir();
    $secure_dir = $upload_dir['basedir'] . '/secure';
    if (is_dir($secure_dir)) {
        array_map('unlink', glob("$secure_dir/*.*"));
        rmdir($secure_dir);
    }

    error_log("Uninstall Hook: Cleanup complete.");
}

// Register hooks
register_activation_hook(__FILE__, 'newsletter_plugin_activate');
register_deactivation_hook(__FILE__, 'newsletter_plugin_deactivate');
register_uninstall_hook(__FILE__, 'newsletter_plugin_uninstall');

// Initialize plugin
function newsletter_plugin_init() {
    load_plugin_textdomain('chimpr-newsletter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    error_log("Plugin Init: Text domain loaded.");
}
add_action('plugins_loaded', 'newsletter_plugin_init');

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

add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'pause_and_edit' && !empty($_GET['campaign_id']) && !empty($_GET['web_id'])) {
        $mailchimp_api = new Newsletter_Mailchimp_API();
        $campaign_id = sanitize_text_field($_GET['campaign_id']);
        $web_id = sanitize_text_field($_GET['web_id']);

        $pause_response = $mailchimp_api->unschedule_campaign($campaign_id);

        if (!is_wp_error($pause_response)) {
            $datacenter = $mailchimp_api->get_datacenter();
            $edit_url = 'https://' . $datacenter . '.admin.mailchimp.com/campaigns/edit?id=' . urlencode($web_id);
            wp_redirect($edit_url);
            exit;
        } else {
            wp_die('Unable to pause this campaign. Please try again.');
        }
    }
});