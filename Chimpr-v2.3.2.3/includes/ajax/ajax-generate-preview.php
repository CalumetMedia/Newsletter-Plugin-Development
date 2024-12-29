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

        error_log('Saved selections: ' . print_r($saved_selections, true));

        // Merge saved selections into blocks
        foreach ($saved_selections as $block_index => $block_data) {
            if (isset($saved_blocks[$block_index]) && isset($block_data['selections'])) {
                $saved_blocks[$block_index]['posts'] = [];
                foreach ($block_data['selections'] as $post_id => $selection) {
                    $saved_blocks[$block_index]['posts'][$post_id] = [
                        'selected' => $selection['checked'] ? 1 : 0,
                        'order' => $selection['order']
                    ];
                }
                $saved_blocks[$block_index]['manual_override'] = $block_data['manual_override'];
                $saved_blocks[$block_index]['story_count'] = $block_data['storyCount'];
            }
        }

        error_log('Merged blocks: ' . print_r($saved_blocks, true));

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
        error_log('Preview generation error: ' . $e->getMessage());
        wp_send_json_error('Error generating preview: ' . $e->getMessage());
    } finally {
        $is_generating = false;
    }
}

endif;

add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');