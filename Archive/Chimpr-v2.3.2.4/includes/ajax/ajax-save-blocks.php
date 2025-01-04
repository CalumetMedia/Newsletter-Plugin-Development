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

        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                continue;
            }

            // Basic block data sanitization
            $sanitized_block = [
                'type' => isset($block['type']) ? sanitize_text_field($block['type']) : (isset($existing_blocks[$index]['type']) ? $existing_blocks[$index]['type'] : ''),
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : (isset($existing_blocks[$index]['title']) ? $existing_blocks[$index]['title'] : ''),
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : (isset($existing_blocks[$index]['template_id']) ? $existing_blocks[$index]['template_id'] : 'default'),
                'show_title' => isset($block['show_title']),
                'date_range' => isset($block['date_range']) ? intval($block['date_range']) : (isset($existing_blocks[$index]['date_range']) ? $existing_blocks[$index]['date_range'] : 7),
                'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : (isset($existing_blocks[$index]['story_count']) ? $existing_blocks[$index]['story_count'] : 'disable'),
                'manual_override' => isset($block['manual_override']) && $block['manual_override'] == '1'
            ];

            // Handle different block types
            if ($block['type'] === 'content' || (isset($existing_blocks[$index]['type']) && $existing_blocks[$index]['type'] === 'content')) {
                $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : (isset($existing_blocks[$index]['category']) ? $existing_blocks[$index]['category'] : 0);
                
                // Initialize posts array
                $sanitized_block['posts'] = [];
                
                // Process post data
                if (isset($block['posts']) && is_array($block['posts'])) {
                    foreach ($block['posts'] as $post_id => $post_data) {
                        // Ensure post_id is valid
                        $post_id = sanitize_text_field($post_id);
                        
                        // Get selection state (handle both selected and checked flags)
                        $is_selected = false;
                        if (isset($post_data['selected'])) {
                            $is_selected = $post_data['selected'] == 1 || $post_data['selected'] === true;
                        } elseif (isset($post_data['checked'])) {
                            $is_selected = $post_data['checked'] == '1' || $post_data['checked'] === true;
                        }
                        
                        // Get order value
                        $order = isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX;
                        
                        // Store post data
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => $is_selected ? 1 : 0,
                            'order' => $order
                        ];
                    }
                }
                
                // If we're in auto-selection mode (not manual override), select top N posts
                if (!$sanitized_block['manual_override'] && $sanitized_block['story_count'] !== 'disable') {
                    $story_count = intval($sanitized_block['story_count']);
                    $posts_by_order = $sanitized_block['posts'];
                    uasort($posts_by_order, function($a, $b) {
                        return $a['order'] - $b['order'];
                    });
                    
                    $count = 0;
                    foreach ($posts_by_order as $post_id => $post_data) {
                        $sanitized_block['posts'][$post_id]['selected'] = ($count < $story_count) ? 1 : 0;
                        $count++;
                    }
                }
            } elseif ($block['type'] === 'wysiwyg' || (isset($existing_blocks[$index]['type']) && $existing_blocks[$index]['type'] === 'wysiwyg')) {
                // Handle WYSIWYG content
                $content = isset($block['wysiwyg']) ? $block['wysiwyg'] : (isset($existing_blocks[$index]['wysiwyg']) ? $existing_blocks[$index]['wysiwyg'] : '');
                
                // Remove any WordPress auto-added slashes
                $content = wp_unslash($content);
                
                // Add paragraphs if needed
                if (!empty($content) && strpos($content, '<p>') === false) {
                    $content = wpautop($content);
                }
                
                // Allow HTML but prevent XSS
                $sanitized_block['wysiwyg'] = wp_kses_post($content);
                
            } elseif ($block['type'] === 'html' || (isset($existing_blocks[$index]['type']) && $existing_blocks[$index]['type'] === 'html')) {
                // Handle HTML content
                $html_content = isset($block['html']) ? $block['html'] : (isset($existing_blocks[$index]['html']) ? $existing_blocks[$index]['html'] : '');
                $html_content = wp_unslash($html_content);
                $sanitized_block['html'] = wp_kses_post($html_content);
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        // Save blocks
        $option_name = "newsletter_blocks_$newsletter_slug";
        
        // Delete and save fresh
        delete_option($option_name);
        $save_result = add_option($option_name, $sanitized_blocks, '', 'no');
        if (!$save_result) {
            $save_result = update_option($option_name, $sanitized_blocks, 'no');
        }
        
        error_log('Newsletter Save - Final save result: ' . ($save_result ? 'Success' : 'Failed'));
        error_log('Newsletter Save - Saved data structure: ' . print_r($sanitized_blocks, true));
        
        if ($save_result) {
            // Verify the save
            $saved_value = get_option($option_name, []);
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
                    'option_name' => $option_name
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