<?php
// includes/ajax/ajax-save-blocks.php
if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler to Save Newsletter Blocks
 */
function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';

    if (isset($_POST['target_tags']) && is_array($_POST['target_tags'])) {
        $target_tags = array_map('sanitize_text_field', $_POST['target_tags']);
        update_option("newsletter_target_tags_$newsletter_slug", $target_tags);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'newsletter'));
    }

    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $sanitized_blocks = [];

    foreach ($blocks as $block) {
        $sanitized_block = [
            'type' => sanitize_text_field($block['type']),
            'title' => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
            'show_title' => isset($block['show_title'])
        ];

        if ($sanitized_block['type'] === 'content') {
            $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['post_count'] = isset($block['post_count']) ? intval($block['post_count']) : 5;
            $sanitized_block['date_range'] = isset($block['date_range']) ? intval($block['date_range']) : 7;
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
        } elseif ($sanitized_block['type'] === 'html') {
            $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
} elseif ($sanitized_block['type'] === 'wysiwyg') {
    $sanitized_block['wysiwyg'] = wp_kses_post(stripslashes($block['wysiwyg'] ?? ''));
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

    wp_send_json_success(__('Blocks have been saved.', 'newsletter'));
}
add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');
