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

            $template_id = isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default';
            $show_title  = isset($block['show_title']) ? (bool)$block['show_title'] : true;
            $block_title = !empty($block['title']) ? sanitize_text_field($block['title']) : '';
            $story_count = isset($block['story_count']) ? $block['story_count'] : 'disable';
            $manual_override = isset($block['manual_override']) ? (bool)$block['manual_override'] : false;

            switch ($block['type']) {
                case 'content':
                    if (!empty($block['posts'])) {
                        $current_block = [
                            'type'        => 'content',
                            'block_title' => $block_title,
                            'show_title'  => $show_title,
                            'post_count'  => isset($block['post_count']) ? intval($block['post_count']) : 5,
                            'date_range'  => isset($block['date_range']) ? intval($block['date_range']) : 7,
                            'end_date'    => isset($block['end_date']) ? $block['end_date'] : '',
                            'start_date'  => isset($block['start_date']) ? $block['start_date'] : '',
                            'posts'       => [],
                            'template_id' => $template_id
                        ];

                        // Convert posts array to a sortable array with order
                        $sorted_posts = [];
                        
                        // Get all selected posts
                        foreach ($block['posts'] as $post_id => $post_data) {
                            // Validate post data structure
                            if (!is_array($post_data)) {
                                error_log("Invalid post data structure for post ID: $post_id");
                                continue;
                            }

                            // Normalize checked value and only store checked posts
                            if (isset($post_data['checked']) && $post_data['checked'] === '1') {
                                // Ensure order is a valid integer
                                $order = isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX;
                                if ($order < 0) {
                                    $order = PHP_INT_MAX;
                                }
                                
                                $sorted_posts[] = [
                                    'id' => $post_id,
                                    'order' => $order
                                ];
                            }
                        }
                        
                        // Sort posts by order
                        usort($sorted_posts, function($a, $b) {
                            return $a['order'] - $b['order'];
                        });
                        
                        // Apply story count limit if not 'disable' and not in manual override mode
                        if (!$manual_override && $story_count !== 'disable') {
                            $count = intval($story_count);
                            if ($count > 0) {
                                $sorted_posts = array_slice($sorted_posts, 0, $count);
                            } else {
                                error_log("Invalid story count value: $story_count");
                            }
                        }

                        // Process sorted posts
                        foreach ($sorted_posts as $sorted_post) {
                            $post_id = $sorted_post['id'];
                            $post = get_post($post_id);
                            if ($post) {
                                $current_block['posts'][] = [
                                    'title'         => get_the_title($post_id),
                                    'content'       => apply_filters('the_content', $post->post_content),
                                    'excerpt'       => get_the_excerpt($post),
                                    'thumbnail_url' => get_the_post_thumbnail_url($post_id, 'full') ?: '',
                                    'permalink'     => get_permalink($post_id),
                                    'author_id'     => $post->post_author,
                                    'author_name'   => get_the_author_meta('display_name', $post->post_author),
                                    'ID'            => $post_id
                                ];
                                error_log("Successfully processed post ID: $post_id with order: " . $sorted_post['order']);
                            } else {
                                error_log("Failed to get post with ID: $post_id");
                            }
                        }

                        if (!empty($current_block['posts'])) {
                            $newsletter_data[] = $current_block;
                            error_log("Added block with " . count($current_block['posts']) . " posts to newsletter data");
                        }
                    }
                    break;

                case 'html':
                    if (isset($block['html'])) {
                        $newsletter_data[] = [
                            'type'        => 'html',
                            'block_title' => $block_title,
                            'show_title'  => $show_title,
                            'html'        => wp_kses_post(wp_unslash($block['html'])),
                            'template_id' => $template_id
                        ];
                    }
                    break;

                case 'wysiwyg':
                    if (isset($block['wysiwyg'])) {
                        $content = $block['wysiwyg'];
                        $content = wp_unslash($content);
                        if (!empty($content)) {
                            if (strpos($content, '<p>') === false) {
                                $content = wpautop($content);
                            }
                            $newsletter_data[] = [
                                'type'        => 'wysiwyg',
                                'block_title' => $block_title,
                                'show_title'  => $show_title,
                                'wysiwyg'     => wp_kses_post($content)
                            ];
                        }
                    }
                    break;
            }
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
        $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
        $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');
        
        $newsletter_html = !empty($custom_header) ? wp_kses_post(wp_unslash($custom_header)) : '';

        $newsletter_posts = get_newsletter_posts($blocks);
        if (empty($newsletter_posts)) {
            return '<p>Error: No content available for preview</p>';
        }

        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block">';

            if (!empty($block_data['block_title']) && $block_data['show_title']) {
                $newsletter_html .= '<h2>' . esc_html(wp_unslash($block_data['block_title'])) . '</h2>';
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
        foreach ($block_data['posts'] as $post_data) {
            if (!is_array($post_data) || empty($post_data['ID'])) {
                error_log("Invalid post data structure in preview generation");
                continue;
            }

            $block_content = $template_content;

            // Handle conditional thumbnail tags
            if (!empty($post_data['thumbnail_url'])) {
                $block_content = preg_replace('/\{if_thumbnail\}(.*?)\{\/if_thumbnail\}/s', '$1', $block_content);
            } else {
                $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
            }

            try {
                $replacements = [
                    '{title}'         => esc_html($post_data['title']),
                    '{content}'       => wp_kses_post($post_data['content']),
                    '{thumbnail_url}' => esc_url($post_data['thumbnail_url']),
                    '{permalink}'     => esc_url($post_data['permalink']),
                    '{excerpt}'       => wp_kses_post(!empty($post_data['excerpt']) ? $post_data['excerpt'] : wp_trim_words($post_data['content'], 55)),
                    '{author}'        => esc_html($post_data['author_name']),
                    '{publish_date}'  => esc_html(get_the_date('', $post_data['ID'])),
                    '{categories}'    => esc_html(implode(', ', wp_get_post_categories($post_data['ID'], ['fields' => 'names'])))
                ];

                $newsletter_html .= strtr($block_content, $replacements);
            } catch (Exception $e) {
                error_log("Error processing post ID: " . $post_data['ID'] . " - " . $e->getMessage());
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