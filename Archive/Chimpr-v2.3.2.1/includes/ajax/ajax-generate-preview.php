<?php
// includes/ajax/ajax-generate-preview.php
if (!defined('ABSPATH')) exit;

// Hook up AJAX handlers for both logged in and non-logged in users
add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');
add_action('wp_ajax_nopriv_generate_preview', 'newsletter_generate_preview');

/**
 * AJAX Handler to Generate Preview
 */
function newsletter_generate_preview() {
    if (!isset($_POST['security'])) {
        error_log('Security nonce is missing in preview request');
        wp_send_json_error('Security nonce is missing');
        return;
    }

    if (!check_ajax_referer('generate_preview_nonce', 'security', false)) {
        error_log('Security nonce verification failed for preview request');
        wp_send_json_error('Security check failed');
        return;
    }

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';

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
            'show_title'  => isset($block['show_title']),
            'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable',
            'date_range'  => isset($block['date_range']) ? intval($block['date_range']) : 7
        ];

        if ($sanitized_block['type'] === 'content') {
            $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['post_count'] = isset($block['post_count']) ? intval($block['post_count']) : 5;
            $sanitized_block['date_range'] = isset($block['date_range']) ? intval($block['date_range']) : 7;
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
        } else if ($sanitized_block['type'] === 'html' || $sanitized_block['type'] === 'wysiwyg') {
            $sanitized_block['content'] = wp_kses_post($block['content'] ?? '');
        }
        
        $sanitized_blocks[] = $sanitized_block;
    }

    // Generate preview HTML
    $preview_html = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);

    // Debug log the preview HTML
    error_log('Preview HTML generated: ' . substr($preview_html, 0, 500) . '...');

    wp_send_json_success(['html' => $preview_html]);
}
