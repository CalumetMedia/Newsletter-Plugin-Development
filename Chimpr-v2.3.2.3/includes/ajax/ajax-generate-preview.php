<?php
// includes/ajax/ajax-generate-preview.php
if (!defined('ABSPATH')) exit;

if (!function_exists('newsletter_generate_preview')):

function newsletter_generate_preview() {
    static $is_generating = false;
    if ($is_generating) {
        wp_send_json_error('Preview generation already in progress');
        return;
    }
    $is_generating = true;

    try {
        check_ajax_referer('generate_preview_nonce', 'security');

        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        
        // Get both saved blocks and current selections
        $saved_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $saved_selections = isset($_POST['saved_selections']) ? json_decode(stripslashes($_POST['saved_selections']), true) : [];

        // Merge saved selections into blocks
        foreach ($saved_selections as $block_index => $block_data) {
            if (isset($saved_blocks[$block_index])) {
                // Handle posts if they exist
                if (isset($block_data['selections'])) {
                    $saved_blocks[$block_index]['posts'] = [];
                    foreach ($block_data['selections'] as $post_id => $selection) {
                        $saved_blocks[$block_index]['posts'][$post_id] = [
                            'selected' => $selection['checked'] ? 1 : 0,
                            'order' => $selection['order']
                        ];
                    }
                }
                
                // Copy over basic block settings
                $saved_blocks[$block_index]['manual_override'] = isset($block_data['manual_override']) ? $block_data['manual_override'] : 0;
                $saved_blocks[$block_index]['story_count'] = isset($block_data['story_count']) ? $block_data['story_count'] : 'disable';
                if (isset($block_data['template_id'])) {
                    $saved_blocks[$block_index]['template_id'] = $block_data['template_id'];
                }
                
                // Handle WYSIWYG and HTML content
                if (isset($block_data['type'])) {
                    $saved_blocks[$block_index]['type'] = $block_data['type'];
                    if ($block_data['type'] === 'wysiwyg' && isset($block_data['wysiwyg'])) {
                        $saved_blocks[$block_index]['wysiwyg'] = wp_kses_post($block_data['wysiwyg']);
                    } elseif ($block_data['type'] === 'html' && isset($block_data['html'])) {
                        $saved_blocks[$block_index]['html'] = wp_kses_post($block_data['html']);
                    }
                }
            }
        }

        $preview_content = newsletter_generate_preview_content($newsletter_slug, $saved_blocks);

        $preview_html = '<div class="newsletter-preview-container">';
        if (!empty($custom_css)) {
            $preview_html .= '<style type="text/css">';
            $preview_html .= '.newsletter-preview-container {' . $custom_css . '}';
            $preview_html .= '</style>';
        }
        $preview_html .= '<div class="newsletter-content">';
        $preview_html .= $preview_content;
        $preview_html .= '</div></div>';

        wp_send_json_success($preview_html);
    } catch (Exception $e) {
        wp_send_json_error('Error generating preview: ' . $e->getMessage());
    } finally {
        $is_generating = false;
    }
}

endif;

add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');