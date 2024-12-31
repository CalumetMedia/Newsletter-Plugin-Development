<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    try {
        check_ajax_referer('save_blocks_action', 'security');
        
        // Set reasonable limits for large operations
        set_time_limit(120);
        $current_limit = ini_get('memory_limit');
        if (intval($current_limit) < 256) {
            ini_set('memory_limit', '256M');
        }
        
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        $is_auto_save = isset($_POST['is_auto_save']) && $_POST['is_auto_save'];
        
        // Get existing blocks
        $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        error_log("[WYSIWYG Debug] Existing blocks from DB: " . print_r($existing_blocks, true));
        
        // Get new blocks data and decode JSON if needed
        $blocks_data = isset($_POST['blocks']) ? $_POST['blocks'] : '[]';
        error_log("[WYSIWYG Debug] Raw blocks data type: " . gettype($blocks_data));
        
        // Handle both string and array inputs
        if (is_string($blocks_data)) {
            $blocks_json = stripslashes($blocks_data);
            error_log("[WYSIWYG Debug] Raw blocks data received: " . $blocks_json);
            $blocks = json_decode($blocks_json, true);
        } else if (is_array($blocks_data)) {
            $blocks = $blocks_data;
            error_log("[WYSIWYG Debug] Blocks data already decoded");
        } else {
            error_log("[WYSIWYG Debug] Invalid blocks data type: " . gettype($blocks_data));
            wp_send_json_error([
                'message' => 'Invalid blocks data type',
                'debug_info' => [
                    'received_type' => gettype($blocks_data)
                ]
            ]);
            return;
        }
        
        if (json_last_error() !== JSON_ERROR_NONE && is_string($blocks_data)) {
            error_log("[WYSIWYG Debug] JSON decode error: " . json_last_error_msg());
            error_log("[WYSIWYG Debug] Raw blocks data that failed: " . $blocks_json);
            wp_send_json_error([
                'message' => 'Invalid JSON format for blocks data',
                'debug_info' => [
                    'error' => json_last_error_msg(),
                    'received_data' => $blocks_json,
                    'raw_post' => $_POST['blocks']
                ]
            ]);
            return;
        }
        
        if (!is_array($blocks)) {
            wp_send_json_error([
                'message' => 'Blocks data must be an array',
                'debug_info' => [
                    'received_type' => gettype($blocks),
                    'decoded_data' => $blocks
                ]
            ]);
            return;
        }

        error_log("[WYSIWYG Debug] Decoded blocks data: " . print_r($blocks, true));
        $sanitized_blocks = [];
        foreach ($blocks as $block) {
            if (!isset($block['type'])) {
                continue;
            }

            $sanitized_block = [
                'type' => sanitize_text_field($block['type']),
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                'show_title' => isset($block['show_title']) ? (int)$block['show_title'] : 1,
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0',
                'category' => isset($block['category']) ? sanitize_text_field($block['category']) : '',
                'date_range' => isset($block['date_range']) ? sanitize_text_field($block['date_range']) : '',
                'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable',
                'manual_override' => isset($block['manual_override']) ? (int)$block['manual_override'] : 0,
                'posts' => []
            ];

            // Handle posts for content blocks
            if ($block['type'] === 'content' && isset($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    if (isset($post_data['checked']) && $post_data['checked']) {
                        $sanitized_block['posts'][$post_id] = [
                            'checked' => '1',
                            'order' => isset($post_data['order']) ? sanitize_text_field($post_data['order']) : '0'
                        ];
                    }
                }
            }

            // Handle WYSIWYG blocks
            if ($block['type'] === 'wysiwyg') {
                error_log("[WYSIWYG Debug] Processing WYSIWYG block: " . print_r($block, true));
                
                // Get existing WYSIWYG content for this block
                $existing_content = '';
                if ($is_auto_save && !empty($existing_blocks)) {
                    foreach ($existing_blocks as $existing_block) {
                        if ($existing_block['type'] === 'wysiwyg' && 
                            $existing_block['title'] === $sanitized_block['title']) {
                            $existing_content = $existing_block['wysiwyg'];
                            error_log("[WYSIWYG Debug] Found existing content for block: " . $sanitized_block['title']);
                            break;
                        }
                    }
                }
                
                if (isset($block['wysiwyg'])) {
                    // Let WordPress handle initial unslashing, apply sanitization after
                    $content = $block['wysiwyg'];
                    
                    // If auto-saving and new content is empty or just a blank paragraph, preserve existing content
                    if ($is_auto_save && 
                        (!$content || trim($content) === '' || trim($content) === '<p></p>') && 
                        !empty($existing_content)) {
                        error_log("[WYSIWYG Debug] Auto-save with empty/blank content, preserving existing content");
                        $sanitized_block['wysiwyg'] = $existing_content;
                    } else {
                        if (!empty($content)) {
                            if (strpos($content, '<p>') === false) {
                                $content = wpautop($content);
                            }
                            $sanitized_block['wysiwyg'] = wp_kses_post($content);
                            error_log("[WYSIWYG Debug] Processed new content: " . $sanitized_block['wysiwyg']);
                        } else {
                            $sanitized_block['wysiwyg'] = '';
                        }
                    }
                } else {
                    error_log("[WYSIWYG Debug] WYSIWYG block missing content");
                    // If auto-saving and we have existing content, preserve it
                    if ($is_auto_save && !empty($existing_content)) {
                        $sanitized_block['wysiwyg'] = $existing_content;
                        error_log("[WYSIWYG Debug] Preserved existing content for missing content");
                    } else {
                        $sanitized_block['wysiwyg'] = '';
                    }
                }
            }

            // Handle HTML blocks
            if ($block['type'] === 'html' && isset($block['html'])) {
                $sanitized_block['html'] = wp_kses_post($block['html']);
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        // Merge with existing blocks if auto-saving
        if ($is_auto_save && !empty($existing_blocks)) {
            error_log("[WYSIWYG Debug] Auto-save detected, merging with existing blocks");
            $merged_blocks = [];
            
            // First, add all existing blocks to the merged array
            foreach ($existing_blocks as $existing_block) {
                $merged_blocks[] = $existing_block;
            }
            
            // Then, update or add new blocks
            foreach ($sanitized_blocks as $new_block) {
                $block_exists = false;
                
                // Look for matching block in merged blocks
                foreach ($merged_blocks as $key => $merged_block) {
                    if ($new_block['type'] === $merged_block['type'] && 
                        $new_block['title'] === $merged_block['title']) {
                        
                        // For WYSIWYG blocks, preserve content if new content is empty
                        if ($new_block['type'] === 'wysiwyg' && 
                            empty(trim($new_block['wysiwyg'])) && 
                            !empty($merged_block['wysiwyg'])) {
                            $new_block['wysiwyg'] = $merged_block['wysiwyg'];
                            error_log("[WYSIWYG Debug] Preserved existing content for block: " . $new_block['title']);
                        }
                        
                        $merged_blocks[$key] = $new_block;
                        $block_exists = true;
                        break;
                    }
                }
                
                // If block doesn't exist in merged blocks, add it
                if (!$block_exists) {
                    $merged_blocks[] = $new_block;
                }
            }
            
            $sanitized_blocks = array_values($merged_blocks);
            error_log("[WYSIWYG Debug] Final merged blocks: " . print_r($sanitized_blocks, true));
        }

        // Check if data has actually changed
        $current_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $save_result = false;
        
        if (serialize($current_blocks) !== serialize($sanitized_blocks)) {
            $save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
            error_log("[WYSIWYG Debug] Blocks changed, saving to DB");
            
            // Verify the save was successful
            $verify_save = get_option("newsletter_blocks_$newsletter_slug");
            $actually_saved = !empty($verify_save) && serialize($verify_save) === serialize($sanitized_blocks);
            
            if (!$actually_saved) {
                error_log("[WYSIWYG Debug] Save verification failed - data mismatch");
                $save_result = false;
            }
        } else {
            $save_result = true; // Data hasn't changed, consider it a success
            error_log("[WYSIWYG Debug] Blocks unchanged, skipping save");
        }

        if ($save_result) {
            error_log("[WYSIWYG Debug] Save operation successful");
            
            // Handle additional fields if not auto-saving
            if (!$is_auto_save) {
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
            }
            
            wp_send_json_success([
                'message' => 'Blocks saved successfully',
                'blocks' => $sanitized_blocks
            ]);
        } else {
            error_log("[WYSIWYG Debug] Failed to save blocks to DB");
            error_log("[WYSIWYG Debug] Current blocks in DB: " . print_r(get_option("newsletter_blocks_$newsletter_slug"), true));
            
            // Check if we can write to the options table
            global $wpdb;
            $test_option = 'newsletter_test_' . time();
            $test_result = add_option($test_option, 'test', '', 'no');
            if ($test_result) {
                delete_option($test_option);
            }
            
            wp_send_json_error([
                'message' => 'Failed to save blocks',
                'debug_info' => [
                    'can_write_options' => $test_result,
                    'mysql_error' => $wpdb->last_error
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("[WYSIWYG Debug] Exception: " . $e->getMessage());
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