<?php
if (!defined('ABSPATH')) exit; 

function newsletter_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'newsletter-stories') === false) return;

    // Enqueue Dashicons
    wp_enqueue_style('dashicons');

    // Enqueue jQuery and jQuery UI scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_editor();

    // Enqueue jQuery UI CSS
    wp_enqueue_style(
        'jquery-ui-css',
        'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css'
    );

    // Enqueue CSS files
    wp_enqueue_style('newsletter-admin-css', NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-admin.css', [], NEWSLETTER_PLUGIN_VERSION);
    wp_enqueue_style('newsletter-stories-css', NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-stories.css', ['newsletter-admin-css'], NEWSLETTER_PLUGIN_VERSION);

    // Main admin JS
    wp_enqueue_script(
        'newsletter-admin-js',
        NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-admin.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion'],
        NEWSLETTER_PLUGIN_VERSION,
        true
    );

    // Additional JS files in order
    wp_enqueue_script('newsletter-editor', NEWSLETTER_PLUGIN_URL . 'assets/js/editor.js', ['jquery', 'newsletter-admin-js'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-dates', NEWSLETTER_PLUGIN_URL . 'assets/js/dates.js', ['jquery', 'newsletter-editor'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-block-manager', NEWSLETTER_PLUGIN_URL . 'assets/js/block-manager.js', ['jquery', 'newsletter-dates'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-ajax-operations', NEWSLETTER_PLUGIN_URL . 'assets/js/ajax-operations.js', ['jquery', 'newsletter-block-manager'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-preview', NEWSLETTER_PLUGIN_URL . 'assets/js/preview.js', ['jquery', 'newsletter-ajax-operations'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-schedule', NEWSLETTER_PLUGIN_URL . 'assets/js/schedule.js', ['jquery', 'newsletter-preview'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-events', NEWSLETTER_PLUGIN_URL . 'assets/js/events.js', ['jquery', 'newsletter-schedule'], NEWSLETTER_PLUGIN_VERSION, true);
    wp_enqueue_script('newsletter-main', NEWSLETTER_PLUGIN_URL . 'assets/js/main.js', ['jquery', 'newsletter-events'], NEWSLETTER_PLUGIN_VERSION, true);

    // Prepare categories data for JavaScript
    $categories_data = [];
    if (!empty($all_categories)) {
        foreach ($all_categories as $category) {
            $categories_data[] = [
                'term_id' => $category->term_id,
                'name'    => $category->name,
            ];
        }
    }

    // Localize script
    wp_localize_script('newsletter-admin-js', 'newsletterData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonceLoadPosts' => wp_create_nonce('load_block_posts_nonce'),
        'nonceGeneratePreview' => wp_create_nonce('generate_preview_nonce'),
        'nonceSaveBlocks' => wp_create_nonce('save_blocks_action'),
        'newsletterSlug' => isset($newsletter_slug) ? $newsletter_slug : '',
        'categories' => $categories_data,
        'availableTemplates' => isset($templates_data) ? $templates_data : [],
        'nonceMailchimp' => wp_create_nonce('mailchimp_campaign_nonce'),
    ]);
}

add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');