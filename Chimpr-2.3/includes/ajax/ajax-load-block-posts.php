<?php
// includes/ajax/ajax-load-block-posts.php
if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler to Load Block Posts
 */
function newsletter_load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $block_index = isset($_POST['block_index']) ? intval($_POST['block_index']) : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

    $posts_args = [
        'cat' => $category_id,
        'numberposts' => 15,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish',
    ];

    if (!empty($start_date) && !empty($end_date)) {
        $posts_args['date_query'] = array(
            array(
                'after'     => $start_date . ' 00:00:00',
                'before'    => $end_date . ' 23:59:59',
                'inclusive' => true,
            ),
        );
    }

    $posts = get_posts($posts_args);

    if ($posts) {
        $html = '<ul class="sortable-posts">';
        foreach ($posts as $post) {
            $post_id = $post->ID;
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '';
            $post_title = get_the_title($post_id);

            $html .= '<li data-post-id="' . esc_attr($post_id) . '">';
            $html .= '<span class="dashicons dashicons-menu drag-handle" style="cursor: move; margin-right: 10px;"></span>';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][selected]" value="1"> ';
            if ($thumbnail_url) {
                $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post_title) . '" style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
            }
            $html .= esc_html($post_title);
            $html .= '</label>';
            $html .= '<input type="hidden" class="post-order" name="blocks[' . esc_attr($block_index) . '][posts][' . esc_attr($post_id) . '][order]" value="0">';
            $html .= '</li>';
        }
        $html .= '</ul>';
        wp_send_json_success($html);
    } else {
        wp_send_json_success('<p class="no-posts-message" style="padding: 10px; color: #666; font-style: italic;">No posts match your date range criteria.</p>');
    }
}
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
