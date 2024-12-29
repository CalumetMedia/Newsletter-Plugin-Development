<?php
if (!defined('ABSPATH')) {
    exit;
}

function newsletter_handle_blocks_form_submission() {
    check_ajax_referer('save_blocks_action', 'security');

    // Get the newsletter slug
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    
    // Get existing blocks to preserve any data not in the current form submission
    $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    
    // Get new blocks data
    $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];
    $sanitized_blocks = [];

    foreach ($blocks as $index => $block) {
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
            
            // Process new post data
            if (!empty($block['posts']) && is_array($block['posts'])) {
                foreach ($block['posts'] as $post_id => $post_data) {
                    $post_id = intval($post_id);
                    
                    // Determine selected state by checking the value
                    $is_selected = isset($post_data['selected']) && $post_data['selected'] == 1;
                    
                    // Update or add post data
                    $sanitized_block['posts'][$post_id] = [
                        'selected' => $is_selected ? 1 : 0,
                        'order' => isset($post_data['order']) ? intval($post_data['order']) : (
                            isset($existing_blocks[$index]['posts'][$post_id]['order']) ? 
                            $existing_blocks[$index]['posts'][$post_id]['order'] : 
                            PHP_INT_MAX
                        )
                    ];
                }
            }

            // If NOT in manual override mode, preserve existing posts data
            if (!$sanitized_block['manual_override'] && isset($existing_blocks[$index]['posts'])) {
                $sanitized_block['posts'] = $existing_blocks[$index]['posts'];
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
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
    
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

    wp_send_json_success('Blocks saved successfully');
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');