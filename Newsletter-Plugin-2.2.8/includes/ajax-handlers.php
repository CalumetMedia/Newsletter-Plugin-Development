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
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';

    if ($category_id <= 0) {
        wp_send_json_error(__('Invalid category ID.', 'newsletter'));
    }

    $posts_args = [
        'cat'            => $category_id,
        'numberposts'    => 15,
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
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $custom_header = isset($_POST['custom_header']) ? wp_kses_post($_POST['custom_header']) : '';
    $custom_footer = isset($_POST['custom_footer']) ? wp_kses_post($_POST['custom_footer']) : '';

    // Save custom header/footer temporarily if provided
    if (!empty($custom_header)) {
        update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
    }
    if (!empty($custom_footer)) {
        update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
    }

    $sanitized_blocks = [];
    foreach ($blocks as $block) {
        $sanitized_block = [
            'type' => sanitize_text_field($block['type']),
            'title' => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default')
        ];

        if ($sanitized_block['type'] === 'content') {
            $sanitized_block['category'] = intval($block['category'] ?? 0);
            $sanitized_block['posts'] = [];

            if (!empty($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    if (!empty($post_data['selected'])) {
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => intval($post_data['order'] ?? 0)
                        ];
                    }
                }
            }
        } elseif ($sanitized_block['type'] === 'advertising') {
            $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    $preview_html = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);
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
        $block_template_id = isset($block['template_id']) && !empty($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default';

        $sanitized_block = [
            'type' => $block_type,
            'title' => $block_title,
            'template_id' => $block_template_id
        ];

        if ($block_type === 'content') {
            $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['posts'] = [];

            if (isset($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    if (isset($post_data['selected']) && $post_data['selected'] == '1') {
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => isset($post_data['order']) ? intval($post_data['order']) : 0
                        ];
                    }
                }
            }
        } elseif ($block_type === 'advertising') {
            $sanitized_block['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}
add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');


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

    // Get blocks and generate content
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
    $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');

    // Generate full content including header, blocks, and footer
    $content = $custom_header;
    $content .= newsletter_generate_preview_content($newsletter_slug, $blocks);
    $content .= $custom_footer;

    try {
        // Create campaign with full content
        $result = np_create_campaign([
            'newsletter_slug' => $newsletter_slug,
            'content_html' => $content
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        wp_send_json_success([
            'campaign_id' => $result['id'],
            'web_id' => $result['web_id'],
            'message' => __('Campaign created successfully.', 'newsletter'),
        ]);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_mailchimp_campaign', 'create_mailchimp_campaign');
?>
