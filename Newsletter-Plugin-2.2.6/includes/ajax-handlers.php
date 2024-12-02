<?php
// ajax-handlers.php

// Ensure no direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include helper functions
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';

/**
 * Load Posts for a Block via AJAX
 */
function newsletter_load_block_posts() {
    // Verify nonce for security
    check_ajax_referer('load_block_posts_nonce', 'security');

    // Retrieve and sanitize AJAX parameters
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;

    // Retrieve date filters from AJAX request
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

    // Validate newsletter_slug and category_id
    if (empty($newsletter_slug)) {
        wp_send_json_error(__('Invalid newsletter slug.', 'newsletter'));
    }
    if ($category_id <= 0) {
        wp_send_json_error(__('Invalid category ID.', 'newsletter'));
    }

    // Set up query arguments
    $args = [
        'category'    => $category_id,
        'numberposts' => -1,
    ];

    // Apply date filters if provided
    if (!empty($start_date) && !empty($end_date)) {
        $args['date_query'] = [
            [
                'after'     => $start_date,
                'before'    => $end_date,
                'inclusive' => true,
            ],
        ];
    }

    // Retrieve posts based on the arguments
    $posts = get_posts($args);

    if ($posts) {
        // Start output buffering to capture HTML
        ob_start();
        echo '<ul class="sortable-posts">';
        foreach ($posts as $post) {
            // Fetch existing blocks to get the current order
            $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
            $selected_posts = isset($blocks[$block_index]['posts']) ? $blocks[$block_index]['posts'] : [];

            // Check if the current post is selected
            $is_selected = in_array($post->ID, $selected_posts, true);

            // Determine the order value if selected
            $order = $is_selected ? array_search($post->ID, $selected_posts, true) : 0;

            echo '<li data-post-id="' . esc_attr($post->ID) . '">';
            echo '<span class="dashicons dashicons-menu drag-handle" style="cursor: move; margin-right: 10px;"></span> ';
            echo '<label>';
            echo '<input type="checkbox" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post->ID) . '][selected]" value="1" ' . checked($is_selected, true, false) . '> ';
            echo esc_html($post->post_title);
            echo '</label>';
            // Hidden input to store the order; default is 0 or existing order
            echo '<input type="hidden" class="post-order" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post->ID) . '][order]" value="' . esc_attr($order) . '">';
            echo '</li>';
        }
        echo '</ul>';
        $content = ob_get_clean();
        wp_send_json_success($content);
    } else {
        wp_send_json_error(__('No posts found in this category and date range.', 'newsletter'));
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');

/**
 * Save Newsletter Blocks via AJAX
 */
function newsletter_save_blocks() {
    // Verify nonce for security
    check_ajax_referer('save_blocks_nonce', 'security');

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'newsletter'));
    }

    // Retrieve and sanitize AJAX parameters
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    // Validate newsletter_slug
    if (empty($newsletter_slug)) {
        wp_send_json_error(__('Invalid newsletter slug.', 'newsletter'));
    }

    $sanitized_blocks = [];
    foreach ($blocks as $block) {
        // Sanitize block data
        $sanitized_block = [
            'type'     => isset($block['type']) ? sanitize_text_field($block['type']) : '',
            'category' => isset($block['category']) ? intval($block['category']) : null,
            'title'    => isset($block['title']) ? sanitize_text_field($block['title']) : '',
            'posts'    => [],
            'html'     => isset($block['html']) ? wp_kses_post($block['html']) : '',
        ];

        if ($sanitized_block['type'] === 'content' && isset($block['posts'])) {
            // Collect selected posts and their order
            $posts = $block['posts'];

            // Sort posts by 'order' using uasort to maintain keys
            uasort($posts, function($a, $b) {
                return intval($a['order']) - intval($b['order']);
            });

            // Only include posts that are selected
            foreach ($posts as $post_id => $post_data) {
                if (isset($post_data['selected'])) {
                    $sanitized_block['posts'][] = intval($post_id);
                }
            }
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    // Update the newsletter blocks option
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}
add_action('wp_ajax_save_newsletter_blocks', 'newsletter_save_blocks');

/**
 * Generate Newsletter Preview via AJAX
 */
function newsletter_generate_preview() {
    // Verify nonce for security
    check_ajax_referer('generate_preview_nonce', 'security');

    // Retrieve and sanitize AJAX parameters
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

    // Validate newsletter_slug and template_id
    if (empty($newsletter_slug) || empty($template_id)) {
        wp_send_json_error(__('Invalid newsletter slug or template ID.', 'newsletter'));
    }

    // Retrieve blocks data
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);

    // Generate preview content
    $preview_html = newsletter_generate_preview_content($newsletter_slug, $template_id, $blocks);

    wp_send_json_success($preview_html);
}
add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');

/**
 * Update Template Selection via AJAX
 */
function newsletter_update_template_selection() {
    // Verify nonce for security
    check_ajax_referer('update_template_selection_nonce', 'security');

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'newsletter'));
    }

    // Retrieve and sanitize AJAX parameters
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

    // Validate newsletter_slug and template_id
    if (empty($newsletter_slug) || empty($template_id)) {
        wp_send_json_error(__('Invalid newsletter slug or template ID.', 'newsletter'));
    }

    // Retrieve all templates
    $templates = get_option('newsletter_templates', []);

    // Extract template IDs
    $template_ids = array_column($templates, 'id');

    // Verify that the template exists or is 'default'
    if ($template_id !== 'default') {
        // Check if templates are available
        if (!is_array($templates) || empty($templates)) {
            wp_send_json_error(__('No templates available.', 'newsletter'));
        }

        // Verify that the template_id exists in the templates
        if (!in_array($template_id, $template_ids, true)) {
            wp_send_json_error(__('Invalid template selected.', 'newsletter'));
        }
    }

    // Update the template selection for the newsletter
    update_option("newsletter_template_id_$newsletter_slug", $template_id);

    wp_send_json_success(__('Template updated successfully.', 'newsletter'));
}
add_action('wp_ajax_update_template_selection', 'newsletter_update_template_selection');
?>
