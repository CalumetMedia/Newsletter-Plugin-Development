<?php
// includes/helpers.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Helper Function to Get Newsletter Posts with Thumbnail URLs
 *
 * This function processes an array of blocks associated with a specific newsletter (identified by its slug)
 * and retrieves detailed information about each post within those blocks.
 *
 * @param array $blocks Array of blocks containing posts.
 * @return array Array containing blocks with their respective posts and thumbnail URLs.
 */
if (!function_exists('get_newsletter_posts')) {
    function get_newsletter_posts($blocks) {
        $newsletter_data = [];

        foreach ($blocks as $block) {
            if (!isset($block['type'])) {
                continue; // Skip blocks without a type
            }

            switch ($block['type']) {
                case 'content':
                    if (!empty($block['posts'])) {
                        $block_title = !empty($block['title']) ? $block['title'] : __('Block', 'newsletter');

                        $newsletter_data[] = [
                            'block_title' => $block_title,
                            'type'        => 'content',
                            'posts'       => [],
                        ];

                        $last_index = count($newsletter_data) - 1;

                        // Sort posts based on user-defined order
                        uasort($block['posts'], function($a, $b) {
                            $order_a = isset($a['order']) ? intval($a['order']) : PHP_INT_MAX;
                            $order_b = isset($b['order']) ? intval($b['order']) : PHP_INT_MAX;
                            return $order_a - $order_b;
                        });

                        foreach ($block['posts'] as $post_id => $post_data) {
                            if (isset($post_data['selected']) && $post_data['selected']) {
                                $post = get_post($post_id);
                                if ($post) {
                                    // Get the featured image URL
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
                        $newsletter_data[] = [
                            'block_title' => !empty($block['title']) ? $block['title'] : __('Advertising', 'newsletter'),
                            'type'        => 'advertising',
                            'html'        => wp_kses_post($block['html']),
                        ];
                    }
                    break;

                default:
                    // Handle unknown block types or skip
                    break;
            }
        }

        return $newsletter_data;
    }
}

/**
 * Generate Preview Content for the Newsletter
 *
 * This function generates the HTML content for the newsletter preview based on the default template and blocks.
 *
 * @param string $newsletter_slug The slug of the newsletter.
 * @param string $template_id The ID of the template (ignored, using default).
 * @param array  $blocks The blocks associated with the newsletter.
 * @return string The generated newsletter HTML.
 */
if (!function_exists('newsletter_generate_preview_content')) {
    function newsletter_generate_preview_content($newsletter_slug, $template_id, $blocks) {
        // Retrieve posts data including thumbnail URLs
        $newsletter_posts = get_newsletter_posts($blocks);

        // Retrieve the default template content
        $template_content = get_option('newsletter_default_template');
        if (!$template_content) {
            return '<p>' . esc_html__('Default template not found.', 'newsletter') . '</p>';
        }

        // Initialize newsletter HTML
        $newsletter_html = '';

        // Iterate through each block to build the newsletter content
        foreach ($newsletter_posts as $block_data) {
            $newsletter_html .= '<div class="newsletter-block">';

            // Block title
            if (!empty($block_data['block_title'])) {
                $newsletter_html .= '<h2>' . esc_html($block_data['block_title']) . '</h2>';
            }

            // Check block type
            if (isset($block_data['type']) && $block_data['type'] === 'content') {
                // Iterate through posts within the block
                foreach ($block_data['posts'] as $post_data) {
                    // Start with the template content
                    $block_content = $template_content;

                    // Replace placeholders with actual data
                    $replacements = [
                        '{title}'         => esc_html($post_data['title']),
                        '{content}'       => wp_kses_post($post_data['content']),
                        '{permalink}'     => esc_url($post_data['permalink']),
                        '{thumbnail_url}' => esc_url($post_data['thumbnail_url']),
                    ];

                    // Handle conditional {if_thumbnail} blocks
                    if (!empty($post_data['thumbnail_url'])) {
                        // Remove the conditional tags
                        $block_content = str_replace(['{if_thumbnail}', '{/if_thumbnail}'], '', $block_content);
                    } else {
                        // Remove the entire block between {if_thumbnail} and {/if_thumbnail}
                        $block_content = preg_replace('/\{if_thumbnail\}.*?\{\/if_thumbnail\}/s', '', $block_content);
                    }

                    // Perform the replacements
                    $block_content = str_replace(array_keys($replacements), array_values($replacements), $block_content);

                    // Append to the newsletter HTML
                    $newsletter_html .= $block_content;
                }
            } elseif (isset($block_data['type']) && $block_data['type'] === 'advertising') {
                $newsletter_html .= isset($block_data['html']) ? $block_data['html'] : '';
            }

            $newsletter_html .= '</div>';
        }

        return $newsletter_html;
    }
}



?>
