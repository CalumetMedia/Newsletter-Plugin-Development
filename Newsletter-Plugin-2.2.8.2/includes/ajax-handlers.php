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
    $custom_css = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';

    // Save custom header/footer temporarily if provided
    if (!empty($custom_header)) {
        update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
    }

    if (!empty($custom_footer)) {
        update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
    } 

    // Save custom CSS if provided
    if (!empty($custom_css)) {
        update_option("newsletter_custom_css_$newsletter_slug", $custom_css);
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

    $preview_content = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);

    // Wrap content in scoped container with CSS
    $preview_html = '<div class="newsletter-preview-container">';

    if (!empty($custom_css)) {
        $preview_html .= '<style type="text/css">';
        $preview_html .= '.newsletter-preview-container {';
        $preview_html .= $custom_css;
        $preview_html .= '}';
        $preview_html .= '</style>';
    }

    $preview_html .= '<div class="newsletter-content">';
    $preview_html .= $preview_content;
    $preview_html .= '</div>';
    $preview_html .= '</div>';

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

    // Save manual schedule settings first
    if (isset($_POST['use_manual_schedule'])) {
        update_option("newsletter_use_manual_schedule_$newsletter_slug", true);
        update_option("newsletter_manual_schedule_date_$newsletter_slug", sanitize_text_field($_POST['manual_schedule_date']));
        update_option("newsletter_manual_schedule_time_$newsletter_slug", sanitize_text_field($_POST['manual_schedule_time']));

    } else {
        update_option("newsletter_use_manual_schedule_$newsletter_slug", false);
        delete_option("newsletter_manual_schedule_date_$newsletter_slug");
        delete_option("newsletter_manual_schedule_time_$newsletter_slug");
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

    // Save the blocks
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    // Save assigned categories
    if (isset($_POST['assigned_categories']) && is_array($_POST['assigned_categories'])) {
        $assigned_categories = array_map('intval', $_POST['assigned_categories']);
        update_option("newsletter_categories_$newsletter_slug", $assigned_categories);
    }

    // Save selected template
    if (isset($_POST['selected_template_id'])) {
        $selected_template_id = sanitize_text_field($_POST['selected_template_id']);
        update_option("newsletter_template_id_$newsletter_slug", $selected_template_id);

    }

    // **New Code: Save Subject Line and Campaign Name**
    if (isset($_POST['subject_line'])) {
        $subject_line = sanitize_text_field($_POST['subject_line']);
        update_option("newsletter_subject_line_$newsletter_slug", $subject_line);
    }


    if (isset($_POST['campaign_name'])) {
        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        update_option("newsletter_campaign_name_$newsletter_slug", $campaign_name);
    }

    // Save custom header/footer HTML
    if (isset($_POST['custom_header'])) {
        update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post($_POST['custom_header']));
    }

    if (isset($_POST['custom_footer'])) {
        update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post($_POST['custom_footer']));
    }

    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');

/**
 * AJAX Handler to Create Mailchimp Campaign
 */
function create_mailchimp_campaign() {
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    // Log start of function
    error_log('Starting create_mailchimp_campaign');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    if (empty($newsletter_slug)) {
        error_log('Newsletter slug missing');
        wp_send_json_error('Newsletter slug is required.');
        return;
    }

    // Get and log campaign details
    $subject_line = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');
    error_log("Campaign Details - Slug: $newsletter_slug, Subject: $subject_line, Name: $campaign_name");

    // Get blocks and generate content
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    if (empty($blocks)) {
        error_log('No blocks found for newsletter');
        wp_send_json_error('No content blocks found for newsletter.');
        return;
    }

    // Generate content
    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        error_log('Generated content is empty');
        wp_send_json_error('Failed to generate newsletter content.');
        return;
    }

    try {
        error_log('Initializing Mailchimp API');
        $mailchimp = new Newsletter_Mailchimp_API();
        
        // Create campaign
        error_log('Creating campaign in Mailchimp');
        $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
        
        if (is_wp_error($campaign)) {
            error_log('Campaign creation failed: ' . $campaign->get_error_message());
            wp_send_json_error($campaign->get_error_message());
            return;
        }

        // Set campaign content
        error_log('Setting campaign content');
        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        
        if (is_wp_error($content_result)) {
            error_log('Setting content failed: ' . $content_result->get_error_message());
            wp_send_json_error($content_result->get_error_message());
            return;
        }

        error_log('Campaign created successfully');
        wp_send_json_success([
            'campaign_id' => $campaign['id'],
            'web_id' => $campaign['web_id'],
            'message' => __('Campaign created successfully.', 'newsletter'),
        ]);

    } catch (Exception $e) {
        error_log('Exception in create_mailchimp_campaign: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}

add_action('wp_ajax_create_mailchimp_campaign', 'create_mailchimp_campaign');

?>