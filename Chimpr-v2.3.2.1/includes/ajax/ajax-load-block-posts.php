<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $date_range = isset($_POST['date_range']) ? intval($_POST['date_range']) : 7;
    $story_count = isset($_POST['story_count']) ? $_POST['story_count'] : 'disable';

    error_log("Loading posts with params: category=$category_id, block=$block_index, range=$date_range, count=$story_count");

    $posts_args = [
        'post_type'   => 'post',
        'cat'         => $category_id,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => ['publish', 'future'],
        'sort_order'  => 'DESC',
        'numberposts' => ($story_count === 'all') ? -1 : ($story_count === 'disable' ? 20 : intval($story_count)),
        'posts_per_page' => ($story_count === 'all') ? -1 : ($story_count === 'disable' ? 20 : intval($story_count))
    ];

    // Add date range filter
    if ($date_range > 0) {
        $today = current_time('Y-m-d');
        $past_date = date('Y-m-d', strtotime("-{$date_range} days"));
        $future_date = date('Y-m-d', strtotime("+{$date_range} days"));
        
        // Remove the date_query completely and use a custom filter
        add_filter('posts_where', function($where) use ($past_date, $future_date) {
            global $wpdb;
            $where .= $wpdb->prepare(
                " AND (
                    ($wpdb->posts.post_date >= %s AND $wpdb->posts.post_date <= %s)
                    OR
                    ($wpdb->posts.post_status = 'future' AND $wpdb->posts.post_date <= %s)
                )",
                $past_date,
                $future_date,
                $future_date
            );
            return $where;
        });
        
        $posts = get_posts($posts_args);
        
        // Remove our custom filter
        remove_all_filters('posts_where');
    } else {
        // If date_range is 0 (All), get all past and future posts
        add_filter('posts_where', function($where) {
            global $wpdb;
            $future_date = date('Y-m-d', strtotime("+1 year"));
            $where .= $wpdb->prepare(
                " AND (
                    $wpdb->posts.post_date <= %s
                    OR
                    ($wpdb->posts.post_status = 'future' AND $wpdb->posts.post_date <= %s)
                )",
                $future_date,
                $future_date
            );
            return $where;
        });
        
        $posts = get_posts($posts_args);
        
        // Remove our custom filter
        remove_all_filters('posts_where');
    }

    // Sort posts by date, putting future posts first
    usort($posts, function($a, $b) {
        // If one is future and one isn't, prioritize future
        $a_is_future = $a->post_status === 'future';
        $b_is_future = $b->post_status === 'future';
        
        if ($a_is_future && !$b_is_future) return -1;
        if (!$a_is_future && $b_is_future) return 1;
        
        // If both are future or both are published, sort by date
        return strtotime($b->post_date) - strtotime($a->post_date);
    });

    error_log('Found ' . count($posts) . ' posts, including scheduled posts');

    if ($posts) {
        $html = '<ul class="sortable-posts">';
        foreach ($posts as $index => $post) {
            $post_id = $post->ID;
            
            // Auto-check based on story count and order
            $checked = '';
            if ($story_count !== 'disable') {
                if ($story_count === 'all' || $index < intval($story_count)) {
                    $checked = 'checked';
                }
            }
            
            // Check if post is scheduled
            $is_scheduled = $post->post_status === 'future';
            $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
            
            error_log("Processing post $post_id at index $index with story_count $story_count - checked: $checked");
            
            $html .= '<li data-post-id="' . esc_attr($post_id) . '">';
            $html .= '<span class="dashicons dashicons-sort story-drag-handle"></span>';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][selected]" value="1" ' . $checked . '> ';
            
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            if ($thumbnail_url) {
                $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '" style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
            }
            $html .= esc_html($post->post_title) . $scheduled_label;
            $html .= '</label>';
            
            // Set order based on post position
            $order = ($story_count !== 'disable') ? $index : 0;
            $html .= '<input type="hidden" class="post-order" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" value="' . esc_attr($order) . '">';
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        
        if ($story_count !== 'disable') {
            $message = ($story_count === 'all') 
                ? esc_html__('Showing all posts in date range.', 'newsletter')
                : sprintf(
                    esc_html__('Showing %d most recent posts. Manual changes will switch to manual selection mode.', 'newsletter'),
                    intval($story_count)
                );
            $html .= '<p class="description">' . $message . '</p>';
        }
        
        wp_send_json_success($html);
    } else {
        wp_send_json_success('<p>' . esc_html__('No posts found in the selected date range.', 'newsletter') . '</p>');
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
