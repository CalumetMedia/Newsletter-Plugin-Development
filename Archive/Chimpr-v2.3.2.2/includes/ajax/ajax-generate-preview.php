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
        foreach ($saved_blocks as $block_index => $block) {
            if (isset($saved_selections[$block_index]) && isset($saved_selections[$block_index]['selections'])) {
                foreach ($saved_selections[$block_index]['selections'] as $post_id => $selection) {
                    if (!isset($saved_blocks[$block_index]['posts'])) {
                        $saved_blocks[$block_index]['posts'] = [];
                    }
                    if (!isset($saved_blocks[$block_index]['posts'][$post_id])) {
                        $saved_blocks[$block_index]['posts'][$post_id] = [];
                    }
                    $saved_blocks[$block_index]['posts'][$post_id]['selected'] = $selection['checked'];
                    $saved_blocks[$block_index]['posts'][$post_id]['order'] = $selection['order'];
                }
            }
            if (isset($saved_selections[$block_index]['storyCount'])) {
                $saved_blocks[$block_index]['story_count'] = $saved_selections[$block_index]['storyCount'];
            }
            if (isset($saved_selections[$block_index]['category'])) {
                $saved_blocks[$block_index]['category'] = $saved_selections[$block_index]['category'];
            }
        }

        $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
        $custom_header = isset($_POST['custom_header']) ? wp_kses_post($_POST['custom_header']) : '';
        $custom_footer = isset($_POST['custom_footer']) ? wp_kses_post($_POST['custom_footer']) : '';
        $custom_css = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';

        if (!empty($custom_header)) {
            update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
        }
        if (!empty($custom_footer)) {
            update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
        }
        if (!empty($custom_css)) {
            update_option("newsletter_custom_css_$newsletter_slug", $custom_css);
        }

        $sanitized_blocks = [];
        foreach ($blocks as $index => $block) {
            $sanitized_block = [
                'type'        => sanitize_text_field($block['type']),
                'title'       => sanitize_text_field($block['title']),
                'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
                'show_title'  => isset($block['show_title']),
                'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable',
                'date_range'  => isset($block['date_range']) ? intval($block['date_range']) : 7
            ];

            if ($sanitized_block['type'] === 'content') {
                $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
                $sanitized_block['posts'] = [];

                // Get current selections from the form data
                if (isset($block['posts']) && is_array($block['posts'])) {
                    foreach ($block['posts'] as $post_id => $post_data) {
                        if (!empty($post_data['selected'])) {
                            $sanitized_block['posts'][$post_id] = [
                                'selected' => true,
                                'order' => isset($post_data['order']) ? intval($post_data['order']) : 0
                            ];
                        }
                    }
                }

                // If no posts are selected in form data, check saved selections
                if (empty($sanitized_block['posts']) && isset($saved_selections[$index]['selections'])) {
                    foreach ($saved_selections[$index]['selections'] as $post_id => $selection) {
                        if ($selection['checked']) {
                            $sanitized_block['posts'][$post_id] = [
                                'selected' => true,
                                'order' => isset($selection['order']) ? intval($selection['order']) : 0
                            ];
                        }
                    }
                }
            } elseif ($sanitized_block['type'] === 'html') {
                $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
            } elseif ($sanitized_block['type'] === 'wysiwyg') {
                $sanitized_block['wysiwyg'] = wp_kses_post($block['wysiwyg'] ?? '');
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        $preview_content = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);

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