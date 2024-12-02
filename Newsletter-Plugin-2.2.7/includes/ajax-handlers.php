<?php
// includes/ajax-handlers.php

// Ensure no direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include helper functions
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';


/**
 * AJAX Handler to Load Block Posts
 */
function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $category_id     = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index     = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $start_date      = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date        = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

    if ($category_id <= 0) {
        wp_send_json_error(__('Invalid category ID.', 'newsletter'));
    }

    $posts_args = [
        'cat'            => $category_id,
        'numberposts'    => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ];

    // Apply date filters if set
    if (!empty($start_date) && !empty($end_date)) {
        $posts_args['date_query'] = [
            [
                'after'     => $start_date,
                'before'    => $end_date,
                'inclusive' => true,
            ],
        ];
    }

    $posts = get_posts($posts_args);

    if ($posts) {
        $html = '<ul class="sortable-posts">';
        foreach ($posts as $post) {
            $post_id = $post->ID;
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '';
            $post_title = get_the_title($post_id);

            $html .= '<li data-post-id="' . esc_attr($post_id) . '">';
            $html .= '<span class="dashicons dashicons-menu drag-handle" style="cursor: move; margin-right: 10px;"></span>';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][selected]" value="1"> ';
            if ($thumbnail_url) {
                $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post_title) . '" style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
            }
            $html .= esc_html($post_title);
            $html .= '</label>';
            $html .= '<input type="hidden" class="post-order" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" value="0">';
            $html .= '</li>';
        }
        $html .= '</ul>';

        wp_send_json_success($html);
    } else {
        wp_send_json_error(__('No posts found in this category and date range.', 'newsletter'));
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');

/**
 * AJAX Handler to Generate Preview
 */
function newsletter_generate_preview() {
    check_ajax_referer('generate_preview_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    $template_id     = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : 'default';

    // Retrieve blocks data from the form submission
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    // Sanitize and process blocks data
    $sanitized_blocks = [];

    foreach ($blocks as $block_index => $block) {
        $block_type = isset($block['type']) ? sanitize_text_field($block['type']) : '';
        $block_title = isset($block['title']) ? sanitize_text_field($block['title']) : '';
        $block_category = isset($block['category']) ? intval($block['category']) : 0;

        $sanitized_block = [
            'type'  => $block_type,
            'title' => $block_title,
        ];

        if ($block_type === 'content') {
            $sanitized_block['category'] = $block_category;
            $sanitized_block['posts'] = [];

            if (isset($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    $post_id = intval($post_id);
                    $selected = isset($post_data['selected']) && $post_data['selected'] == '1';
                    $order = isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX;

                    if ($selected) {
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order'    => $order,
                        ];
                    }
                }
            }
        } elseif ($block_type === 'advertising') {
            $sanitized_block['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    // Generate the preview content
    $preview_html = newsletter_generate_preview_content($newsletter_slug, $template_id, $sanitized_blocks);

    wp_send_json_success($preview_html);
}
add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');

/**
 * AJAX Handler to Save Newsletter Blocks
 */
function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'newsletter'));
    }

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    $sanitized_blocks = [];

    foreach ($blocks as $block_index => $block) {
        $block_type = isset($block['type']) ? sanitize_text_field($block['type']) : '';
        $block_title = isset($block['title']) ? sanitize_text_field($block['title']) : '';

        $sanitized_block = [
            'type'  => $block_type,
            'title' => $block_title,
        ];

        if ($block_type === 'content') {
            $block_category = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['category'] = $block_category;
            $sanitized_block['posts'] = [];

            if (isset($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    $post_id = intval($post_id);
                    $selected = isset($post_data['selected']) && $post_data['selected'] == '1';
                    $order = isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX;

                    if ($selected) {
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order'    => $order,
                        ];
                    }
                }
            }
        } elseif ($block_type === 'advertising') {
            $sanitized_block['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    // Update the newsletter blocks option
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}
add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');

/**
 * AJAX Handler to Update Template Selection
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

/**
 * AJAX Handler to Create Mailchimp Campaign
 */
function create_mailchimp_campaign() {
    // Verify nonce
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    // Get newsletter slug
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    if (empty($newsletter_slug)) {
        wp_send_json_error('Newsletter slug is required.');
        return;
    }

    try {
        // Create campaign
        $result = np_create_campaign($newsletter_slug);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        wp_send_json_success([
            'campaign_id' => $result['id'],
            'web_id'      => $result['web_id'],
            'message'     => __('Campaign created successfully.', 'newsletter'),
        ]);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_mailchimp_campaign', 'create_mailchimp_campaign');
