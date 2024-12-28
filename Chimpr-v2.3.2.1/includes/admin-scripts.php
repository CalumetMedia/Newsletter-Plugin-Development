<?php
if (!defined('ABSPATH')) exit; 

function newsletter_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'newsletter-stories') === false) return;

    // Get newsletter slug from URL or default
    $newsletter_slug = isset($_GET['newsletter']) ? sanitize_text_field($_GET['newsletter']) : 'default';

    // Core WP Editor Requirements
    wp_enqueue_editor();
    wp_enqueue_media();
    add_filter('user_can_richedit', '__return_true');

    // Styles
    wp_enqueue_style('dashicons');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style('newsletter-admin-css', NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-admin.css', [], NEWSLETTER_PLUGIN_VERSION);
    wp_enqueue_style('newsletter-stories-css', NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-stories.css', ['newsletter-admin-css'], NEWSLETTER_PLUGIN_VERSION);

    // jQuery UI Dependencies
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-widget');
    wp_enqueue_script('jquery-ui-mouse');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-accordion');

    // Make sure jQuery UI CSS is loaded
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', [], '1.13.2');

    // Initialize data arrays
    $categories_data = [];
    $templates_data = [];

    // Get categories if function exists
    if (function_exists('get_categories')) {
        $categories_data = get_categories(['hide_empty' => false]);
    }

    // Get templates if function exists
    if (function_exists('get_newsletter_templates')) {
        $templates_data = get_newsletter_templates();
    }

    // Prepare localized data
    $newsletter_data = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonceLoadPosts' => wp_create_nonce('load_block_posts_nonce'),
        'security' => wp_create_nonce('generate_preview_nonce'),
        'nonceSaveBlocks' => wp_create_nonce('save_blocks_action'),
        'newsletterSlug' => $newsletter_slug,
        'categories' => $categories_data,
        'availableTemplates' => $templates_data,
        'nonceMailchimp' => wp_create_nonce('mailchimp_campaign_nonce'),
        'selectCategoryPrompt' => __('Please select a category to display posts.', 'newsletter')
    ];

    // Localize the data to jQuery itself to make it available to all scripts
    wp_enqueue_script('newsletter-data', false, ['jquery']);
    wp_localize_script('newsletter-data', 'newsletterData', $newsletter_data);

    // Enqueue all scripts with dependency on newsletter-data
    wp_enqueue_script(
        'events',
        NEWSLETTER_PLUGIN_URL . 'assets/js/events.js',
        ['newsletter-data', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion', 'wp-editor'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    wp_enqueue_script(
        'preview',
        NEWSLETTER_PLUGIN_URL . 'assets/js/preview.js',
        ['events'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    wp_enqueue_script(
        'block-manager',
        NEWSLETTER_PLUGIN_URL . 'assets/js/block-manager.js',
        ['events', 'preview'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    wp_enqueue_script(
        'ajax-operations',
        NEWSLETTER_PLUGIN_URL . 'assets/js/ajax-operations.js',
        ['events', 'block-manager'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    wp_enqueue_script(
        'main',
        NEWSLETTER_PLUGIN_URL . 'assets/js/main.js',
        ['events', 'block-manager', 'ajax-operations'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    // PDF functionality
    wp_enqueue_script(
        'pdf-admin',
        NEWSLETTER_PLUGIN_URL . 'assets/js/pdf-admin.js',
        ['jquery'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );
}

add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');