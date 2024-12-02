<?php
// includes/helpers.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Helper Function to Get Newsletter Posts with Thumbnail URLs and Templates
 *
 * Processes an array of blocks associated with a newsletter and retrieves detailed information,
 * including posts within those blocks and the assigned templates.
 *
 * @param array $blocks Array of blocks containing posts and template IDs.
 * @return array Array containing blocks with their respective posts, thumbnail URLs, and templates.
 */
if (!function_exists('get_newsletter_posts')) {
function get_newsletter_posts($blocks) {
    error_log('Starting get_newsletter_posts with blocks: ' . print_r($blocks, true));
    $newsletter_data = [];

    foreach ($blocks as $block) {
        error_log('Processing block: ' . print_r($block, true));
        
        if (!isset($block['type'])) {
            error_log('Block type not set, skipping');
            continue;
        }

        $template_id = isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default';
        error_log('Template ID for block: ' . $template_id);

        switch ($block['type']) {
            case 'content':
                if (!empty($block['posts'])) {
                    $block_title = !empty($block['title']) ? sanitize_text_field($block['title']) : '';

                    error_log('Processing content block: ' . $block_title);
                    error_log('Posts in block: ' . print_r($block['posts'], true));

                    $newsletter_data[] = [
                        'block_title' => $block_title,
                        'type'        => 'content',
                        'posts'       => [],
                        'template_id' => $template_id,
                    ];

                    $last_index = count($newsletter_data) - 1;

                    foreach ($block['posts'] as $post_id => $post_data) {
                        if (isset($post_data['selected']) && $post_data['selected']) {
                            $post = get_post($post_id);
                            if ($post) {
                                error_log('Processing post: ' . $post->post_title);
                                
                                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full') ?: '';
                                
                                $newsletter_data[$last_index]['posts'][] = [
                                    'title'         => get_the_title($post_id),
                                    'content'       => apply_filters('the_content', $post->post_content),
                                    'thumbnail_url' => $thumbnail_url,
                                    'permalink'     => get_permalink($post_id),
                                ];
                            }
                        }
                    }
                }
                break;

            case 'advertising':
                if (!empty($block['html'])) {
                    error_log('Processing advertising block');
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

    error_log('Final newsletter_data: ' . print_r($newsletter_data, true));
    return $newsletter_data;
}
}

/**
 * Generate Preview Content for the Newsletter
 *
 * Generates the HTML content for the newsletter preview based on the assigned templates and blocks.
 *
 * @param string $newsletter_slug The slug of the newsletter.
 * @param array  $blocks          The blocks associated with the newsletter.
 * @return string The generated newsletter HTML.
 */
if (!function_exists('newsletter_generate_preview_content')) {
function newsletter_generate_preview_content($newsletter_slug, $blocks) {
    // Get custom header/footer
    $custom_header = get_option("newsletter_custom_header_$newsletter_slug", '');
    $custom_footer = get_option("newsletter_custom_footer_$newsletter_slug", '');
    
    // Initialize newsletter HTML with header
    $newsletter_html = !empty($custom_header) ? wp_kses_post($custom_header) : '';
    
    // Retrieve posts data including thumbnail URLs and templates
    $newsletter_posts = get_newsletter_posts($blocks);
       
    // Get available templates
    $available_templates = get_option('newsletter_templates', []);

    // Ensure default template exists
if (!isset($available_templates['default'])) {
    $default_content = get_option('newsletter_default_template', '');
    $available_templates['default'] = [
        'name' => __('Default Template', 'newsletter'),
        'html' => $default_content
    ];
}

    // Iterate through blocks and apply their templates
    foreach ($newsletter_posts as $block_data) {
        $newsletter_html .= '<div class="newsletter-block">';
        
        if (!empty($block_data['block_title'])) {
            $newsletter_html .= '<h2>' . esc_html($block_data['block_title']) . '</h2>';
        }

        // Get template for this block
    $template_id = isset($block_data['template_id']) ? $block_data['template_id'] : 'default';

    if (isset($available_templates[$template_id]['html'])) {
        $template_content = $available_templates[$template_id]['html'];
    } else {
        $template_content = $available_templates['default']['html'];
    }

        if ($block_data['type'] === 'content') {
            foreach ($block_data['posts'] as $post_data) {
                $block_content = $template_content;
                
                // Handle {if_thumbnail} conditional
                if (!empty($post_data['thumbnail_url'])) {
                    // Remove the conditional tags but keep content
                    $block_content = preg_replace('/\{if_thumbnail\}(.*?)\{\/if_thumbnail\}/s', '$1', $block_content);
                } else {
                    // Remove the entire conditional block including content
                    $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
                }

                // Replace remaining placeholders
                $replacements = [
                    '{title}' => esc_html($post_data['title']),
                    '{content}' => wp_kses_post($post_data['content']),
                    '{thumbnail_url}' => esc_url($post_data['thumbnail_url']),
                    '{permalink}' => esc_url($post_data['permalink'])
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
?>
