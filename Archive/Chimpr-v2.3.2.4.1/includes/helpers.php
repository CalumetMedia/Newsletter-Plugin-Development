<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_newsletter_posts')) {
    function get_newsletter_posts($blocks) {
        if (empty($blocks) || !is_array($blocks)) {
            return [];
        }

        $newsletter_data = [];

        foreach ($blocks as $block) {
            if (!isset($block['type'])) {
                continue;
            }

            $block_data = [
                'type' => $block['type'],
                'show_title' => isset($block['show_title']) ? (bool)$block['show_title'] : true,
                'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                'block_title' => isset($block['block_title']) ? sanitize_text_field($block['block_title']) : '',
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default'
            ];

            if ($block['type'] === 'content' && !empty($block['posts'])) {
                // Get selected posts in their saved order
                $selected_posts = [];
                foreach ($block['posts'] as $post_id => $post_data) {
                    if (isset($post_data['checked']) && $post_data['checked'] === '1') {
                        $selected_posts[$post_id] = $post_data;
                    }
                }

                // Sort selected posts by their order
                uasort($selected_posts, function($a, $b) {
                    $order_a = isset($a['order']) ? intval($a['order']) : PHP_INT_MAX;
                    $order_b = isset($b['order']) ? intval($b['order']) : PHP_INT_MAX;
                    return $order_a - $order_b;
                });

                // Get post objects in the correct order
                $ordered_posts = [];
                foreach ($selected_posts as $post_id => $post_data) {
                    $post = get_post($post_id);
                    if ($post) {
                        $ordered_posts[] = $post;
                    }
                }

                $block_data['posts'] = $ordered_posts;
            } elseif ($block['type'] === 'html') {
                $block_data['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
            } elseif ($block['type'] === 'wysiwyg') {
                $content = isset($block['wysiwyg']) ? $block['wysiwyg'] : '';
                
                // Always set wysiwyg field, even if empty
                $block_data['wysiwyg'] = '';
                
                // Only process if we have content
                if (!empty($content)) {
                    if (strpos($content, '<p>') === false) {
                        $content = wpautop($content);
                    }
                    $block_data['wysiwyg'] = wp_kses_post($content);
                }
            } elseif ($block['type'] === 'pdf_link') {
                $block_data['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
            }

            $newsletter_data[] = $block_data;
        }

        return $newsletter_data;
    }
}

if (!function_exists('newsletter_generate_preview_content')) {
    function newsletter_generate_preview_content($newsletter_slug, $blocks) {
        if (empty($newsletter_slug)) {
            return '<p>Error: Invalid newsletter slug</p>';
        }

        if (!is_array($blocks)) {
            return '<p>Error: Invalid blocks data</p>';
        }

        $available_templates = get_option('newsletter_templates', []);
        $newsletter_html = '';

        // Add header template if selected
        $header_template_id = get_option("newsletter_header_template_$newsletter_slug", '');
        if (!empty($header_template_id) && isset($available_templates[$header_template_id]) && 
            isset($available_templates[$header_template_id]['html'])) {
            $newsletter_html .= wp_kses_post($available_templates[$header_template_id]['html']);
        }

        $newsletter_posts = get_newsletter_posts($blocks);
        if (empty($newsletter_posts)) {
            return '<p>Error: No content available for preview</p>';
        }

        // Add content blocks
        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block" style="margin-bottom: 20px;">';

            if ($block_data['show_title'] && (!empty($block_data['block_title']) || !empty($block_data['title']))) {
                $title = !empty($block_data['block_title']) ? $block_data['block_title'] : $block_data['title'];
                $newsletter_html .= '<h2>' . esc_html($title) . '</h2>';
            }

            if ($block_data['type'] === 'content' && !empty($block_data['posts'])) {
                $template_id = isset($block_data['template_id']) ? $block_data['template_id'] : 'default';
                $template_content = '';

                // Validate and get template content
                if (($template_id === '0' || $template_id === 0) && isset($available_templates[0])) {
                    $template_content = $available_templates[0]['html'];
                } elseif (!empty($template_id) && isset($available_templates[$template_id]) && isset($available_templates[$template_id]['html'])) {
                    $template_content = $available_templates[$template_id]['html'];
                } else {
                    error_log("Template not found for ID: $template_id, using default template");
                    if (file_exists(NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php')) {
                        ob_start();
                        include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
                        $template_content = ob_get_clean();
                    } else {
                        error_log("Default template file not found");
                        $template_content = '<div class="post-content">{title}<br>{content}</div>';
                    }
                }

                foreach ($block_data['posts'] as $post) {
                    $block_content = $template_content;
                    $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');

                    // Handle conditional thumbnail tags
                    if (!empty($thumbnail_url)) {
                        $block_content = preg_replace('/\{if_thumbnail\}(.*?)\{\/if_thumbnail\}/s', '$1', $block_content);
                    } else {
                        $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
                    }

                    try {
                        $replacements = [
                            '{title}'         => esc_html($post->post_title),
                            '{content}'       => wp_kses_post($post->post_content),
                            '{thumbnail_url}' => esc_url($thumbnail_url),
                            '{permalink}'     => esc_url(get_permalink($post->ID)),
                            '{excerpt}'       => wp_kses_post(get_the_excerpt($post)),
                            '{author}'        => esc_html(get_the_author_meta('display_name', $post->post_author)),
                            '{publish_date}'  => esc_html(get_the_date('', $post->ID)),
                            '{categories}'    => esc_html(implode(', ', wp_get_post_categories($post->ID, ['fields' => 'names'])))
                        ];

                        $newsletter_html .= strtr($block_content, $replacements);
                    } catch (Exception $e) {
                        error_log("Error processing post ID: " . $post->ID . " - " . $e->getMessage());
                        continue;
                    }
                }
            } elseif ($block_data['type'] === 'html' && isset($block_data['html'])) {
                $newsletter_html .= wp_kses_post(wp_unslash($block_data['html']));
            } elseif ($block_data['type'] === 'wysiwyg' && isset($block_data['wysiwyg'])) {
                $newsletter_html .= wp_kses_post($block_data['wysiwyg']);
            } elseif ($block_data['type'] === 'pdf_link' && isset($block_data['html'])) {
                // For PDF Link blocks, we treat them like HTML blocks
                $newsletter_html .= wp_kses_post(wp_unslash($block_data['html']));
            }

            $newsletter_html .= '</div>';
        }

        // Add footer template if selected
        $footer_template_id = get_option("newsletter_footer_template_$newsletter_slug", '');
        if (!empty($footer_template_id) && isset($available_templates[$footer_template_id]) && 
            isset($available_templates[$footer_template_id]['html'])) {
            $newsletter_html .= wp_kses_post($available_templates[$footer_template_id]['html']);
        }

        return $newsletter_html;
    }
}

if (!function_exists('newsletter_handle_blocks_form_submission_non_ajax')) {
    function newsletter_handle_blocks_form_submission_non_ajax($newsletter_slug) {
        $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
        $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $sanitized_blocks = [];

        foreach ($blocks as $block) {
            // Basic block sanitization first
            $sanitized_block = [
                'type' => isset($block['type']) ? sanitize_text_field($block['type']) : '',
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

            // Get existing block for content preservation
            $existing_block = null;
            if (!empty($existing_blocks)) {
                foreach ($existing_blocks as $existing_block_item) {
                    if ($existing_block_item['type'] === $block['type'] && 
                        $existing_block_item['title'] === $block['title']) {
                        $existing_block = $existing_block_item;
                        break;
                    }
                }
            }

            // Handle different block types
            if (isset($block['type'])) {
                if ($block['type'] === 'wysiwyg') {
                    $processed_block = handle_wysiwyg_content($block, $existing_block);
                    $sanitized_block['wysiwyg'] = isset($processed_block['wysiwyg']) ? $processed_block['wysiwyg'] : '';
                } elseif ($block['type'] === 'html') {
                    $processed_block = handle_html_content($block, $existing_block);
                    $sanitized_block['html'] = isset($processed_block['html']) ? $processed_block['html'] : '';
                }
            }

            $sanitized_blocks[] = $sanitized_block;
        }

        update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

        if (isset($_POST['subject_line'])) {
            update_option("newsletter_subject_line_$newsletter_slug", sanitize_text_field(wp_unslash($_POST['subject_line'])));
        }
        if (isset($_POST['header_template'])) {
            update_option("newsletter_header_template_$newsletter_slug", sanitize_text_field(wp_unslash($_POST['header_template'])));
        }
        if (isset($_POST['footer_template'])) {
            update_option("newsletter_footer_template_$newsletter_slug", sanitize_text_field(wp_unslash($_POST['footer_template'])));
        }
    }
}

// Ensure proper WYSIWYG content preservation
function handle_wysiwyg_content($block, $existing_block = null) {
    if ($block['type'] !== 'wysiwyg') {
        return $block;
    }
    
    // Handle empty content cases with correct 'wysiwyg' key
    if (empty($block['wysiwyg']) && !empty($existing_block['wysiwyg'])) {
        error_log('[WYSIWYG Debug] Empty content detected, preserving existing content');
        $block['wysiwyg'] = $existing_block['wysiwyg'];
        return $block;
    }
    
    // Handle auto-save content preservation
    if (isset($_POST['is_auto_save']) && $_POST['is_auto_save']) {
        $new_content = isset($block['wysiwyg']) ? trim($block['wysiwyg']) : '';
        $existing_content = isset($existing_block['wysiwyg']) ? trim($existing_block['wysiwyg']) : '';
        
        if (empty($new_content) || $new_content === '<p></p>') {
            error_log('[WYSIWYG Debug] Empty content during auto-save, preserving existing content');
            $block['wysiwyg'] = $existing_content;
            return $block;
        }
        
        // Compare normalized content
        $normalized_new = wp_kses_post($new_content);
        $normalized_existing = wp_kses_post($existing_content);
        
        if ($normalized_new === $normalized_existing) {
            error_log('[WYSIWYG Debug] Content unchanged, preserving existing content');
            $block['wysiwyg'] = $existing_content;
            return $block;
        }
    }
    
    // Ensure proper content format
    if (!empty($block['wysiwyg'])) {
        $content = $block['wysiwyg'];
        if (strpos($content, '<p>') === false) {
            $content = wpautop($content);
        }
        $block['wysiwyg'] = wp_kses_post($content);
        error_log('[WYSIWYG Debug] Content processed, length: ' . strlen($block['wysiwyg']));
    }
    
    return $block;
}

// Ensure proper HTML content preservation
function handle_html_content($block, $existing_block = null) {
    if ($block['type'] !== 'html') {
        return $block;
    }
    
    // Handle empty content cases
    if (empty($block['html']) && !empty($existing_block['html'])) {
        error_log('Preserving existing HTML content');
        $block['html'] = $existing_block['html'];
    }
    
    // Ensure proper content format
    if (!empty($block['html'])) {
        $block['html'] = wp_kses_post($block['html']);
    }
    
    return $block;
}