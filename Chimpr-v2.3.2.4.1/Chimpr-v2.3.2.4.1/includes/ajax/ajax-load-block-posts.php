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

        // Build the WP_Query arguments
        $posts_args = [
            'post_type'   => 'post',
            'cat'         => $category_id,
            'post_status' => ['publish', 'future'],
            'posts_per_page' => -1,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ];

        // Add date range filter
        $today = current_time('Y-m-d H:i:s');
        $future_date = date('Y-m-d H:i:s', strtotime("+7 days")); 

        if ($date_range > 0) {
            $past_date = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
            
            $posts_args['date_query'] = [
                'relation' => 'OR',
                [
                    'after' => $past_date,
                    'before' => $today,
                    'inclusive' => true,
                    'column' => 'post_date'
                ],
                [
                    'after' => $today,
                    'before' => $future_date,
                    'inclusive' => true,
                    'column' => 'post_date'
                ]
            ];
        } else {
            $posts_args['date_query'] = [
                'relation' => 'OR',
                [
                    'before' => $today,
                    'inclusive' => true,
                    'column' => 'post_date'
                ],
                [
                    'after' => $today,
                    'before' => $future_date,
                    'inclusive' => true,
                    'column' => 'post_date'
                ]
            ];
        }

        // Execute query
        $query = new WP_Query($posts_args);
        $posts = $query->posts;

        error_log('Newsletter Load - Block ' . $block_index . ' found ' . count($posts) . ' posts');

        if ($posts) {
            $html = '<ul class="sortable-posts"' . (!$manual_override ? ' style="pointer-events: none; opacity: 0.7;"' : '') . '>';
            
            // Create a map of post IDs to posts for easier lookup
            $posts_map = array();
            foreach ($posts as $post) {
                $posts_map[$post->ID] = $post;
            }
            
            // First, add posts that have saved selections in the correct order
            $ordered_posts = array();
            if (!empty($normalized_selections)) {
                // Sort normalized selections by order
                uasort($normalized_selections, function($a, $b) {
                    return intval($a['order']) - intval($b['order']);
                });
                
                // Add selected posts first, in their saved order
                foreach ($normalized_selections as $post_id => $selection) {
                    if (isset($posts_map[$post_id]) && $selection['checked'] === '1') {
                        $ordered_posts[] = $posts_map[$post_id];
                        unset($posts_map[$post_id]);
                    }
                }
            }
            
            // Then add remaining posts sorted by date
            if (!empty($posts_map)) {
                $remaining_posts = array_values($posts_map);
                usort($remaining_posts, function($a, $b) {
                    return strtotime($b->post_date) - strtotime($a->post_date);
                });
                $ordered_posts = array_merge($ordered_posts, $remaining_posts);
            }
            
            // Output posts in final order
            foreach ($ordered_posts as $index => $post) {
                $post_id = $post->ID;
                $is_scheduled = $post->post_status === 'future';
                $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
                
                $checked = '';
                if ($manual_override) {
                    $checked = (isset($normalized_selections[$post_id]) && 
                              $normalized_selections[$post_id]['checked'] === '1') ? 'checked' : '';
                } else {
                    // In automatic mode, check based on story count
                    $checked = ($story_count === 'disable' || $index < intval($story_count)) ? 'checked' : '';
                }
                
                $order_value = isset($normalized_selections[$post_id]) ? 
                    $normalized_selections[$post_id]['order'] : $index;

                $html .= sprintf(
                    '<li class="sortable-post-item" data-post-id="%d" data-post-date="%s">
                        <span class="dashicons dashicons-menu story-drag-handle"></span>
                        <input type="checkbox" name="blocks[%d][posts][%d][checked]" value="1" %s>
                        <input type="hidden" name="blocks[%d][posts][%d][order]" value="%s" class="post-order">
                        <label>%s%s</label>
                    </li>',
                    $post_id,
                    $post->post_date,
                    $block_index,
                    $post_id,
                    $checked,
                    $block_index,
                    $post_id,
                    $order_value,
                    esc_html($post->post_title),
                    $scheduled_label
                );
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

function newsletter_load_block_content() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $block_type = isset($_POST['block_type']) ? sanitize_text_field($_POST['block_type']) : '';
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';

    if ($block_type === 'wysiwyg') {
        // Get existing blocks
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        $wysiwyg_content = isset($blocks[$block_index]['wysiwyg']) ? wp_kses_post($blocks[$block_index]['wysiwyg']) : '';
        
        ob_start();
        ?>
        <label><?php esc_html_e('WYSIWYG Content:', 'newsletter'); ?></label>
        <?php
        $editor_id = 'wysiwyg-editor-' . $block_index;
        wp_editor(
            $wysiwyg_content,
            $editor_id,
            array(
                'textarea_name' => 'blocks[' . esc_attr($block_index) . '][wysiwyg]',
                'media_buttons' => true,
                'textarea_rows' => 15,
                'editor_class' => 'wysiwyg-editor-content',
                'tinymce' => array(
                    'wpautop' => true,
                    'plugins' => 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                    'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr'
                ),
                'quicktags' => true
            )
        );
        $content = ob_get_clean();
        wp_send_json_success($content);
    }

    wp_send_json_error('Invalid block type');
}
add_action('wp_ajax_load_block_content', 'newsletter_load_block_content');
