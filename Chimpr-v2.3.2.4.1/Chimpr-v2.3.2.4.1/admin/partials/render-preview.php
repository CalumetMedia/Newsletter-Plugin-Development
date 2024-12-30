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

    // Process each content block to maintain selected posts
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
                    'category' => $block['category'],
                    'date_query' => array(
                        'after' => date('Y-m-d', strtotime("-{$block['date_range']} days"))
                    ),
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                
                $query = new WP_Query($args);
                $current_count = 0;
                
                foreach ($query->posts as $post) {
                    $post_id = $post->ID;
                    // Only include posts that are explicitly checked in manual mode
                    if (isset($selected_posts[$post_id]) && isset($selected_posts[$post_id]['checked']) && $selected_posts[$post_id]['checked'] === '1') {
                        $block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => isset($selected_posts[$post_id]['order']) 
                                ? $selected_posts[$post_id]['order'] 
                                : $current_count
                        ];
                        $current_count++;
                    } elseif (!$block['manual_override'] && $count > 0 && $current_count < $count) {
                        // In automatic mode, include posts based on story count
                        $block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => $current_count
                        ];
                        $current_count++;
                    }
                }
            }
        }
    }
    unset($block);

    return $blocks;
}

// Main logic
$newsletter_slug = get_valid_newsletter_slug();
$blocks = get_newsletter_blocks_by_slug($newsletter_slug);
$preview_html = newsletter_generate_preview_content($newsletter_slug, $blocks);

// Output the generated preview
echo !empty($preview_html) ? $preview_html : '<p class="error">' . esc_html__('Unable to generate preview content.', 'newsletter') . '</p>';