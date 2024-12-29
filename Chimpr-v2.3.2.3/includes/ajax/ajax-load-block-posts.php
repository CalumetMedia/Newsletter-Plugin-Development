<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');
    error_log("Nonce check passed");

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $date_range = isset($_POST['date_range']) ? intval($_POST['date_range']) : 7;
    $story_count = isset($_POST['story_count']) ? $_POST['story_count'] : 'disable';
    $saved_selections = isset($_POST['saved_selections']) ? json_decode(stripslashes($_POST['saved_selections']), true) : [];

    error_log("Raw POST data: " . print_r($_POST, true));

    $posts_args = [
        'post_type'   => 'post',
        'cat'         => $category_id,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => ['publish', 'future'],
        'posts_per_page' => -1
    ];

    // Add date range filter
    if ($date_range > 0) {
        $today = current_time('Y-m-d H:i:s');
        $past_date = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        $future_date = date('Y-m-d H:i:s', strtotime("+7 days")); // Always look 7 days ahead for scheduled posts
        
        error_log("Date range filter: past_date=$past_date, today=$today, future_date=$future_date");
        
        $posts_args['date_query'] = [
            'relation' => 'OR',
            // Published posts within the selected date range
            [
                'after' => $past_date,
                'before' => $today,
                'inclusive' => true,
                'post_status' => 'publish',
            ],
            // Scheduled posts for the next 7 days (regardless of past date range)
            [
                'after' => $today,
                'before' => $future_date,
                'inclusive' => true,
                'post_status' => 'future'
            ]
        ];
    } else {
        // If date_range is 0 (All), get all published posts and next 7 days of scheduled posts
        $today = current_time('Y-m-d H:i:s');
        $future_date = date('Y-m-d H:i:s', strtotime("+7 days"));
        
        $posts_args['date_query'] = [
            'relation' => 'OR',
            [
                'before' => $today,
                'inclusive' => true,
                'post_status' => 'publish',
            ],
            [
                'after' => $today,
                'before' => $future_date,
                'inclusive' => true,
                'post_status' => 'future'
            ]
        ];
    }

    error_log("WP_Query args: " . print_r($posts_args, true));
    
    // Use WP_Query instead of get_posts for more control
    $query = new WP_Query($posts_args);
    error_log("SQL Query: " . $query->request);
    error_log("Found posts: " . $query->post_count);
    
    $posts = $query->posts;

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

    if ($posts) {
        $html = '<ul class="sortable-posts">';
        foreach ($posts as $index => $post) {
            $post_id = $post->ID;
            error_log("Processing post ID: $post_id, Date: {$post->post_date}, Status: {$post->post_status}");
            
            // Check if post was previously selected
            $was_selected = isset($saved_selections[$post_id]) && !empty($saved_selections[$post_id]['selected']);
            
            // Auto-check based on story count and order, or if previously selected
            $checked = '';
            if ($story_count === 'disable' || $was_selected || $index < intval($story_count)) {
                $checked = 'checked';
            }
            
            // Check if post is scheduled
            $is_scheduled = $post->post_status === 'future';
            $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
            
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
            
            // Set order based on saved selections or post position
            $order = isset($saved_selections[$post_id]['order']) ? $saved_selections[$post_id]['order'] : $index;
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
        
        error_log("Generated HTML length: " . strlen($html));
        wp_send_json_success($html);
    } else {
        wp_send_json_success('<p>' . esc_html__('No posts found in the selected date range.', 'newsletter') . '</p>');
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
