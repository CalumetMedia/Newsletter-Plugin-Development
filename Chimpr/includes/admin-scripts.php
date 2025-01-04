<?php
if (!defined('ABSPATH')) exit; 

function newsletter_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'newsletter-stories') === false) return;

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
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-accordion');

    // Main plugin script
    wp_enqueue_script(
        'newsletter-admin',
        NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-admin.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion', 'wp-editor'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    // Additional scripts in dependency order
    $scripts = [
        'editor' => ['wp-editor', 'newsletter-admin'],
        'block-manager' => ['editor'],
        'ajax-operations' => ['block-manager'],
        'preview' => ['ajax-operations'],
        'auto-save' => ['preview'],
        'dates' => ['auto-save'],
        'schedule' => ['dates'],
        'events' => ['schedule'],
        'main' => ['events']
    ];

    foreach ($scripts as $script => $deps) {
        wp_enqueue_script(
            $script,
            NEWSLETTER_PLUGIN_URL . "assets/js/$script.js",
            $deps,
            NEWSLETTER_PLUGIN_VERSION,
            true
        );
    }

    // PDF functionality
    wp_enqueue_script(
        'pdf-admin',
        NEWSLETTER_PLUGIN_URL . 'assets/js/pdf-admin.js',
        ['jquery'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    // Localize script data
    wp_localize_script('newsletter-admin', 'newsletterData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonceLoadPosts' => wp_create_nonce('load_block_posts_nonce'),
        'nonceGeneratePreview' => wp_create_nonce('generate_preview_nonce'),
        'nonceSaveBlocks' => wp_create_nonce('save_blocks_action'),
        'newsletterSlug' => isset($newsletter_slug) ? $newsletter_slug : '',
        'categories' => isset($categories_data) ? $categories_data : [],
        'availableTemplates' => isset($templates_data) ? $templates_data : [],
        'nonceMailchimp' => wp_create_nonce('mailchimp_campaign_nonce'),
    ]);
}

add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');