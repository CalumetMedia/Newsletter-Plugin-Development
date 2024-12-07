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

    error_log("Date range received: start={$start_date}, end={$end_date}");

    $posts_args = [
        'cat'            => $category_id,
        'numberposts'    => 15,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ];



    error_log("Posts query: " . print_r($posts_args, true));
    $posts = get_posts($posts_args);
    error_log("Posts found: " . count($posts));

    $posts = get_posts($posts_args);

    if ($posts) {
        error_log('[newsletter_load_block_posts] Posts found. Returning success.');
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
        error_log('[newsletter_load_block_posts] No posts found.');
        wp_send_json_error(__('No posts found in this category and date range.', 'newsletter'));
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');

/**
 * AJAX Handler to Generate Preview
 */
function newsletter_generate_preview() {
    error_log('[newsletter_generate_preview] AJAX action triggered.');
    check_ajax_referer('generate_preview_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    error_log("[newsletter_generate_preview] slug: $newsletter_slug");

    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $custom_header = isset($_POST['custom_header']) ? wp_kses_post($_POST['custom_header']) : '';
    $custom_footer = isset($_POST['custom_footer']) ? wp_kses_post($_POST['custom_footer']) : '';
    $custom_css = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';

    if (!empty($custom_header)) {
        update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
    }
    if (!empty($custom_footer)) {
        update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
    }
    if (!empty($custom_css)) {
        update_option("newsletter_custom_css_$newsletter_slug", $custom_css);
    }

    $sanitized_blocks = [];
    foreach ($blocks as $block) {
        $sanitized_block = [
            'type'        => sanitize_text_field($block['type']),
            'title'       => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
            'show_title'  => isset($block['show_title']) ? true : false
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
        } elseif ($sanitized_block['type'] === 'html') {
            $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
        }
        $sanitized_blocks[] = $sanitized_block;
    }

    error_log('[newsletter_generate_preview] Generating preview content.');
    $preview_content = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);

    $preview_html = '<div class="newsletter-preview-container">';
    if (!empty($custom_css)) {
        $preview_html .= '<style type="text/css">';
        $preview_html .= '.newsletter-preview-container {' . $custom_css . '}';
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
    error_log('[newsletter_handle_blocks_form_submission] AJAX action triggered.');
    check_ajax_referer('save_blocks_action', 'security');

    if (!current_user_can('manage_options')) {
        error_log('[newsletter_handle_blocks_form_submission] Current user cannot manage options.');
        wp_send_json_error(__('You do not have permission to perform this action.', 'newsletter'));
    }

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    error_log("[newsletter_handle_blocks_form_submission] slug: $newsletter_slug");

    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $sanitized_blocks = [];

    foreach ($blocks as $block) {
        $sanitized_block = [
            'type' => sanitize_text_field($block['type']),
            'title' => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
            'show_title' => isset($block['show_title']) ? true : false
        ];

        if ($sanitized_block['type'] === 'content') {
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
        } elseif ($sanitized_block['type'] === 'html' || $sanitized_block['type'] === 'advertising') {
            $sanitized_block['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    if (isset($_POST['assigned_categories']) && is_array($_POST['assigned_categories'])) {
        $assigned_categories = array_map('intval', $_POST['assigned_categories']);
        update_option("newsletter_categories_$newsletter_slug", $assigned_categories);
    }

    if (isset($_POST['selected_template_id'])) {
        $selected_template_id = sanitize_text_field($_POST['selected_template_id']);
        update_option("newsletter_template_id_$newsletter_slug", $selected_template_id);
    }

    if (isset($_POST['subject_line'])) {
        $subject_line = sanitize_text_field($_POST['subject_line']);
        update_option("newsletter_subject_line_$newsletter_slug", $subject_line);
    }

    if (isset($_POST['campaign_name'])) {
        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        update_option("newsletter_campaign_name_$newsletter_slug", $campaign_name);
    }

    if (isset($_POST['custom_header'])) {
        update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post($_POST['custom_header']));
    }

    if (isset($_POST['custom_footer'])) {
        update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post($_POST['custom_footer']));
    }

    error_log('[newsletter_handle_blocks_form_submission] Blocks saved successfully.');
    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}
add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');

/**
 * AJAX Handler to Save Newsletter Schedule
 */
function handle_save_newsletter_schedule() {
    error_log('[handle_save_newsletter_schedule] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = sanitize_text_field($_POST['newsletter_slug']);
    error_log("[handle_save_newsletter_schedule] slug: $newsletter_slug");

    update_option("newsletter_use_manual_schedule_$newsletter_slug", sanitize_text_field($_POST['use_manual_schedule']));

    if (!empty($_POST['manual_schedule_date'])) {
        update_option("newsletter_manual_schedule_date_$newsletter_slug", sanitize_text_field($_POST['manual_schedule_date']));
    }

    if (!empty($_POST['manual_schedule_time'])) {
        update_option("newsletter_manual_schedule_time_$newsletter_slug", sanitize_text_field($_POST['manual_schedule_time']));
    }

    error_log('[handle_save_newsletter_schedule] Schedule saved.');
    wp_send_json_success(['message' => 'Schedule settings saved']);
}
add_action('wp_ajax_save_newsletter_schedule', 'handle_save_newsletter_schedule');

/**
 * AJAX Handler to Create Mailchimp Campaign
 */
function create_mailchimp_campaign() {
    error_log('[create_mailchimp_campaign] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    error_log("[create_mailchimp_campaign] slug: $newsletter_slug");

    if (empty($newsletter_slug)) {
        wp_send_json_error('Newsletter slug is required.');
    }

    $subject_line = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');

    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    if (empty($blocks)) {
        error_log('[create_mailchimp_campaign] No content blocks.');
        wp_send_json_error('No content blocks found for newsletter.');
    }

    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        error_log('[create_mailchimp_campaign] Failed to generate content.');
        wp_send_json_error('Failed to generate newsletter content.');
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        error_log('[create_mailchimp_campaign] Creating campaign via Mailchimp API.');
        $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);

        if (is_wp_error($campaign)) {
            error_log('[create_mailchimp_campaign] ' . $campaign->get_error_message());
            wp_send_json_error($campaign->get_error_message());
        }

        error_log('[create_mailchimp_campaign] Setting campaign content.');
        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            error_log('[create_mailchimp_campaign] ' . $content_result->get_error_message());
            wp_send_json_error($content_result->get_error_message());
        }

        update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);
        error_log('[create_mailchimp_campaign] Campaign created successfully with ID: ' . $campaign['id']);
        wp_send_json_success([
            'campaign_id' => $campaign['id'],
            'web_id' => $campaign['web_id'],
            'message' => __('Campaign created successfully.', 'newsletter'),
        ]);
    } catch (Exception $e) {
        error_log('[create_mailchimp_campaign] Exception: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_mailchimp_campaign', 'create_mailchimp_campaign');

/**
 * AJAX Handler to Create and Schedule a New Mailchimp Campaign in One Step
 */
function create_and_schedule_campaign() {
    error_log('[create_and_schedule_campaign] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';
    error_log("[create_and_schedule_campaign] slug: $newsletter_slug, schedule: $schedule_datetime");

    if (empty($newsletter_slug) || empty($schedule_datetime)) {
        wp_send_json_error('Newsletter slug and schedule datetime are required.');
    }

    $site_timezone = wp_timezone();
    $local_dt = new DateTime($schedule_datetime, $site_timezone);
    $local_dt->setTimezone(new DateTimeZone('UTC'));
    $utc_schedule = $local_dt->format('Y-m-d\TH:i:s\Z');

    $subject_line  = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');

    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    if (empty($blocks)) {
        error_log('[create_and_schedule_campaign] No content blocks.');
        wp_send_json_error('No content blocks found for newsletter.');
    }

    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        error_log('[create_and_schedule_campaign] Failed to generate content.');
        wp_send_json_error('Failed to generate newsletter content.');
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        error_log('[create_and_schedule_campaign] Creating and scheduling campaign.');
        $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
        if (is_wp_error($campaign)) {
            error_log('[create_and_schedule_campaign] ' . $campaign->get_error_message());
            wp_send_json_error($campaign->get_error_message());
        }

        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            error_log('[create_and_schedule_campaign] ' . $content_result->get_error_message());
            wp_send_json_error($content_result->get_error_message());
        }

        update_option("newsletter_mailchimp_campaign_id_$newsletter_slug", $campaign['id']);

        $schedule_result = $mailchimp->schedule_campaign($campaign['id'], $utc_schedule);
        if (is_wp_error($schedule_result)) {
            error_log('[create_and_schedule_campaign] ' . $schedule_result->get_error_message());
            wp_send_json_error($schedule_result->get_error_message());
        }

        error_log('[create_and_schedule_campaign] Campaign created and scheduled successfully.');
        wp_send_json_success([
            'message' => 'Campaign created and scheduled successfully.',
            'campaign_id' => $campaign['id']
        ]);

    } catch (Exception $e) {
        error_log('[create_and_schedule_campaign] Exception: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_create_and_schedule_campaign', 'create_and_schedule_campaign');

/**
 * AJAX Handler to Send Test Email
 */
function send_test_email() {
    error_log('[send_test_email] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    error_log("[send_test_email] slug: $newsletter_slug, test_email: $test_email");

    if (empty($test_email)) {
        error_log('[send_test_email] Invalid email address.');
        wp_send_json_error(['message' => 'Invalid email address']);
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $content = newsletter_generate_preview_content($newsletter_slug, $blocks);

        error_log('[send_test_email] Creating test campaign.');
        $campaign = $mailchimp->create_campaign($newsletter_slug, "Test - " . date('Y-m-d H:i:s'), "Test Campaign");
        if (is_wp_error($campaign)) {
            error_log('[send_test_email] ' . $campaign->get_error_message());
            wp_send_json_error(['message' => $campaign->get_error_message()]);
        }

        error_log('[send_test_email] Setting content for test campaign.');
        $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
        if (is_wp_error($content_result)) {
            error_log('[send_test_email] ' . $content_result->get_error_message());
            wp_send_json_error(['message' => $content_result->get_error_message()]);
        }

        error_log('[send_test_email] Sending test email.');
        $test_result = $mailchimp->send_test_email($campaign['id'], $test_email);
        if (is_wp_error($test_result)) {
            error_log('[send_test_email] ' . $test_result->get_error_message());
            wp_send_json_error(['message' => $test_result->get_error_message()]);
        }

        error_log('[send_test_email] Test email sent successfully.');
        wp_send_json_success(['message' => 'Test email sent successfully']);
    } catch (Exception $e) {
        error_log('[send_test_email] Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_send_test_email', 'send_test_email');

/**
 * AJAX Handler to Schedule Mailchimp Campaign
 */
function schedule_mailchimp_campaign() {
    error_log('[schedule_mailchimp_campaign] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';
    error_log("[schedule_mailchimp_campaign] slug: $newsletter_slug, schedule: $schedule_datetime");

    if (empty($newsletter_slug) || empty($schedule_datetime)) {
        wp_send_json_error('Newsletter slug and schedule datetime are required.');
    }

    $site_timezone = wp_timezone();
    $local_dt = new DateTime($schedule_datetime, $site_timezone);
    $local_dt->setTimezone(new DateTimeZone('UTC'));
    $utc_schedule = $local_dt->format('Y-m-d\TH:i:s\Z');

    $campaign_id = get_option("newsletter_mailchimp_campaign_id_$newsletter_slug", '');
    if (empty($campaign_id)) {
        error_log('[schedule_mailchimp_campaign] No campaign found for this newsletter.');
        wp_send_json_error('No campaign found for this newsletter.');
    }

    try {
        $mailchimp = new Newsletter_Mailchimp_API();
        error_log("[schedule_mailchimp_campaign] Scheduling campaign $campaign_id at $utc_schedule");
        $result = $mailchimp->schedule_campaign($campaign_id, $utc_schedule);
        if (is_wp_error($result)) {
            error_log('[schedule_mailchimp_campaign] ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        } else {
            error_log('[schedule_mailchimp_campaign] Campaign scheduled successfully.');
            wp_send_json_success(['message' => 'Campaign scheduled successfully.']);
        }
    } catch (Exception $e) {
        error_log('[schedule_mailchimp_campaign] Exception: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_schedule_mailchimp_campaign', 'schedule_mailchimp_campaign');

/**
 * AJAX Handler to Send the Campaign Immediately (SEND NOW)
 */
function send_now_campaign() {
    error_log('[send_now_campaign] AJAX action triggered.');
    check_ajax_referer('mailchimp_campaign_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    error_log("[send_now_campaign] slug: $newsletter_slug");

    if (empty($newsletter_slug)) {
        error_log('[send_now_campaign] Missing newsletter slug.');
        wp_send_json_error('Newsletter slug is required.');
    }

    $mailchimp = new Newsletter_Mailchimp_API();

    // Gather data for campaign creation
    $subject_line  = get_option("newsletter_subject_line_$newsletter_slug", '');
    $campaign_name = get_option("newsletter_campaign_name_$newsletter_slug", '');
    $blocks        = get_option("newsletter_blocks_$newsletter_slug", []);

    if (empty($blocks)) {
        error_log('[send_now_campaign] No content blocks found.');
        wp_send_json_error('No content blocks found for newsletter.');
    }

    // Generate newsletter content
    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    if (empty($content)) {
        error_log('[send_now_campaign] Failed to generate content.');
        wp_send_json_error('Failed to generate newsletter content.');
    }

    // Create a new campaign
    error_log('[send_now_campaign] Creating a new campaign...');
    $campaign = $mailchimp->create_campaign($newsletter_slug, $subject_line, $campaign_name);
    if (is_wp_error($campaign)) {
        error_log('[send_now_campaign] ' . $campaign->get_error_message());
        wp_send_json_error($campaign->get_error_message());
    }

    error_log('[send_now_campaign] Setting campaign content before sending.');
    $content_result = $mailchimp->set_campaign_content($campaign['id'], $content);
    if (is_wp_error($content_result)) {
        error_log('[send_now_campaign] ' . $content_result->get_error_message());
        wp_send_json_error($content_result->get_error_message());
    }

    // Immediately send the campaign
    error_log('[send_now_campaign] Attempting to send the campaign now.');
    $send_result = $mailchimp->send_campaign($campaign['id']);
    if (is_wp_error($send_result)) {
        error_log('[send_now_campaign] ' . $send_result->get_error_message());
        wp_send_json_error($send_result->get_error_message());
    }

    error_log('[send_now_campaign] Campaign sent successfully.');
    wp_send_json_success('Campaign created and sent successfully.');
}

add_action('wp_ajax_send_now_campaign', 'send_now_campaign');
