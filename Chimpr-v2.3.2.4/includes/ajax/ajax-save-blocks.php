<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];
    $sanitized_blocks = [];

    foreach ($blocks as $index => $block) {
        $sanitized_block = [
            'type' => sanitize_text_field($block['type']),
            'title' => sanitize_text_field($block['title']),
            'show_title' => isset($block['show_title']),
            'template_id' => sanitize_text_field($block['template_id']),
        ];

        // Handle content based on block type
        switch ($block['type']) {
            case 'content':
                $sanitized_block['category'] = intval($block['category']);
                $sanitized_block['date_range'] = intval($block['date_range']);
                $sanitized_block['story_count'] = sanitize_text_field($block['story_count']);
                $sanitized_block['manual_override'] = !empty($block['manual_override']);
                $sanitized_block['posts'] = [];
                
                if (!empty($block['posts']) && is_array($block['posts'])) {
                    foreach ($block['posts'] as $post_id => $post_data) {
                        $sanitized_block['posts'][intval($post_id)] = [
                            'selected' => !empty($post_data['selected']) ? 1 : 0,
                            'order' => isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX
                        ];
                    }
                }
                break;

            case 'html':
                $sanitized_block['html'] = wp_kses_post($block['html']);
                break;

            case 'wysiwyg':
                $content = wp_unslash($block['wysiwyg']);
                if (!empty($content) && strpos($content, '<p>') === false) {
                    $content = wpautop($content);
                }
                $sanitized_block['wysiwyg'] = wp_kses_post($content);
                break;
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
    wp_send_json_success('Blocks saved successfully');
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');