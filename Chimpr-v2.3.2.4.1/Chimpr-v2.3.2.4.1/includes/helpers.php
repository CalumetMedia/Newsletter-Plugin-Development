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

            $current_block = [
                'type'        => $block['type'],
                'title'       => isset($block['title']) ? stripslashes(sanitize_text_field($block['title'])) : '',
                'block_title' => isset($block['title']) ? stripslashes(sanitize_text_field($block['title'])) : '',
                'show_title'  => isset($block['show_title']) ? (bool)$block['show_title'] : true,
                'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default',
                'category'    => isset($block['category']) ? sanitize_text_field($block['category']) : '',
                'date_range'  => isset($block['date_range']) ? $block['date_range'] : '',
                'story_count' => isset($block['story_count']) ? $block['story_count'] : 'disable',
                'manual_override' => isset($block['manual_override']) ? (bool)$block['manual_override'] : false,
                'posts'       => []
            ];

            switch ($block['type']) {
                case 'content':
                    if (!empty($block['posts'])) {
                        foreach ($block['posts'] as $post_id => $post_data) {
                            if (isset($post_data['checked']) && $post_data['checked']) {
                                $current_block['posts'][$post_id] = [
                                    'checked' => '1',
                                    'order' => isset($post_data['order']) ? $post_data['order'] : '0'
                                ];
                            }
                        }
                    }
                    error_log("Added content block to newsletter data");
                    break;

                case 'html':
                    $current_block['html'] = isset($block['html']) ? wp_kses_post(stripslashes($block['html'])) : '';
                    error_log("Added HTML block to newsletter data");
                    break;

                case 'wysiwyg':
                    $content = isset($block['wysiwyg']) ? stripslashes($block['wysiwyg']) : '';
                    if (!empty($content) && strpos($content, '<p>') === false) {
                        $content = wpautop($content);
                    }
                    $current_block['wysiwyg'] = wp_kses_post($content);
                    error_log("Added WYSIWYG block to newsletter data. Content length: " . strlen($content));
                    break;
            }

            $newsletter_data[] = $current_block;
        }

        error_log("Final newsletter data contains " . count($newsletter_data) . " blocks");
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
        $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
        $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');
        
        $newsletter_html = !empty($custom_header) ? wp_kses_post(wp_unslash($custom_header)) : '';

        $newsletter_posts = get_newsletter_posts($blocks);
        if (empty($newsletter_posts)) {
            return '<p>Error: No content available for preview</p>';
        }

        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block">';

            if ($block_data['show_title'] && (!empty($block_data['block_title']) || !empty($block_data['title']))) {
                $title = !empty($block_data['block_title']) ? $block_data['block_title'] : $block_data['title'];
                $newsletter_html .= '<h2>' . esc_html($title) . '</h2>';
            }

            if ($block_data['type'] === 'content') {
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

                if (!empty($block_data['posts'])) {
                    foreach ($block_data['posts'] as $post_id => $post_data) {
                        if (!isset($post_data['checked']) || !$post_data['checked']) {
                            continue;
                        }

                        $post = get_post($post_id);
                        if (!$post) {
                            error_log("Post not found: $post_id");
                            continue;
                        }

                        $block_content = $template_content;
                        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');

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
                                '{permalink}'     => esc_url(get_permalink($post_id)),
                                '{excerpt}'       => wp_kses_post(get_the_excerpt($post)),
                                '{author}'        => esc_html(get_the_author_meta('display_name', $post->post_author)),
                                '{publish_date}'  => esc_html(get_the_date('', $post_id)),
                                '{categories}'    => esc_html(implode(', ', wp_get_post_categories($post_id, ['fields' => 'names'])))
                            ];

                            $newsletter_html .= strtr($block_content, $replacements);
                        } catch (Exception $e) {
                            error_log("Error processing post ID: $post_id - " . $e->getMessage());
                            continue;
                        }
                    }
                }
            } elseif ($block_data['type'] === 'html') {
                $newsletter_html .= wp_kses_post(wp_unslash($block_data['html']));
            } elseif ($block_data['type'] === 'wysiwyg') {
                $content = isset($block_data['wysiwyg']) ? $block_data['wysiwyg'] : '';
                $newsletter_html .= sprintf(
                    '<div class="wysiwyg-content">%s</div>',
                    wp_kses_post($content)
                );
            }

            $newsletter_html .= '</div>';
        }

        if (!empty($custom_footer)) {
            $newsletter_html .= wp_kses_post(wp_unslash($custom_footer));
        }

        return $newsletter_html;
    }
}

if (!function_exists('newsletter_handle_blocks_form_submission_non_ajax')) {
    function newsletter_handle_blocks_form_submission_non_ajax($newsletter_slug) {
        $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];

        foreach ($blocks as $key => $block) {
            if (isset($block['type']) && $block['type'] === 'wysiwyg') {
                $content = wp_unslash($block['wysiwyg']);
                if (!empty($content) && strpos($content, '<p>') === false) {
                    $content = wpautop($content);
                }
                $blocks[$key]['wysiwyg'] = wp_kses_post($content);
            } elseif (isset($block['type']) && $block['type'] === 'html') {
                $blocks[$key]['html'] = wp_kses_post(wp_unslash($block['html']));
            }
        }

        update_option("newsletter_blocks_$newsletter_slug", $blocks);

        if (isset($_POST['subject_line'])) {
            update_option("newsletter_subject_line_$newsletter_slug", sanitize_text_field(wp_unslash($_POST['subject_line'])));
        }
        if (isset($_POST['custom_header'])) {
            update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post(wp_unslash($_POST['custom_header'])));
        }
        if (isset($_POST['custom_footer'])) {
            update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post(wp_unslash($_POST['custom_footer'])));
        }
    }
}