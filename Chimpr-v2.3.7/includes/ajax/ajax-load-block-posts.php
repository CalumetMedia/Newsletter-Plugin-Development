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
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Set a reasonable posts per page limit
        $posts_per_page = 20;

        // Build the WP_Query arguments with pagination
        $posts_args = [
            'post_type'   => 'post',
            'cat'         => $category_id,
            'post_status' => ['publish', 'future'],
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'no_found_rows' => false, // We need this for pagination
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'orderby' => 'date',
            'order' => 'DESC'
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
        }

        // Execute query
        $query = new WP_Query($posts_args);
        $posts = $query->posts;

        if ($posts) {
            $max_pages = $query->max_num_pages;
            $total_posts = $query->found_posts;

            $html = '<div class="posts-container">';
            
            if ($paged === 1) {
                $html .= '<ul class="sortable-posts"' . (!$manual_override ? ' style="pointer-events: none; opacity: 0.7;"' : '') . '>';
            }

            foreach ($posts as $index => $post) {
                $post_id = $post->ID;
                $is_scheduled = $post->post_status === 'future';
                $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
                
                $checked = '';
                if ($manual_override) {
                    if (isset($normalized_selections[$post_id]) && $normalized_selections[$post_id]['checked'] === '1') {
                        $checked = 'checked="checked"';
                    }
                } else {
                    if ($story_count === 'disable' || $index < intval($story_count)) {
                        $checked = 'checked="checked"';
                    }
                }

                $html .= sprintf(
                    '<li class="story-item" data-post-id="%d" data-post-date="%s">
                        <span class="dashicons dashicons-sort story-drag-handle"></span>
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
                    PHP_INT_MAX - strtotime($post->post_date),
                    esc_html($post->post_title),
                    $scheduled_label
                );
            }

            if ($paged === 1) {
                $html .= '</ul>';
            }

            // Add load more button if there are more pages
            if ($paged < $max_pages) {
                $html .= sprintf(
                    '<button type="button" class="button load-more-posts" 
                            data-page="%d" 
                            data-category="%d" 
                            data-block-index="%d" 
                            data-date-range="%d">
                        Load More Posts
                    </button>',
                    $paged + 1,
                    $category_id,
                    $block_index,
                    $date_range
                );
            }

            $html .= sprintf(
                '<p class="posts-count">Showing %d of %d posts</p>',
                min($paged * $posts_per_page, $total_posts),
                $total_posts
            );

            $html .= '</div>';
            
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

    // Get existing blocks
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    
    if ($block_type === 'wysiwyg') {
        $content = isset($blocks[$block_index]['wysiwyg']) ? wp_kses_post($blocks[$block_index]['wysiwyg']) : '';
        
        ob_start();
        ?>
        <label><?php esc_html_e('WYSIWYG Content:', 'newsletter'); ?></label>
        <?php
        $editor_id = 'wysiwyg-editor-' . $block_index;
        wp_editor(
            $content,
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
    elseif ($block_type === 'html') {
        $content = isset($blocks[$block_index]['html']) ? wp_kses_post($blocks[$block_index]['html']) : '';
        
        ob_start();
        ?>
        <label><?php esc_html_e('HTML Content:', 'newsletter'); ?></label>
        <textarea 
            id="html-editor-<?php echo esc_attr($block_index); ?>"
            name="blocks[<?php echo esc_attr($block_index); ?>][html]"
            class="html-editor-content"
            rows="15"
        ><?php echo esc_textarea($content); ?></textarea>
        <?php
        $content = ob_get_clean();
        wp_send_json_success($content);
    }

    wp_send_json_error('Invalid block type');
}
add_action('wp_ajax_load_block_content', 'newsletter_load_block_content');
