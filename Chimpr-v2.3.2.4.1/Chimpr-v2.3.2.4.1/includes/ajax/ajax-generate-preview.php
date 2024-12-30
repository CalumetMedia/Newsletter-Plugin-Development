<?php
// includes/ajax/ajax-generate-preview.php
if (!defined('ABSPATH')) exit;

if (!function_exists('newsletter_generate_preview')):

function newsletter_generate_preview() {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error('Invalid request method');
        return;
    }

    // Set time limit for preview generation
    set_time_limit(120); // 2 minutes
    
    // Increase memory limit if needed
    $current_limit = ini_get('memory_limit');
    if (intval($current_limit) < 256) {
        ini_set('memory_limit', '256M');
    }

    // Enable output compression if not already enabled
    if (!ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'On');
    }

    static $is_generating = false;
    if ($is_generating) {
        wp_send_json_error('Preview generation already in progress');
        return;
    }
    $is_generating = true;

    // Set a reasonable execution time limit
    $start_time = microtime(true);
    $timeout_seconds = 90; // 1.5 minutes timeout

    try {
        check_ajax_referer('generate_preview_nonce', 'security');

        // Validate required fields
        if (!isset($_POST['newsletter_slug'])) {
            wp_send_json_error('Missing newsletter slug');
            return;
        }

        $newsletter_slug = sanitize_text_field($_POST['newsletter_slug']);
        if (empty($newsletter_slug)) {
            wp_send_json_error('Invalid newsletter slug');
            return;
        }
        
        // Get both saved blocks and current selections
        $saved_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        if (!is_array($saved_blocks)) {
            error_log('Invalid saved blocks format in database');
            wp_send_json_error('Invalid blocks format in database');
            return;
        }

        // Validate and decode saved selections
        $saved_selections = [];
        if (isset($_POST['saved_selections'])) {
            $decoded = json_decode(stripslashes($_POST['saved_selections']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Preview generation - JSON decode error: ' . json_last_error_msg());
                wp_send_json_error('Invalid saved selections format');
                return;
            }
            if (!is_array($decoded)) {
                error_log('Preview generation - Invalid saved selections structure');
                wp_send_json_error('Invalid saved selections structure');
                return;
            }
            $saved_selections = $decoded;
        }

        error_log('Saved selections: ' . print_r($saved_selections, true));

        // Merge saved selections into blocks with validation
        foreach ($saved_selections as $block_index => $block_data) {
            if (!isset($saved_blocks[$block_index])) {
                continue; // Skip if block doesn't exist
            }
            
            // Handle WYSIWYG content
            if (isset($saved_blocks[$block_index]['type']) && $saved_blocks[$block_index]['type'] === 'wysiwyg') {
                if (isset($block_data['wysiwyg'])) {
                    $saved_blocks[$block_index]['wysiwyg'] = wp_kses_post(wp_unslash($block_data['wysiwyg']));
                } else {
                    // Preserve empty WYSIWYG blocks
                    $saved_blocks[$block_index]['wysiwyg'] = '';
                }
                continue; // Skip post processing for WYSIWYG blocks
            }
            
            if (!isset($block_data['selections']) || !is_array($block_data['selections'])) {
                continue; // Skip if selections are invalid
            }

            $saved_blocks[$block_index]['posts'] = [];
            foreach ($block_data['selections'] as $post_id => $selection) {
                if (!is_array($selection)) {
                    continue; // Skip invalid selection
                }

                // Only store checked posts
                if (isset($selection['checked']) && $selection['checked'] === '1') {
                    $saved_blocks[$block_index]['posts'][$post_id] = [
                        'checked' => '1',
                        'order' => isset($selection['order']) ? intval($selection['order']) : PHP_INT_MAX
                    ];
                }
            }

            // Only update these if they exist in the data
            if (isset($block_data['manual_override'])) {
                $saved_blocks[$block_index]['manual_override'] = $block_data['manual_override'] ? 1 : 0;
            }
            if (isset($block_data['storyCount'])) {
                $saved_blocks[$block_index]['story_count'] = sanitize_text_field($block_data['storyCount']);
            }
        }

        error_log('Merged blocks: ' . print_r($saved_blocks, true));

        // Check execution time before heavy operations
        if ((microtime(true) - $start_time) > $timeout_seconds) {
            error_log('Preview generation timeout');
            wp_send_json_error('Preview generation timeout');
            return;
        }

        // Generate preview content with error handling
        $preview_content = newsletter_generate_preview_content($newsletter_slug, $saved_blocks);
        if ($preview_content === false) {
            error_log('Error generating preview content');
            wp_send_json_error('Error generating preview content');
            return;
        }

        // Check execution time again before sending response
        if ((microtime(true) - $start_time) > $timeout_seconds) {
            error_log('Preview generation timeout during content generation');
            wp_send_json_error('Preview generation timeout');
            return;
        }

        // Build preview HTML
        $preview_html = '<div class="newsletter-preview-container">';
        if (!empty($custom_css)) {
            $preview_html .= '<style type="text/css">';
            $preview_html .= '.newsletter-preview-container {' . esc_html($custom_css) . '}';
            $preview_html .= '</style>';
        }
        $preview_html .= '<div class="newsletter-content">';
        $preview_html .= $preview_content;
        $preview_html .= '</div></div>';

        // Clear any output buffers before sending response
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Start fresh output buffer with compression
        ob_start('ob_gzhandler');

        wp_send_json_success($preview_html);
    } catch (Exception $e) {
        error_log('Preview generation error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        wp_send_json_error('Error generating preview: ' . $e->getMessage());
    } finally {
        $is_generating = false;
        // Reset memory limit if we changed it
        if (isset($current_limit)) {
            ini_set('memory_limit', $current_limit);
        }
        // Clean up any remaining output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}

endif;

// Register the AJAX action only if we're in an admin context
if (is_admin()) {
    add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');
}