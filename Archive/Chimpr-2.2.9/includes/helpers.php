<?php
if (!defined('ABSPATH')) {
    exit;
}

include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';

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
            $show_title = isset($block['show_title']) ? (bool)$block['show_title'] : true;
            $block_title = !empty($block['title']) ? sanitize_text_field($block['title']) : '';

            switch ($block['type']) {
                case 'content':
                    if (!empty($block['posts'])) {
                        $current_block = [
                            'type'        => 'content',
                            'block_title' => $block_title,
                            'show_title'  => $show_title,
                            'posts'       => [],
                            'template_id' => $template_id
                        ];

                        foreach ($block['posts'] as $post_id => $post_data) {
                            if (!empty($post_data['selected'])) {
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
                                }
                            }
                        }

                        if (!empty($current_block['posts'])) {
                            $newsletter_data[] = $current_block;
                        }
                    }
                    break;

                case 'html':
                    if (isset($block['html'])) {
                        $newsletter_data[] = [
                            'type'        => 'html',
                            'block_title' => $block_title,
                            'show_title'  => $show_title,
                            'html'        => wp_kses_post($block['html']),
                            'template_id' => $template_id
                        ];
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

        $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
        $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');
        $newsletter_html = !empty($custom_header) ? wp_kses_post($custom_header) : '';

        $newsletter_posts = get_newsletter_posts($blocks);
        if (empty($newsletter_posts)) {
            return '<p>Error: No content available for preview</p>';
        }

        $available_templates = get_option('newsletter_templates', []);

        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block">';

            if (!empty($block_data['block_title']) && $block_data['show_title']) {
                $newsletter_html .= '<h2>' . esc_html($block_data['block_title']) . '</h2>';
            }

            if ($block_data['type'] === 'content') {
                $template_id = isset($block_data['template_id']) ? $block_data['template_id'] : 'default';
                $template_content = '';

                if (!empty($template_id) && isset($available_templates[$template_id]['html'])) {
                    $template_content = $available_templates[$template_id]['html'];
                } else {
                    ob_start();
                    include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
                    $template_content = ob_get_clean();
                }

                if (!empty($block_data['posts'])) {
                    foreach ($block_data['posts'] as $post_data) {
                        $block_content = $template_content;

                        if (!empty($post_data['thumbnail_url'])) {
                            $block_content = preg_replace('/\{if_thumbnail\}(.*?)\{\/if_thumbnail\}/s', '$1', $block_content);
                        } else {
                            $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
                        }

                        $replacements = [
                            '{title}' => esc_html($post_data['title']),
                            '{content}' => wp_kses_post($post_data['content']),
                            '{thumbnail_url}' => esc_url($post_data['thumbnail_url']),
                            '{permalink}' => esc_url($post_data['permalink']),
                            '{excerpt}' => wp_kses_post(!empty($post_data['excerpt']) ? $post_data['excerpt'] : wp_trim_words($post_data['content'], 55)),
                            '{author_name}' => esc_html($post_data['author_name']),
                            '{publish_date}' => esc_html(get_the_date('', $post_data['ID'])),
                            '{categories}' => esc_html(implode(', ', wp_get_post_categories($post_data['ID'], ['fields' => 'names'])))
                        ];

                        $newsletter_html .= strtr($block_content, $replacements);
                    }
                }
            } elseif ($block_data['type'] === 'html') {
                $newsletter_html .= wp_kses_post($block_data['html']);
            }

            $newsletter_html .= '</div>';
        }

        if (!empty($custom_footer)) {
            $newsletter_html .= wp_kses_post($custom_footer);
        }

        return $newsletter_html;
    }
}
