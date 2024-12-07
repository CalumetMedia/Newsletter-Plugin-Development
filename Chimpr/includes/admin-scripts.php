<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

function newsletter_admin_enqueue_scripts($hook) {
    // Only enqueue scripts and styles on newsletter-related admin pages
    if (strpos($hook, 'newsletter-stories') === false) return;

    // Enqueue Dashicons
    wp_enqueue_style('dashicons');

    // Enqueue jQuery and jQuery UI scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-accordion');

    // Enqueue jQuery UI CSS
    wp_enqueue_style(
        'jquery-ui-css',
        'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css'
    );

    // Enqueue admin CSS
    wp_enqueue_style(
        'newsletter-admin-css',
        NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-admin.css',
        [],
        NEWSLETTER_PLUGIN_VERSION
    );

    // Enqueue stories-specific CSS after admin CSS to allow overrides
    wp_enqueue_style(
        'newsletter-stories-css',
        NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-stories.css',
        ['newsletter-admin-css'],
        NEWSLETTER_PLUGIN_VERSION
    );

    // Enqueue the JavaScript file for the newsletter stories page
    wp_enqueue_script(
        'newsletter-admin-js',
        NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-admin.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion'],
        NEWSLETTER_PLUGIN_VERSION,
        true // Load in footer
    );

    // Prepare categories data for JavaScript
    $categories_js = [];
    $all_categories = get_categories(['hide_empty' => false]);
    foreach ($all_categories as $category) {
        $categories_js[] = [
            'term_id' => $category->term_id,
            'name'    => $category->name,
        ];
    }

    // Get newsletter_slug from URL or page slug
    if (isset($_GET['newsletter_slug'])) {
        $newsletter_slug = sanitize_text_field($_GET['newsletter_slug']);
    } elseif (isset($_GET['page'])) {
        $page_slug = sanitize_text_field($_GET['page']);
        $prefix = 'newsletter-stories-';
        $newsletter_slug = (strpos($page_slug, $prefix) === 0) 
            ? str_replace($prefix, '', $page_slug) 
            : 'default';
    } else {
        $newsletter_slug = 'default';
    }

    // Localize script to pass PHP variables to JavaScript
    wp_localize_script('newsletter-admin-js', 'newsletterData', [
        // AJAX Settings
        'ajaxUrl'                      => admin_url('admin-ajax.php'),
        'nonceLoadPosts'               => wp_create_nonce('load_block_posts_nonce'),
        'nonceGeneratePreview'         => wp_create_nonce('generate_preview_nonce'),
        'nonceSaveBlocks'              => wp_create_nonce('save_blocks_action'),
        'nonceUpdateTemplateSelection' => wp_create_nonce('update_template_selection_nonce'),
        'nonceMailchimp'               => wp_create_nonce('mailchimp_campaign_nonce'),
        'newsletterSlug'               => $newsletter_slug,
        'categories'                   => $categories_js,

        // Labels
        'blockLabel'                   => __('Block', 'newsletter'),
        'blockTitleLabel'              => __('Block Title:', 'newsletter'),
        'blockTypeLabel'               => __('Block Type:', 'newsletter'),
        'contentLabel'                 => __('Content', 'newsletter'),
        'htmlLabel'                    => __('HTML', 'newsletter'),
        'customHtmlLabel'              => __('Custom HTML:', 'newsletter'),
        'templateLabel'                => __('Template:', 'newsletter'),
        'selectCategoryLabel'          => __('Select Category:', 'newsletter'),
        'selectCategoryOption'         => __('-- Select Category --', 'newsletter'),
        'selectCategoryPrompt'         => __('Please select a category.', 'newsletter'),
        'removeBlockLabel'             => __('Remove Block', 'newsletter'),

        // Mailchimp Messages
        'mailchimpConfirmSend'         => __('This will create a draft campaign in Mailchimp. Would you like to continue?', 'newsletter'),
        'mailchimpSuccess'             => __('Newsletter draft created in Mailchimp successfully!', 'newsletter'),
        'mailchimpError'               => __('Error creating Mailchimp campaign: ', 'newsletter'),

        // Additional Data for Debugging
        'debugShowTitle'               => true, // Enable debug for show_title
        'debugHtmlContent'             => true, // Enable debug for HTML content
    ]);
}

add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');
?>
