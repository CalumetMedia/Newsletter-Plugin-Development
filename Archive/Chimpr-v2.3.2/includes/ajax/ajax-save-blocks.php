<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];
    $sanitized_blocks = [];

    foreach ($blocks as $block) {
        // Basic block data sanitization
        $sanitized_block = [
            'type' => sanitize_text_field($block['type']),
            'title' => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
            'show_title' => isset($block['show_title']),
            'date_range' => isset($block['date_range']) ? intval($block['date_range']) : 7
        ];

        // Handle different block types
        if ($block['type'] === 'content') {
            $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['posts'] = [];
            
            if (!empty($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    if (isset($post_data['selected']) && $post_data['selected'] == '1') {
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => isset($post_data['order']) ? intval($post_data['order']) : 0
                        ];
                    }
                }
            }
        } elseif ($block['type'] === 'wysiwyg') {
            // Handle WYSIWYG content
            $content = isset($block['wysiwyg']) ? $block['wysiwyg'] : '';
            
            // Remove any WordPress auto-added slashes
            $content = wp_unslash($content);
            
            // Add paragraphs if needed
            if (!empty($content) && strpos($content, '<p>') === false) {
                $content = wpautop($content);
            }
            
            // Allow HTML but prevent XSS
            $sanitized_block['wysiwyg'] = wp_kses_post($content);
            
        } elseif ($block['type'] === 'html') {
            // Handle HTML content
            $html_content = isset($block['html']) ? $block['html'] : '';
            $html_content = wp_unslash($html_content);
            $sanitized_block['html'] = wp_kses_post($html_content);
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    // Save blocks
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    // Handle additional fields
    if (isset($_POST['subject_line'])) {
        update_option(
            "newsletter_subject_line_$newsletter_slug", 
            sanitize_text_field(wp_unslash($_POST['subject_line']))
        );
    }

    if (isset($_POST['custom_header'])) {
        update_option(
            "newsletter_custom_header_$newsletter_slug", 
            wp_kses_post(wp_unslash($_POST['custom_header']))
        );
    }

    if (isset($_POST['custom_footer'])) {
        update_option(
            "newsletter_custom_footer_$newsletter_slug", 
            wp_kses_post(wp_unslash($_POST['custom_footer']))
        );
    }

    wp_send_json_success('Blocks saved successfully');
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');