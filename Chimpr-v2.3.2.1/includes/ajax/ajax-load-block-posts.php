<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $date_range = isset($_POST['date_range']) ? intval($_POST['date_range']) : 7;

    // If you want previously selected IDs passed from JS, handle that here. For simplicity, not required.
    // If you'd like them, you can pass previously_selected from JS and check them.
    // Example: $previously_selected = isset($_POST['previously_selected']) ? array_map('intval', $_POST['previously_selected']) : [];

    $posts_args = [
        'post_type'   => 'post',
        'cat'         => $category_id,
        'numberposts' => 20,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => 'publish'
    ];

    if ($date_range > 0) {
        $posts_args['date_query'] = [
            [
                'after'     => date('Y-m-d', strtotime("-{$date_range} days")),
                'before'    => date('Y-m-d'),
                'inclusive' => true,
            ],
        ];
    }

    $posts = get_posts($posts_args);

    if ($posts) {
        $html = '<ul class="sortable-posts">';
        foreach ($posts as $post) {
            $post_id = $post->ID;
            // If previously_selected is implemented, you could do:
            // $checked = in_array($post_id, $previously_selected) ? 'checked' : '';
            // For now, no persistence here. The user must re-check after load unless we store previously_selected in JS.
            $html .= '<li data-post-id="' . esc_attr($post_id) . '">';
            $html .= '<span class="dashicons dashicons-sort story-drag-handle"></span>';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][selected]" value="1"> ';
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            if ($thumbnail_url) {
                $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '" style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
            }
            $html .= esc_html($post->post_title);
            $html .= '</label>';
            $html .= '<input type="hidden" class="post-order" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" value="0">';
            $html .= '</li>';
        }
        $html .= '</ul>';
        wp_send_json_success($html);
    } else {
        wp_send_json_success('<p>' . esc_html__('No posts found in the selected date range.', 'newsletter') . '</p>');
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
