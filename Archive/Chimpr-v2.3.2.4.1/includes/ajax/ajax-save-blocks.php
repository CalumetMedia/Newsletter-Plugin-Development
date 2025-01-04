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

            // Basic block sanitization first
            $sanitized_block = [
                'type' => isset($block['type']) ? sanitize_text_field($block['type']) : '',
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                'show_title' => isset($block['show_title']) ? (int)$block['show_title'] : 1,
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0'
            ];

            // Handle content blocks
            if ($block['type'] === 'content') {
                $sanitized_block['category'] = isset($block['category']) ? sanitize_text_field($block['category']) : '';
                $sanitized_block['date_range'] = isset($block['date_range']) ? sanitize_text_field($block['date_range']) : '';
                $sanitized_block['story_count'] = isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable';
                $sanitized_block['manual_override'] = isset($block['manual_override']) ? (int)$block['manual_override'] : 0;
                $sanitized_block['posts'] = [];

                if (isset($block['posts']) && is_array($block['posts'])) {
                    foreach ($block['posts'] as $post_id => $post_data) {
                        if (isset($post_data['checked']) && $post_data['checked']) {
                            $sanitized_block['posts'][$post_id] = [
                                'checked' => '1',
                                'order' => isset($post_data['order']) ? sanitize_text_field($post_data['order']) : '0'
                            ];
                        }
                    }
                }
                error_log("[Block Debug] Content block processed: " . print_r($sanitized_block, true));
            }

            // Handle WYSIWYG blocks
            if ($block['type'] === 'wysiwyg') {
                error_log("[WYSIWYG Debug] Processing WYSIWYG block: " . print_r($block, true));
                
                // Get existing block for content preservation
                $existing_block = null;
                if ($is_auto_save && !empty($existing_blocks)) {
                    foreach ($existing_blocks as $existing_block_item) {
                        if ($existing_block_item['type'] === 'wysiwyg' && 
                            $existing_block_item['title'] === $block['title']) {
                            $existing_block = $existing_block_item;
                            break;
                        }
                    }
                }
                
                $processed_block = handle_wysiwyg_content($block, $existing_block);
                $sanitized_block['wysiwyg'] = isset($processed_block['wysiwyg']) ? $processed_block['wysiwyg'] : '';
                error_log("[WYSIWYG Debug] Processed content length: " . strlen($sanitized_block['wysiwyg']));
            }

            // Handle HTML blocks
            if ($block['type'] === 'html') {
                error_log("[Block Debug] Processing HTML block - Title: " . (isset($block['title']) ? $block['title'] : 'untitled'));
                
                // Get existing block for content preservation
                $existing_block = null;
                if ($is_auto_save && !empty($existing_blocks)) {
                    foreach ($existing_blocks as $existing_block_item) {
                        if ($existing_block_item['type'] === 'html' && 
                            $existing_block_item['title'] === $block['title']) {
                            $existing_block = $existing_block_item;
                            break;
                        }
                    }
                }
                
                $processed_block = handle_html_content($block, $existing_block);
                $sanitized_block['html'] = isset($processed_block['html']) ? $processed_block['html'] : '';
                error_log("[Block Debug] Processed HTML content length: " . strlen($sanitized_block['html']));
            }

            // Handle PDF Link blocks
            if ($block['type'] === 'pdf_link') {
                error_log("[Block Debug] Processing PDF Link block - Title: " . (isset($block['title']) ? $block['title'] : 'untitled'));
                // PDF Link blocks only use common fields, no additional processing needed
            }

            // Log block before adding to sanitized blocks
            error_log("[Block Debug] Adding block to sanitized_blocks - Type: " . $block['type']);
            error_log("[Block Debug] Block details: " . print_r($sanitized_block, true));
            $sanitized_blocks[] = $sanitized_block;

            // Log sanitized blocks array size
            error_log("[Block Debug] Current sanitized_blocks count: " . count($sanitized_blocks));
        }

        // Log final blocks array before any merging
        error_log("[Block Debug] Final sanitized blocks before merge/save:");
        error_log(print_r($sanitized_blocks, true));

        // Merge with existing blocks if auto-saving
        if ($is_auto_save && !empty($existing_blocks)) {
            error_log("[Block Debug] Auto-save detected");
            error_log("[Block Debug] Existing blocks count: " . count($existing_blocks));
            error_log("[Block Debug] New blocks count: " . count($sanitized_blocks));
            
            // Check if blocks are actually different
            if (serialize($existing_blocks) !== serialize($sanitized_blocks)) {
                error_log("[Block Debug] Blocks differ - Details:");
                error_log("Existing: " . print_r($existing_blocks, true));
                error_log("New: " . print_r($sanitized_blocks, true));
                $merged_blocks = $sanitized_blocks;
            } else {
                error_log("[Block Debug] Blocks identical - keeping existing");
                $merged_blocks = $existing_blocks;
            }
            
            $sanitized_blocks = array_values($merged_blocks);
            error_log("[Block Debug] Final block count after merge: " . count($sanitized_blocks));
        }

        // Check if data has actually changed
        $current_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $save_result = false;
        
        // Compare blocks with special handling for WYSIWYG content
        $blocks_changed = false;
        if (count($current_blocks) !== count($sanitized_blocks)) {
            $blocks_changed = true;
            error_log("[Block Debug] Block count changed - Current: " . count($current_blocks) . ", New: " . count($sanitized_blocks));
        } else {
            foreach ($sanitized_blocks as $index => $new_block) {
                if (!isset($current_blocks[$index])) {
                    $blocks_changed = true;
                    error_log("[Block Debug] New block at index $index");
                    break;
                }
                
                $current_block = $current_blocks[$index];
                
                // Compare titles first
                if ($new_block['title'] !== $current_block['title']) {
                    $blocks_changed = true;
                    error_log("[Block Debug] Title changed at index $index - Old: " . $current_block['title'] . ", New: " . $new_block['title']);
                    break;
                }
                
                if ($new_block['type'] !== $current_block['type']) {
                    $blocks_changed = true;
                    error_log("[Block Debug] Block type changed at index $index");
                    break;
                }
                
                // For WYSIWYG blocks, compare normalized content
                if ($new_block['type'] === 'wysiwyg') {
                    $current_content = isset($current_block['wysiwyg']) ? trim(wp_kses_post($current_block['wysiwyg'])) : '';
                    $new_content = isset($new_block['wysiwyg']) ? trim(wp_kses_post($new_block['wysiwyg'])) : '';
                    
                    // If auto-saving and new content is empty but we have existing content, preserve it
                    if ($is_auto_save && empty($new_content) && !empty($current_content)) {
                        $sanitized_blocks[$index]['wysiwyg'] = $current_content;
                        error_log("[Block Debug] Preserved existing WYSIWYG content during auto-save");
                        continue;
                    }
                    
                    // Compare normalized content
                    if ($current_content !== $new_content) {
                        $blocks_changed = true;
                        error_log("[Block Debug] WYSIWYG content changed at index $index");
                        error_log("[Block Debug] Current content length: " . strlen($current_content));
                        error_log("[Block Debug] New content length: " . strlen($new_content));
                        break;
                    }
                }
                // For HTML blocks, compare normalized content
                else if ($new_block['type'] === 'html') {
                    $current_content = isset($current_block['html']) ? trim(wp_kses_post($current_block['html'])) : '';
                    $new_content = isset($new_block['html']) ? trim(wp_kses_post($new_block['html'])) : '';
                    
                    // If auto-saving and new content is empty but we have existing content, preserve it
                    if ($is_auto_save && empty($new_content) && !empty($current_content)) {
                        $sanitized_blocks[$index]['html'] = $current_content;
                        error_log("[Block Debug] Preserved existing HTML content during auto-save");
                        continue;
                    }
                    
                    // Compare normalized content
                    if ($current_content !== $new_content) {
                        $blocks_changed = true;
                        error_log("[Block Debug] HTML content changed at index $index");
                        error_log("[Block Debug] Current content length: " . strlen($current_content));
                        error_log("[Block Debug] New content length: " . strlen($new_content));
                        break;
                    }
                }
                else {
                    // For non-WYSIWYG blocks, compare all fields except posts array order
                    $current_block_compare = $current_block;
                    $new_block_compare = $new_block;
                    
                    // Sort posts arrays if they exist to ensure consistent comparison
                    if (isset($current_block_compare['posts']) && is_array($current_block_compare['posts'])) {
                        ksort($current_block_compare['posts']);
                    }
                    if (isset($new_block_compare['posts']) && is_array($new_block_compare['posts'])) {
                        ksort($new_block_compare['posts']);
                    }
                    
                    if (serialize($current_block_compare) !== serialize($new_block_compare)) {
                        $blocks_changed = true;
                        error_log("[Block Debug] Block changed at index $index");
                        break;
                    }
                }
            }
        }
        
        if ($blocks_changed) {
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

                if (isset($_POST['header_template'])) {
                    update_option(
                        "newsletter_header_template_$newsletter_slug", 
                        sanitize_text_field(wp_unslash($_POST['header_template']))
                    );
                }

                if (isset($_POST['footer_template'])) {
                    update_option(
                        "newsletter_footer_template_$newsletter_slug", 
                        sanitize_text_field(wp_unslash($_POST['footer_template']))
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