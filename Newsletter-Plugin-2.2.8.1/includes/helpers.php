<?php
// includes/helpers.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';

/**
 * Helper Function to Get Newsletter Posts with Thumbnail URLs and Templates
 *
 * @param array $blocks Array of blocks containing posts and template IDs.
 * @return array Array containing blocks with their respective posts, thumbnail URLs, and templates.
 */
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

            switch ($block['type']) {
                case 'content':
                    if (!empty($block['posts'])) {
                        $block_title = !empty($block['title']) ? sanitize_text_field($block['title']) : '';
                        $current_block = [
                            'block_title' => $block_title,
                            'type'        => 'content',
                            'posts'       => [],
                            'template_id' => $template_id,
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

                case 'advertising':
                    if (!empty($block['html'])) {
                        $newsletter_data[] = [
                            'block_title' => !empty($block['title']) ? sanitize_text_field($block['title']) : __('Advertising', 'newsletter'),
                            'type'        => 'advertising',
                            'html'        => wp_kses_post($block['html']),
                            'template_id' => $template_id,
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

        if (empty($blocks) || !is_array($blocks)) {
            return '<p>Error: No content blocks defined</p>';
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
            
            if (!empty($block_data['block_title'])) {
                $newsletter_html .= '<h2>' . esc_html($block_data['block_title']) . '</h2>';
            }

            $template_content = '';
            $template_id = $block_data['template_id'] ?? 'default';

            if (!empty($template_id) && isset($available_templates[$template_id]['html'])) {
                $template_content = $available_templates[$template_id]['html'];
            } else {
                ob_start();
                include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
                $template_content = ob_get_clean();
            }

            if ($block_data['type'] === 'content' && !empty($block_data['posts'])) {
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
                        '{excerpt}' => wp_kses_post($post_data['excerpt'] ?? wp_trim_words($post_data['content'], 55)),
                        '{author_name}' => esc_html($post_data['author_name']),
                        '{publish_date}' => esc_html(get_the_date('', $post_data['ID'])),
                        '{categories}' => esc_html(implode(', ', wp_get_post_categories($post_data['ID'], ['fields' => 'names'])))
                    ];
                    
                    $newsletter_html .= strtr($block_content, $replacements);
                }
            } elseif ($block_data['type'] === 'advertising') {
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

/**
 * Generate Preview Content for the Newsletter
 *
 * @param string $newsletter_slug The slug of the newsletter.
 * @param array  $blocks          The blocks associated with the newsletter.
 * @return string The generated newsletter HTML.
 */
if (!function_exists('newsletter_generate_preview_content')) {
    function newsletter_generate_preview_content($newsletter_slug, $blocks) {



        if (empty($newsletter_slug)) {
            return '<p>Error: Invalid newsletter slug</p>';
        }

        if (!is_array($blocks)) {
            return '<p>Error: Invalid blocks data</p>';
        }






        // Get custom header/footer
        $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
        $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');
        
        // Initialize newsletter HTML with header
        $newsletter_html = !empty($custom_header) ? wp_kses_post($custom_header) : '';
        
        // Retrieve posts data including thumbnail URLs and templates
        $newsletter_posts = get_newsletter_posts($blocks);
           
        // Get available templates
        $available_templates = get_option('newsletter_templates', []);
    
        // Iterate through blocks and apply their templates
        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block">';
            
            if (!empty($block_data['block_title'])) {
                $newsletter_html .= '<h2>' . esc_html($block_data['block_title']) . '</h2>';
            }

            // Get template for this block
            $template_id = isset($block_data['template_id']) ? $block_data['template_id'] : '';
            $template_content = '';

            if (!empty($template_id) && isset($available_templates[$template_id]['html'])) {
                $template_content = $available_templates[$template_id]['html'];
            } else {
                // Include default-template.php content if no template is selected
                ob_start();
                include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
                $template_content = ob_get_clean();
            }

            if ($block_data['type'] === 'content') {
                foreach ($block_data['posts'] as $post_data) {
                    $block_content = $template_content;
                    
                    // Handle {if_thumbnail} conditional
                    if (!empty($post_data['thumbnail_url'])) {
                        $block_content = preg_replace('/\{if_thumbnail\}(.*?)\{\/if_thumbnail\}/s', '$1', $block_content);
                    } else {
                        $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
                    }

                    // Replace remaining placeholders
$replacements = [
    '{title}' => esc_html($post_data['title']),
    '{content}' => wp_kses_post($post_data['content']),
    '{thumbnail_url}' => esc_url($post_data['thumbnail_url']),
    '{permalink}' => esc_url($post_data['permalink']),
    '{excerpt}' => wp_kses_post(!empty($post_data['excerpt']) ? $post_data['excerpt'] : wp_trim_words($post_data['content'], 55)), // Fallback for missing excerpts
    '{author_name}' => esc_html(get_the_author_meta('display_name', $post_data['author_id'])),
    '{publish_date}' => esc_html(get_the_date('', $post_data['ID'])),
    '{categories}' => esc_html(implode(', ', wp_get_post_categories($post_data['ID'], ['fields' => 'names']))),
];
                    
                    $block_content = str_replace(
                        array_keys($replacements),
                        array_values($replacements),
                        $block_content
                    );
                    
                    $newsletter_html .= $block_content;
                }
            } elseif ($block_data['type'] === 'advertising') {
                $newsletter_html .= wp_kses_post($block_data['html']);
            }

            $newsletter_html .= '</div>';
        }

        // Add footer to newsletter HTML
        if (!empty($custom_footer)) {
            $newsletter_html .= wp_kses_post($custom_footer);
        }

        return $newsletter_html;
    }
}