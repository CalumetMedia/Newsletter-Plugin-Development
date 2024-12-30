<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    try {
        check_ajax_referer('save_blocks_action', 'security');

        // Get the newsletter slug
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        
        // Debug logging - only log critical data
        error_log('Newsletter Save - Received blocks data structure: ' . print_r($_POST['blocks'], true));
        
        // Get existing blocks to preserve any data not in the current form submission
        $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        
        // Get new blocks data and unslash it
        $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];
        
        // Ensure blocks is an array
        if (!is_array($blocks)) {
            error_log('Blocks data is not an array: ' . gettype($blocks));
            wp_send_json_error([
                'message' => 'Blocks data must be an array',
                'debug_info' => [
                    'received_type' => gettype($blocks),
                    'received_data' => $blocks
                ]
            ]);
            return;
        }

        $sanitized_blocks = [];
        foreach ($blocks as $block) {
            $sanitized_block = [
                'type' => isset($block['type']) ? sanitize_text_field($block['type']) : '',
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0',
                'show_title' => isset($block['show_title']) ? (int)$block['show_title'] : 1,
                'date_range' => isset($block['date_range']) ? sanitize_text_field($block['date_range']) : '',
                'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : '',
                'manual_override' => isset($block['manual_override']) ? (int)$block['manual_override'] : 0,
                'category' => isset($block['category']) ? sanitize_text_field($block['category']) : '',
                'posts' => isset($block['posts']) ? $block['posts'] : []
            ];

            // Handle WYSIWYG content
            if ($block['type'] === 'wysiwyg' && isset($block['wysiwyg'])) {
                $sanitized_block['wysiwyg'] = wp_kses_post(wp_unslash($block['wysiwyg']));
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        // Save the sanitized blocks
        $save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
        error_log('Newsletter Save - Final save result: ' . ($save_result ? 'Success' : 'Failed'));
        error_log('Newsletter Save - Saved data structure: ' . print_r($sanitized_blocks, true));
        
        if ($save_result) {
            // Verify the save
            $saved_value = get_option("newsletter_blocks_$newsletter_slug", []);
            error_log('Saved value after save: ' . print_r($saved_value, true));
            
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

            wp_send_json_success([
                'message' => 'Blocks saved successfully',
                'blocks' => $sanitized_blocks
            ]);
        } else {
            // Check if we can write to the options table
            global $wpdb;
            $test_option = 'newsletter_test_' . time();
            $test_result = add_option($test_option, 'test', '', 'no');
            if ($test_result) {
                delete_option($test_option);
                error_log('Test option write successful');
            } else {
                error_log('Test option write failed');
            }
            
            wp_send_json_error([
                'message' => 'Failed to save blocks',
                'debug_info' => [
                    'newsletter_slug' => $newsletter_slug,
                    'block_count' => count($sanitized_blocks),
                    'can_write_options' => $test_result,
                    'option_name' => "newsletter_blocks_$newsletter_slug"
                ]
            ]);
        }
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error: ' . $e->getMessage(),
            'debug_info' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');