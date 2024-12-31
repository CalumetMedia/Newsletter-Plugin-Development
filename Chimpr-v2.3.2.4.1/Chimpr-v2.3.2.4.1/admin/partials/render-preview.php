<?php
// admin/partials/render-preview.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Retrieve and validate the newsletter slug.
 *
 * @return string Validated newsletter slug.
 */
function get_valid_newsletter_slug() {
    $slug = '';

    // Extract slug from various sources
    if (!empty($_GET['newsletter_slug'])) {
        $slug = sanitize_text_field($_GET['newsletter_slug']);
    } elseif (!empty($_GET['slug'])) {
        $slug = sanitize_text_field($_GET['slug']);
    } elseif (!empty($_GET['tab'])) {
        $slug = sanitize_text_field($_GET['tab']);
    } elseif (!empty($_GET['page'])) {
        $page = sanitize_text_field($_GET['page']);
        if (strpos($page, 'newsletter-stories-') === 0) {
            $slug = str_replace('newsletter-stories-', '', $page);
        }
    }

    // Retrieve and validate against newsletter_list
    $newsletter_list = get_option('newsletter_list', []);
    if (!is_array($newsletter_list)) {
        error_log("newsletter_list option is not an array. Re-initializing with 'default'.");
        $newsletter_list = ['default' => 'Default Newsletter'];
        update_option('newsletter_list', $newsletter_list);
    }

    // Default slug if invalid or not provided
    if (empty($slug) || !array_key_exists($slug, $newsletter_list)) {
        $slug = array_key_first($newsletter_list) ?: 'default';
        if (!array_key_exists('default', $newsletter_list)) {
            $newsletter_list['default'] = __('Default Newsletter', 'newsletter');
            update_option('newsletter_list', $newsletter_list);
            error_log("Added 'default' to newsletter_list.");
        }
    }

    return $slug;
}

/**
 * Retrieve the blocks for the given newsletter slug.
 *
 * @param string $slug The newsletter slug.
 * @return array The blocks associated with the newsletter.
 */
function get_newsletter_blocks_by_slug($slug) {
    $blocks_option = "newsletter_blocks_$slug";
    $blocks = get_option($blocks_option, []);

    if (!is_array($blocks)) {
        error_log("Blocks for slug '$slug' are not an array. Initializing as empty array.");
        $blocks = [];
        update_option($blocks_option, $blocks);
    }

    error_log("Retrieved blocks for slug '$slug': " . print_r($blocks, true));

    // Process each block to maintain all necessary data
    foreach ($blocks as &$block) {
        if ($block['type'] === 'content') {
            $story_count = isset($block['story_count']) ? $block['story_count'] : 'disable';
            $count = ($story_count === 'disable') ? 0 : intval($story_count);
            
            // If there are saved post selections, maintain them
            if (!empty($block['posts'])) {
                $selected_posts = $block['posts'];
                
                // Get all available posts for the block's category and date range
                $args = array(
                    'posts_per_page' => -1,
                    'category' => isset($block['category']) ? $block['category'] : '',
                    'date_query' => array(
                        'after' => isset($block['date_range']) ? date('Y-m-d', strtotime("-{$block['date_range']} days")) : date('Y-m-d', strtotime('-7 days'))
                    ),
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                
                $query = new WP_Query($args);
                $posts = $query->posts;
                
                // Sort posts based on the saved order values
                if (!empty($selected_posts)) {
                    usort($posts, function($a, $b) use ($selected_posts) {
                        $order_a = isset($selected_posts[$a->ID]['order']) ? intval($selected_posts[$a->ID]['order']) : PHP_INT_MAX;
                        $order_b = isset($selected_posts[$b->ID]['order']) ? intval($selected_posts[$b->ID]['order']) : PHP_INT_MAX;
                        return $order_a - $order_b;
                    });
                }
                
                $current_count = 0;
                $block['posts'] = [];  // Reset posts array to maintain order
                
                foreach ($posts as $post) {
                    $post_id = $post->ID;
                    // Only include posts that are explicitly checked in manual mode
                    if (isset($selected_posts[$post_id]) && isset($selected_posts[$post_id]['checked']) && $selected_posts[$post_id]['checked'] === '1') {
                        $block['posts'][$post_id] = [
                            'checked' => '1',
                            'order' => isset($selected_posts[$post_id]['order']) 
                                ? $selected_posts[$post_id]['order'] 
                                : $current_count
                        ];
                        $current_count++;
                    } elseif (!isset($block['manual_override']) || !$block['manual_override']) {
                        // In automatic mode, include posts based on story count
                        if ($count <= 0 || $current_count < $count) {
                            $block['posts'][$post_id] = [
                                'checked' => '1',
                                'order' => $current_count
                            ];
                            $current_count++;
                        }
                    }
                }
            }
        } elseif ($block['type'] === 'wysiwyg') {
            error_log("Processing WYSIWYG block: " . print_r($block, true));
            // Always preserve WYSIWYG content
            if (isset($block['wysiwyg'])) {
                // Preserve HTML formatting while ensuring security
                $content = wp_unslash($block['wysiwyg']);
                if (!empty($content) && strpos($content, '<p>') === false) {
                    $content = wpautop($content);
                }
                $block['wysiwyg'] = wp_kses_post($content);
                error_log("Processed WYSIWYG content length: " . strlen($block['wysiwyg']));
            } else {
                $block['wysiwyg'] = '';
                error_log("Empty WYSIWYG content in block");
            }
        } elseif ($block['type'] === 'html') {
            // Always preserve HTML content
            if (isset($block['html'])) {
                $block['html'] = wp_kses_post(wp_unslash($block['html']));
                error_log("Processed HTML block content");
            } else {
                $block['html'] = '';
                error_log("Empty HTML content in block");
            }
        }
        
        // Ensure all blocks have common fields
        $block['show_title'] = isset($block['show_title']) ? (bool)$block['show_title'] : true;
        $block['template_id'] = isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default';
        $block['block_title'] = isset($block['title']) ? sanitize_text_field($block['title']) : '';
    }
    unset($block);

    error_log("Processed blocks for preview: " . print_r($blocks, true));
    return $blocks;
}

// Main logic
$newsletter_slug = get_valid_newsletter_slug();
$blocks = get_newsletter_blocks_by_slug($newsletter_slug);
error_log("Generating preview for blocks: " . print_r($blocks, true));
$preview_html = newsletter_generate_preview_content($newsletter_slug, $blocks);

// Output the generated preview
if (!empty($preview_html)) {
    echo $preview_html;
} else {
    error_log("Preview generation failed - empty preview HTML");
    echo '<p class="error">' . esc_html__('Unable to generate preview content.', 'newsletter') . '</p>';
}