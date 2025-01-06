<?php
if (!defined('ABSPATH')) exit;

// Add AJAX handler for debug logging
add_action('wp_ajax_log_reset_debug', 'handle_reset_debug_logging');
function handle_reset_debug_logging() {
    error_log('[Reset Flow Debug] AJAX handler triggered at: ' . date('Y-m-d H:i:s.u'));
    error_log('[Reset Flow Debug] Request method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('[Reset Flow Debug] Available POST keys: ' . print_r(array_keys($_POST), true));

    // Basic nonce check
    if (!isset($_POST['security'])) {
        error_log('[Reset Flow Debug] Security nonce missing. Full POST data: ' . print_r($_POST, true));
        wp_die();
    }
    
    if (!wp_verify_nonce($_POST['security'], 'save_blocks_action')) {
        error_log('[Reset Flow Debug] Security check failed');
        wp_die();
    }

    $log_type = isset($_POST['log_type']) ? sanitize_text_field($_POST['log_type']) : '';
    switch($log_type) {
        case 'debug_data':
            error_log('[Reset Flow Debug] Newsletter Data State: ' . (isset($_POST['available_data']) ? $_POST['available_data'] : 'none'));
            break;
        case 'editor_state':
            error_log('[Reset Flow Debug] Editor State: ' . (isset($_POST['editor_data']) ? $_POST['editor_data'] : 'none'));
            break;
        case 'form_state':
            error_log('[Reset Flow Debug] Form State: ' . (isset($_POST['form_data']) ? $_POST['form_data'] : 'none'));
            break;
    }
    
    wp_send_json_success();
}

function newsletter_stories_handle_form_submission() {
    // Basic initial debugging
    file_put_contents(WP_CONTENT_DIR . '/debug.txt', 'Form handler called at: ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    error_log('NEWSLETTER DEBUG: Form handler called at: ' . date('Y-m-d H:i:s'));
    error_log('NEWSLETTER DEBUG: REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('NEWSLETTER DEBUG: POST data keys: ' . print_r(array_keys($_POST), true));
    
    // Make sure it's a POST with our nonce
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocks_nonce'])) {
        error_log('NEWSLETTER DEBUG: Inside main POST condition');
        error_log('NEWSLETTER DEBUG: Full POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
            error_log('NEWSLETTER DEBUG: Nonce verification failed');
            wp_die(__('Security check failed.', 'newsletter'));
        }

        // Grab the slug
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        error_log('NEWSLETTER DEBUG: Newsletter slug: ' . $newsletter_slug);

        // Save target tag if present
        if (isset($_POST['target_tag'])) {
            $target_tag = sanitize_text_field($_POST['target_tag']);
            error_log('[Form Handler] Saving target_tag: ' . $target_tag);
            update_option("newsletter_target_tag_$newsletter_slug", $target_tag);
            
            // Verify save
            $saved_tag = get_option("newsletter_target_tag_$newsletter_slug");
            error_log('[Form Handler] Verified saved target_tag: ' . $saved_tag);
        } else {
            error_log('[Form Handler] No target_tag found in POST data');
        }

        // Check if user reset blocks
        if (isset($_POST['reset_blocks'])) {
            error_log('[Reset Flow] Reset blocks requested');
            error_log('[Reset Flow] POST data structure: ' . print_r(array_keys($_POST), true));
            error_log('[Reset Flow] Reset flag value: ' . print_r($_POST['reset_blocks'], true));
            error_log('[Reset Flow] Raw POST blocks data: ' . print_r($_POST['blocks'], true));
        }

        // =====================
        // 1. Handle Blocks
        // =====================
        if (isset($_POST['blocks'])) {
            $blocks = wp_unslash($_POST['blocks']);
            error_log('[Form Handler] Processing blocks: ' . print_r($blocks, true));

            $sanitized_blocks = [];
            foreach ($blocks as $index => $block) {
                // Debug WYSIWYG
                if (isset($block['type']) && $block['type'] === 'wysiwyg') {
                    error_log(sprintf(
                        '[Reset Flow] WYSIWYG Block %d - Title: %s, Content Length: %d',
                        $index,
                        isset($block['title']) ? $block['title'] : 'no title',
                        isset($block['wysiwyg']) ? strlen($block['wysiwyg']) : 0
                    ));
                }

                // Sanitize each block
                $sanitized_block = [
                    'type'            => isset($block['type']) ? sanitize_text_field($block['type']) : '',
                    'title'           => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                    'template_id'     => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0',
                    'show_title'      => isset($block['show_title']) ? (int)$block['show_title'] : 1,
                    'date_range'      => isset($block['date_range']) ? sanitize_text_field($block['date_range']) : '',
                    'story_count'     => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : '',
                    'manual_override' => isset($block['manual_override']) ? (int)$block['manual_override'] : 0,
                    'category'        => isset($block['category']) ? sanitize_text_field($block['category']) : '',
                    'posts'           => isset($block['posts']) ? $block['posts'] : []
                ];

                // Preserve HTML for HTML block
                if ($sanitized_block['type'] === 'html') {
                    $sanitized_block['html'] = isset($block['html']) ? $block['html'] : '';
                }

                // Preserve WYSIWYG content for WYSIWYG block
                if ($sanitized_block['type'] === 'wysiwyg') {
                    $sanitized_block['wysiwyg'] = isset($block['wysiwyg']) ? $block['wysiwyg'] : '';
                }

                $sanitized_blocks[] = $sanitized_block;
            }

            // Update blocks option
            update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

            // Optional verification
            $verify_save = get_option("newsletter_blocks_$newsletter_slug");
            if (!empty($verify_save)) {
                foreach ($verify_save as $index => $b) {
                    if (isset($b['type']) && $b['type'] === 'wysiwyg') {
                        error_log(sprintf(
                            '[Reset Flow] Saved WYSIWYG Block %d - Title: %s, Content Length: %d',
                            $index,
                            isset($b['title']) ? $b['title'] : 'no title',
                            isset($b['wysiwyg']) ? strlen($b['wysiwyg']) : 0
                        ));
                    }
                }
            }
        }

        // Finally, redirect with success
        wp_redirect(add_query_arg([
            'page'    => 'newsletter-stories-' . $newsletter_slug,
            'message' => 'blocks_saved'
        ], admin_url('admin.php')));
        exit;
    } else {
        error_log('NEWSLETTER DEBUG: Initial conditions not met');
        error_log('NEWSLETTER DEBUG: REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
        error_log('NEWSLETTER DEBUG: blocks_nonce present: ' . (isset($_POST['blocks_nonce']) ? 'yes' : 'no'));
    }
}
