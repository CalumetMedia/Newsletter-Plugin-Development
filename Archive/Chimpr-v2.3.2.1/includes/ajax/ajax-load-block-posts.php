<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_action', 'security');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0; // Critical - maintain original index
    $date_range = isset($_POST['date_range']) ? intval($_POST['date_range']) : 7;
    $story_count = isset($_POST['story_count']) ? sanitize_text_field($_POST['story_count']) : 'all';

    // Add debug logging
    error_log("Loading block posts - Index: $block_index, Story Count: $story_count");

    // Keep track of original block data
    $output = '<input type="hidden" name="blocks[' . esc_attr($block_index) . '][original_index]" value="' . esc_attr($block_index) . '">';


    $posts_args = [
        'post_type'   => 'post',
        'category'    => $category_id,
        'numberposts' => 20,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => ['publish', 'future']
    ];

    // Add date range filter
    if ($date_range > 0) {
        $today = current_time('Y-m-d');
        $posts_args['date_query'] = [
            'relation' => 'OR',
            ['after' => date('Y-m-d', strtotime("-{$date_range} days")), 'before' => $today, 'inclusive' => true],
            ['after' => $today, 'before' => date('Y-m-d', strtotime("+1 year")), 'inclusive' => true]
        ];
    }

    $posts = get_posts($posts_args);
    $output = '';

    if ($posts) {
        // Sort posts by date first
        usort($posts, function($a, $b) {
            return strtotime($b->post_date) - strtotime($a->post_date);
        });

        $output .= '<ul class="sortable-posts">';
        foreach ($posts as $index => $post) {
            $post_id = $post->ID;
            $checked = ($story_count === 'all' || $index < intval($story_count)) ? 'checked' : '';
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '';
            $is_scheduled = $post->post_status === 'future';
            $post_date = get_the_date('M j, Y g:i A', $post);

            $output .= '<li data-post-id="' . esc_attr($post_id) . '">';
            $output .= '<span class="dashicons dashicons-sort story-drag-handle" style="cursor: move; margin-right: 10px;"></span>';
            $output .= '<label>';
            $output .= '<input type="checkbox" 
                              name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][selected]" 
                              value="1" ' . $checked . ' 
                              class="post-checkbox">';
            if ($thumbnail_url) {
                $output .= '<img src="' . esc_url($thumbnail_url) . '" 
                               alt="' . esc_attr($post->post_title) . '" 
                               style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
            }
            $output .= '<span class="post-title">' . esc_html($post->post_title);
            if ($is_scheduled) {
                $output .= '<span class="scheduled-label" 
                                style="display: inline-block; background: #e5e5e5; color: #135e96; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 8px;">
                                Scheduled for ' . esc_html($post_date) . '
                           </span>';
            }
            $output .= '</span></label>';
            $output .= '<input type="hidden" 
                              class="post-order" 
                              name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" 
                              value="' . esc_attr($index) . '">';
            $output .= '</li>';
        }
        $output .= '</ul>';
    } else {
        $output = '<p>' . esc_html__('No posts found in this category.', 'newsletter') . '</p>';
    }

    wp_send_json_success($output);
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
