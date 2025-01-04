<?php
// includes/ajax/ajax-save-blocks.php
if (!defined('ABSPATH')) exit;



function compare_block_content($new_block, $existing_block) {
    if ($new_block['type'] !== $existing_block['type']) {
        return false;
    }
    
    if ($new_block['type'] === 'wysiwyg') {
        $new_content = isset($new_block['wysiwyg']) ? trim(wp_kses_post($new_block['wysiwyg'])) : '';
        $existing_content = isset($existing_block['wysiwyg']) ? trim(wp_kses_post($existing_block['wysiwyg'])) : '';
        return $new_content === $existing_content;
    }
    
    if ($new_block['type'] === 'html') {
        $new_content = isset($new_block['html']) ? trim(wp_kses_post($new_block['html'])) : '';
        $existing_content = isset($existing_block['html']) ? trim(wp_kses_post($existing_block['html'])) : '';
        return $new_content === $existing_content;
    }
    
    return false;
}



function newsletter_handle_blocks_form_submission() {
    try {
        check_ajax_referer('save_blocks_action', 'security');
        
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        $is_auto_save = isset($_POST['is_auto_save']) && $_POST['is_auto_save'];
        
        error_log('[Debug Structure] AJAX Save - Starting save operation');
        error_log('[Debug Structure] AJAX Save - Auto-save: ' . ($is_auto_save ? 'yes' : 'no'));
        
        // Get existing blocks
        $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        error_log('[Debug Structure] AJAX Save - Existing blocks structure: ' . print_r($existing_blocks, true));
        
        // Get new blocks data and decode JSON if needed
        $blocks_data = isset($_POST['blocks']) ? $_POST['blocks'] : '[]';
        error_log('[Debug Structure] AJAX Save - Raw blocks data type: ' . gettype($blocks_data));
        
        // Handle both string and array inputs
        if (is_string($blocks_data)) {
            $blocks_json = stripslashes($blocks_data);
            $blocks = json_decode($blocks_json, true);
            error_log('[Debug Structure] AJAX Save - Decoded JSON blocks structure: ' . print_r($blocks, true));
        } else if (is_array($blocks_data)) {
            $blocks = $blocks_data;
            error_log('[Debug Structure] AJAX Save - Array blocks structure: ' . print_r($blocks, true));
        }

        $sanitized_blocks = [];
        foreach ($blocks as $index => $block) {
            if (!isset($block['type'])) continue;

            if ($block['type'] === 'wysiwyg') {
                error_log(sprintf(
                    '[Debug Structure] AJAX Save - Processing WYSIWYG block %d - Initial keys: %s, Content present: %s',
                    $index,
                    implode(', ', array_keys($block)),
                    isset($block['wysiwyg']) ? 'yes' : 'no'
                ));
            }

            // Basic block sanitization
            $sanitized_block = [
                'type' => sanitize_text_field($block['type']),
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                'show_title' => isset($block['show_title']) ? (bool)$block['show_title'] : false,
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0'
            ];

            // Get existing block
            $existing_block = isset($existing_blocks[$index]) ? $existing_blocks[$index] : null;

            // Handle different block types
            if ($block['type'] === 'wysiwyg') {
                error_log(sprintf(
                    '[Debug Structure] AJAX Save - WYSIWYG block %d - Before processing, has existing content: %s',
                    $index,
                    $existing_block && isset($existing_block['wysiwyg']) ? 'yes' : 'no'
                ));
                
                $sanitized_block = handle_wysiwyg_content($block, $existing_block, $is_auto_save);
                
                error_log(sprintf(
                    '[Debug Structure] AJAX Save - WYSIWYG block %d - After processing keys: %s, Has content: %s',
                    $index,
                    implode(', ', array_keys($sanitized_block)),
                    isset($sanitized_block['wysiwyg']) ? 'yes' : 'no'
                ));
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        error_log('[Debug Structure] AJAX Save - Final sanitized blocks structure: ' . print_r($sanitized_blocks, true));
        
        // Save blocks if changes detected or not auto-saving
        $save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
        $verify_save = get_option("newsletter_blocks_$newsletter_slug");
        error_log('[Debug Structure] AJAX Save - Verification retrieved structure: ' . print_r($verify_save, true));

        wp_send_json_success([
            'message' => 'Blocks saved successfully',
            'blocks' => $sanitized_blocks
        ]);
    } catch (Exception $e) {
        error_log('[Debug Structure] AJAX Save - Error: ' . $e->getMessage());
        wp_send_json_error('Error saving blocks: ' . $e->getMessage());
    }
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');

// Load block posts for initial state
function newsletter_load_initial_block_state() {
    check_ajax_referer('load_block_posts_nonce', 'security');
    
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';

    if (empty($newsletter_slug)) {
        wp_send_json_error('Missing newsletter slug');
        return;
    }

    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    
    if (!isset($blocks[$block_index])) {
        wp_send_json_error('Block not found');
        return;
    }

    $block = $blocks[$block_index];
    
    // Load content based on block type
    if ($block['type'] === 'wysiwyg') {
        $content = isset($block['wysiwyg']) ? wp_kses_post($block['wysiwyg']) : '';
        wp_send_json_success(['content' => $content, 'type' => 'wysiwyg']);
    } 
    else if ($block['type'] === 'html') {
        $content = isset($block['html']) ? wp_kses_post($block['html']) : '';
        wp_send_json_success(['content' => $content, 'type' => 'html']);
    }
    else if ($block['type'] === 'content') {
        $posts = isset($block['posts']) ? $block['posts'] : [];
        wp_send_json_success([
            'content' => $posts,
            'type' => 'content',
            'category' => isset($block['category']) ? $block['category'] : '',
            'date_range' => isset($block['date_range']) ? $block['date_range'] : '',
            'story_count' => isset($block['story_count']) ? $block['story_count'] : 'disable',
            'manual_override' => isset($block['manual_override']) ? (bool)$block['manual_override'] : false
        ]);
    }
    else {
        wp_send_json_error('Unsupported block type');
    }
}
add_action('wp_ajax_load_initial_block_state', 'newsletter_load_initial_block_state');

// Save block state independently
function newsletter_save_block_state() {
    check_ajax_referer('save_blocks_action', 'security');
    
    try {
        $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : -1;
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
        $block_type = isset($_POST['block_type']) ? sanitize_text_field($_POST['block_type']) : '';
        
        if ($block_index < 0 || empty($newsletter_slug) || empty($block_type)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        
        if (!isset($blocks[$block_index])) {
            wp_send_json_error('Block not found');
            return;
        }

        $block = $blocks[$block_index];
        $content_version = isset($_POST['content_version']) ? intval($_POST['content_version']) : 0;

        // Only update if version is newer or not versioned
        if ($block_type === 'wysiwyg') {
            $current_version = isset($block['content_version']) ? intval($block['content_version']) : 0;
            if ($content_version > $current_version) {
                $block['wysiwyg'] = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
                $block['content_version'] = $content_version;
                error_log("[Block Debug] Updating WYSIWYG content version $content_version");
            }
        }
        else if ($block_type === 'html') {
            $current_version = isset($block['content_version']) ? intval($block['content_version']) : 0;
            if ($content_version > $current_version) {
                $block['html'] = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
                $block['content_version'] = $content_version;
                error_log("[Block Debug] Updating HTML content version $content_version");
            }
        }
        else if ($block_type === 'content') {
            if (isset($_POST['posts']) && is_array($_POST['posts'])) {
                $block['posts'] = [];
                foreach ($_POST['posts'] as $post_id => $post_data) {
                    if (isset($post_data['checked']) && $post_data['checked']) {
                        $block['posts'][$post_id] = [
                            'checked' => '1',
                            'order' => isset($post_data['order']) ? sanitize_text_field($post_data['order']) : '0'
                        ];
                    }
                }
            }
        }

        $blocks[$block_index] = $block;
        $save_result = update_option("newsletter_blocks_$newsletter_slug", $blocks);

        if ($save_result) {
            wp_send_json_success([
                'message' => 'Block state saved successfully',
                'block' => $block
            ]);
        } else {
            wp_send_json_error('Failed to save block state');
        }
    } catch (Exception $e) {
        error_log("[Block State Save Error] " . $e->getMessage());
        wp_send_json_error('Error saving block state: ' . $e->getMessage());
    }
}
add_action('wp_ajax_save_block_state', 'newsletter_save_block_state');

// Clean up any temporary block data
function cleanup_temporary_block_data() {
    check_ajax_referer('save_blocks_action', 'security');
    
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
    
    if (empty($newsletter_slug)) {
        wp_send_json_error('Missing newsletter slug');
        return;
    }

    // Remove any temporary options
    $temp_options = [
        "newsletter_temp_blocks_$newsletter_slug",
        "newsletter_block_versions_$newsletter_slug"
    ];

    foreach ($temp_options as $option) {
        delete_option($option);
    }

    wp_send_json_success('Cleanup completed');
}
add_action('wp_ajax_cleanup_block_data', 'cleanup_temporary_block_data');