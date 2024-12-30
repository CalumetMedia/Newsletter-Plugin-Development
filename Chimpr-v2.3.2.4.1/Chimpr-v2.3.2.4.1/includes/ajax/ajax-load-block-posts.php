<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

function newsletter_load_block_posts() {
    try {
        check_ajax_referer('load_block_posts_nonce', 'security');

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
        $date_range = isset($_POST['date_range']) ? intval($_POST['date_range']) : 7;
        $story_count = isset($_POST['story_count']) ? $_POST['story_count'] : 'disable';
        $manual_override = isset($_POST['manual_override']) && $_POST['manual_override'] === 'true';
        
        // Validate and decode saved selections with error handling
        $saved_selections = [];
        if (isset($_POST['saved_selections'])) {
            $decoded = json_decode(stripslashes($_POST['saved_selections']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Newsletter load block posts - JSON decode error: ' . json_last_error_msg());
                wp_send_json_error('Invalid saved selections format');
                return;
            }
            $saved_selections = $decoded;
        }

        if (!$category_id) {
            wp_send_json_error('Invalid category ID');
            return;
        }

        // Get the current blocks from the database
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        $current_blocks = get_option("newsletter_blocks_$newsletter_slug", []);

        // Validate current blocks format
        if (!is_array($current_blocks)) {
            error_log('Newsletter load block posts - Invalid blocks format in database');
            wp_send_json_error('Invalid blocks format in database');
            return;
        }

        // Normalize saved selections to a consistent format
        $normalized_selections = [];
        if (!empty($saved_selections)) {
            if (isset($saved_selections[$block_index]['posts']) && is_array($saved_selections[$block_index]['posts'])) {
                foreach ($saved_selections[$block_index]['posts'] as $post_id => $data) {
                    if (!is_array($data)) {
                        continue;
                    }
                    // Only store checked posts
                    if (isset($data['checked']) && $data['checked'] === '1') {
                        $normalized_selections[$post_id] = [
                            'checked' => '1',
                            'order' => isset($data['order']) ? intval($data['order']) : PHP_INT_MAX
                        ];
                    }
                }
            }
        }

        // If we have saved blocks, merge with normalized selections
        if (!empty($current_blocks[$block_index]['posts']) && is_array($current_blocks[$block_index]['posts'])) {
            foreach ($current_blocks[$block_index]['posts'] as $post_id => $post_data) {
                // Only store checked posts that aren't already in normalized selections
                if (!isset($normalized_selections[$post_id]) && is_array($post_data) && isset($post_data['checked']) && $post_data['checked'] === '1') {
                    $normalized_selections[$post_id] = [
                        'checked' => '1',
                        'order' => isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX
                    ];
                }
            }
        }

        error_log('Newsletter Load - Block ' . $block_index . ' normalized selections: ' . print_r($normalized_selections, true));

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
        
        // Use WP_Query instead of get_posts for more control
        $query = new WP_Query($posts_args);
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
            $html = '<ul class="sortable-posts"' . (!$manual_override ? ' style="pointer-events: none; opacity: 0.7;"' : '') . '>';
            
            $selected_posts = [];
            $post_orders = [];
            $posts_to_display = $posts; // Default to date-sorted posts
            
            // Process saved selections if they exist
            if (!empty($normalized_selections)) {
                foreach ($posts as $post) {
                    $post_id = $post->ID;
                    // Only consider posts that are explicitly checked
                    if (isset($normalized_selections[$post_id]) && $normalized_selections[$post_id]['checked'] === '1') {
                        $selected_posts[] = $post_id;
                        $post_orders[$post_id] = $normalized_selections[$post_id]['order'];
                    }
                }

                // Only sort by saved order if we have saved orders and are in manual mode
                if (!empty($post_orders) && $manual_override) {
                    usort($posts_to_display, function($a, $b) use ($post_orders) {
                        $order_a = isset($post_orders[$a->ID]) ? $post_orders[$a->ID] : PHP_INT_MAX;
                        $order_b = isset($post_orders[$b->ID]) ? $post_orders[$b->ID] : PHP_INT_MAX;
                        if ($order_a === $order_b) {
                            return strtotime($b->post_date) - strtotime($a->post_date);
                        }
                        return $order_a - $order_b;
                    });
                }
            }
            
            foreach ($posts_to_display as $index => $post) {
                $post_id = $post->ID;
                $is_scheduled = $post->post_status === 'future';
                $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
                
                $checked = '';
                if ($manual_override) {
                    // In manual mode, only checked if explicitly set to '1'
                    $checked = (isset($normalized_selections[$post_id]) && 
                              $normalized_selections[$post_id]['checked'] === '1') ? 'checked' : '';
                } else {
                    // In automatic mode, select based on story count
                    if ($story_count !== 'disable') {
                        $checked = ($index < intval($story_count)) ? 'checked' : '';
                    } else {
                        $checked = 'checked';
                    }
                }
                
                $html .= '<li data-post-id="' . esc_attr($post_id) . '" class="sortable-post-item">';
                $html .= '<span class="dashicons dashicons-sort story-drag-handle" style="cursor: ' . (!$manual_override ? 'default' : 'move') . '; margin-right: 10px;"></span>';
                $html .= '<label>';
                $html .= '<input type="checkbox" 
                                name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][checked]" 
                                value="1" 
                                ' . $checked . '
                                ' . (!$manual_override ? 'disabled' : '') . '> ';
                
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
                if ($thumbnail_url) {
                    $html .= '<img src="' . esc_url($thumbnail_url) . '" 
                                  alt="' . esc_attr($post->post_title) . '" 
                                  style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
                }
                
                $html .= esc_html($post->post_title) . $scheduled_label;
                $html .= '</label>';
                
                // Always use the current index for order when manual override is disabled
                $order = ($manual_override && isset($post_orders[$post_id])) ? $post_orders[$post_id] : $index;
                
                $html .= '<input type="hidden" 
                                class="post-order" 
                                name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" 
                                value="' . esc_attr($order) . '">';
                
                $html .= '</li>';
            }
            $html .= '</ul>';
            
            if ($story_count !== 'disable') {
                $message = sprintf(
                    esc_html__('Showing %d most recent posts. Manual changes will switch to manual selection mode.', 'newsletter'),
                    intval($story_count)
                );
                $html .= '<p class="description">' . $message . '</p>';
            } else {
                $html .= '<p class="description">' . esc_html__('Showing all posts in date range.', 'newsletter') . '</p>';
            }
            
            wp_send_json_success($html);
        } else {
            wp_send_json_success('<p>' . esc_html__('No posts found in the selected date range.', 'newsletter') . '</p>');
        }
    } catch (Exception $e) {
        error_log('Newsletter load block posts error: ' . $e->getMessage());
        wp_send_json_error('Error loading block posts: ' . $e->getMessage());
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
