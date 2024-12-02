<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');
function newsletter_admin_enqueue_scripts($hook) {
    // Only enqueue scripts and styles on newsletter-related admin pages
    if (strpos($hook, 'newsletter-stories') === false) return;

    // Enqueue jQuery and jQuery UI scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css'); // jQuery UI CSS

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
        '1.0'
    );

    // Enqueue the JavaScript file for the newsletter stories page
    wp_enqueue_script(
        'newsletter-stories-js',
        NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-stories.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion'],
        '1.0',
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
        // Try to extract newsletter_slug from the 'page' parameter
        $page_slug = sanitize_text_field($_GET['page']);
        $prefix = 'newsletter-stories-';
        if (strpos($page_slug, $prefix) === 0) {
            $newsletter_slug = str_replace($prefix, '', $page_slug);
        } else {
            $newsletter_slug = '';
        }
    } else {
        $newsletter_slug = '';
    }

    // Localize script to pass PHP variables to JavaScript
    wp_localize_script('newsletter-stories-js', 'newsletterData', [
        'ajaxUrl'                       => admin_url('admin-ajax.php'),
        'nonceLoadPosts'                => wp_create_nonce('load_block_posts_nonce'),
        'nonceGeneratePreview'          => wp_create_nonce('generate_preview_nonce'),
        'nonceUpdateTemplateSelection'  => wp_create_nonce('update_template_selection_nonce'),
        'nonceSaveBlocks'               => wp_create_nonce('save_blocks_nonce'),
        'newsletterSlug'                => $newsletter_slug,
        'blockLabel'                    => __('Block', 'newsletter'),
        'blockTypeLabel'                => __('Block Type:', 'newsletter'),
        'blockTitleLabel'               => __('Block Title:', 'newsletter'),
        'contentLabel'                  => __('Content', 'newsletter'),
        'advertisingLabel'              => __('Advertising', 'newsletter'),
        'selectCategoryLabel'           => __('Select Category:', 'newsletter'),
        'selectCategoryOption'          => __('-- Select Category --', 'newsletter'),
        'selectCategoryPrompt'          => __('Please select a category.', 'newsletter'),
        'advertisingHtmlLabel'          => __('Advertising HTML:', 'newsletter'),
        'removeBlockLabel'              => __('Remove Block', 'newsletter'),
        'categories'                    => $categories_js,
    ]);
}
?>
