<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $subject_line = isset($_POST['subject_line']) ? sanitize_text_field($_POST['subject_line']) : '';
    $custom_header = isset($_POST['custom_header']) ? wp_kses_post($_POST['custom_header']) : '';
    $custom_footer = isset($_POST['custom_footer']) ? wp_kses_post($_POST['custom_footer']) : '';

    // Ensure blocks is an array
    if (!is_array($blocks)) {
        $blocks = json_decode(stripslashes($blocks), true);
        if (!is_array($blocks)) {
            wp_send_json_error('Invalid blocks data format');
            return;
        }
    }

    // Add error logging
    error_log('Received blocks data: ' . print_r($blocks, true));

    // Sanitize and validate each block
    $sanitized_blocks = [];
    foreach ($blocks as $index => $block) {
        if (!is_array($block)) {
            error_log('Block at index ' . $index . ' is not an array');
            continue;
        }

        $sanitized_block = [
            'type' => isset($block['type']) ? sanitize_text_field($block['type']) : 'content',
            'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
            'show_title' => isset($block['show_title']) ? (bool)$block['show_title'] : true,
            'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default',
            'category' => isset($block['category']) ? intval($block['category']) : 0,
            'date_range' => isset($block['date_range']) ? intval($block['date_range']) : 7,
            'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable',
            'manual_override' => isset($block['manual_override']) ? (bool)$block['manual_override'] : false,
            'posts' => []
        ];

        // Handle WYSIWYG content
        if ($block['type'] === 'wysiwyg' && isset($block['wysiwyg'])) {
            $sanitized_block['wysiwyg'] = wp_kses_post($block['wysiwyg']);
        }

        // Handle HTML content
        if ($block['type'] === 'html' && isset($block['html'])) {
            $sanitized_block['html'] = wp_kses_post($block['html']);
        }

        // Handle posts if they exist
        if (isset($block['posts']) && is_array($block['posts'])) {
            foreach ($block['posts'] as $post_id => $post_data) {
                if (!is_array($post_data)) continue;
                $sanitized_block['posts'][$post_id] = [
                    'selected' => isset($post_data['selected']) ? (bool)$post_data['selected'] : false,
                    'order' => isset($post_data['order']) ? intval($post_data['order']) : 0
                ];
            }
        }

        $sanitized_blocks[$index] = $sanitized_block;
    }

    try {
        // Save all the data
        update_option("newsletter_subject_line_$newsletter_slug", $subject_line);
        update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
        update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
        
        // Save the blocks
        $update_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
        
        if ($update_result === false) {
            error_log('Failed to update blocks option: newsletter_blocks_' . $newsletter_slug);
            wp_send_json_error('Failed to save blocks to database');
            return;
        }
        
        wp_send_json_success();
    } catch (Exception $e) {
        error_log('Exception while saving newsletter data: ' . $e->getMessage());
        wp_send_json_error('Server error while saving newsletter data');
    }
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');