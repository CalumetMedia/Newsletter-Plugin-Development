<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

error_log('========== AJAX ENDPOINT TRIGGERED ==========');
error_log('REQUEST METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('POST DATA: ' . print_r($_POST, true));

function newsletter_load_block_posts() {
    error_log('========== FUNCTION CALLED ==========');
    try {
        // Verify this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
            wp_send_json_error('Invalid request method');
            return;
        }

        error_log('Checking nonce...');
        if (!isset($_POST['security'])) {
            error_log('Security nonce is missing');
            wp_send_json_error('Security check failed');
            return;
        }

        check_ajax_referer('load_block_posts_nonce', 'security');
        error_log('Nonce check passed');

        // Validate required parameters
        $required_params = ['category_id', 'block_index', 'date_range', 'story_count'];
        foreach ($required_params as $param) {
            if (!isset($_POST[$param])) {
                error_log("Missing required parameter: $param");
                wp_send_json_error("Missing required parameter: $param");
                return;
            }
        }

        $category_id = intval($_POST['category_id']);
        $block_index = intval($_POST['block_index']);
        $date_range = intval($_POST['date_range']);
        $story_count = $_POST['story_count'];
        $manual_override = isset($_POST['manual_override']) && $_POST['manual_override'] === 'true';
        
        error_log('Request parameters: ' . print_r([
            'category_id' => $category_id,
            'block_index' => $block_index,
            'date_range' => $date_range,
            'story_count' => $story_count,
            'manual_override' => $manual_override
        ], true));

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

        // Add initial debug log
        error_log('=================== NEWSLETTER DEBUG START ===================');
        error_log('Newsletter Debug: Function load_block_posts called');
        error_log('Newsletter Debug: Category ID: ' . $category_id);
        error_log('Newsletter Debug: Block Index: ' . $block_index);
        error_log('Newsletter Debug: Story Count: ' . $story_count);
        error_log('Newsletter Debug: Manual Override: ' . ($manual_override ? 'true' : 'false'));

        // After WP_Query
        error_log('Newsletter Debug: Number of posts found: ' . count($posts));
        
        if ($posts) {
            error_log('Newsletter Debug: Starting to process posts');
            
            // Before processing ordered posts
            error_log('Newsletter Debug: Number of posts in posts_map: ' . count($posts_map));
            error_log('Newsletter Debug: Number of normalized selections: ' . count($normalized_selections));
            
            // Before final loop
            error_log('Newsletter Debug: Number of ordered posts to process: ' . count($ordered_posts));
            
            $html = '<ul class="sortable-posts"' . (!$manual_override ? ' style="pointer-events: none; opacity: 0.7;"' : '') . '>';
            
            // Create a map of post IDs to posts for easier lookup
            $posts_map = array();
            foreach ($posts as $post) {
                $posts_map[$post->ID] = array(
                    'ID' => $post->ID,
                    'title' => $post->post_title,
                    'post_title' => $post->post_title,
                    'post_date' => $post->post_date,
                    'post_status' => $post->post_status,
                    'post_type' => $post->post_type
                );
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
                    return strtotime($b['post_date']) - strtotime($a['post_date']);
                });
                $ordered_posts = array_merge($ordered_posts, $remaining_posts);
            }
            
            // Output posts in final order
            foreach ($ordered_posts as $index => $post) {
                if (empty($post['ID'])) {
                    error_log('Newsletter Debug: Invalid post data at index ' . $index);
                    continue;
                }
                
                error_log(sprintf(
                    'Newsletter Debug: Processing post - ID: %d, Title: %s, Status: %s, Post Type: %s',
                    $post['ID'],
                    $post['title'],
                    $post['post_status'],
                    $post['post_type']
                ));

                $post_id = $post['ID'];
                $is_scheduled = $post['post_status'] === 'future';
                $scheduled_label = $is_scheduled ? '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>' : '';
                
                // Debug post data
                error_log(sprintf(
                    '[Newsletter Debug] Post %d - Title: %s, Status: %s',
                    $post_id,
                    $post['title'],
                    $post['post_status']
                ));

                $checked = '';
                if ($manual_override) {
                    // In manual mode, use saved selections
                    if (isset($normalized_selections[$post_id]) && $normalized_selections[$post_id]['checked'] === '1') {
                        $checked = 'checked="checked"';
                    }
                } else {
                    // In automatic mode, check based on story count
                    if ($story_count === 'disable' || $index < intval($story_count)) {
                        $checked = 'checked="checked"';
                    }
                }
                
                $order_value = isset($normalized_selections[$post_id]['order']) ? $normalized_selections[$post_id]['order'] : $index;

                // Create HTML for debugging
                $debug_html = sprintf(
                    '<li class="story-item" data-post-id="%d" data-post-date="%s">
                        <span class="dashicons dashicons-sort story-drag-handle"></span>
                        <label class="story-label">
                            <input type="checkbox" name="blocks[%d][posts][%d][checked]" value="1" %s>
                            <span class="post-title">%s%s</span>
                        </label>
                        <input type="hidden" name="blocks[%d][posts][%d][order]" value="%s" class="post-order">
                    </li>',
                    $post_id,
                    $post['post_date'],
                    $block_index,
                    $post_id,
                    $checked,
                    esc_html($post['title']),
                    $scheduled_label,
                    $block_index,
                    $post_id,
                    $order_value
                );

                // Log the generated HTML for inspection
                error_log('[Newsletter Debug] Generated HTML for post ' . $post_id . ': ' . $debug_html);

                $html .= $debug_html;
                
                // After generating HTML for each post
                error_log('Newsletter Debug: Generated HTML length: ' . strlen($debug_html));
            }
            
            // Before sending response
            error_log('Newsletter Debug: Final HTML length: ' . strlen($html));
            error_log('=================== NEWSLETTER DEBUG END ===================');

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

function register_newsletter_ajax_endpoints() {
    static $registered = false;
    
    if ($registered) {
        return;
    }
    
    error_log('Registering newsletter AJAX endpoints');
    add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
    add_action('wp_ajax_nopriv_load_block_posts', 'newsletter_load_block_posts');
    
    // Add nonce creation
    add_action('admin_footer', function() {
        ?>
        <script type="text/javascript">
            if (typeof newsletterAjaxNonce === 'undefined') {
                var newsletterAjaxNonce = '<?php echo wp_create_nonce("load_block_posts_nonce"); ?>';
            }
        </script>
        <?php
    });
    
    $registered = true;
}

// Move registration to admin_init to ensure it only runs in admin context
add_action('admin_init', 'register_newsletter_ajax_endpoints');
