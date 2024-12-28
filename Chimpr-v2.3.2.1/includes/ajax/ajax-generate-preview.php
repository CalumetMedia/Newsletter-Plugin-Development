<?php
// includes/ajax/ajax-generate-preview.php
if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler to Generate Preview
 */
function newsletter_generate_preview() {
    check_ajax_referer('generate_preview_nonce', 'security');

    $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
    
    // Get both saved blocks and current selections
    $saved_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $saved_selections = isset($_POST['saved_selections']) ? json_decode(stripslashes($_POST['saved_selections']), true) : [];

    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $custom_header = isset($_POST['custom_header']) ? wp_kses_post($_POST['custom_header']) : '';
    $custom_footer = isset($_POST['custom_footer']) ? wp_kses_post($_POST['custom_footer']) : '';
    $custom_css = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';

    if (!empty($custom_header)) {
        update_option("newsletter_custom_header_$newsletter_slug", $custom_header);
    }
    if (!empty($custom_footer)) {
        update_option("newsletter_custom_footer_$newsletter_slug", $custom_footer);
    }
    if (!empty($custom_css)) {
        update_option("newsletter_custom_css_$newsletter_slug", $custom_css);
    }

    $sanitized_blocks = [];
    foreach ($blocks as $index => $block) {
        $sanitized_block = [
            'type'        => sanitize_text_field($block['type']),
            'title'       => sanitize_text_field($block['title']),
            'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
            'show_title'  => isset($block['show_title']),
            'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'disable',
            'date_range'  => isset($block['date_range']) ? intval($block['date_range']) : 7
        ];

        if ($sanitized_block['type'] === 'content') {
            $sanitized_block['category'] = isset($block['category']) ? intval($block['category']) : 0;
            $sanitized_block['date_range'] = isset($block['date_range']) ? intval($block['date_range']) : 7;
            $sanitized_block['posts'] = [];

            // Get current selections and saved posts
            $current_selections = isset($saved_selections[$index]['posts']) ? $saved_selections[$index]['posts'] : [];
            $saved_posts = [];
            foreach ($saved_blocks as $saved_block) {
                if ($saved_block['title'] === $sanitized_block['title'] && $saved_block['type'] === 'content') {
                    $saved_posts = $saved_block['posts'] ?? [];
                    break;
                }
            }
            
            if (!empty($block['posts'])) {
                // Get all posts for this category and date range
                $args = array(
                    'posts_per_page' => -1,
                    'category' => $sanitized_block['category'],
                    'date_query' => array(
                        'after' => date('Y-m-d', strtotime("-{$sanitized_block['date_range']} days"))
                    ),
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                
                $query = new WP_Query($args);
                $posts = $query->posts;
                
                // Handle story count and selections
                $story_count = $sanitized_block['story_count'];
                $count = ($story_count === 'disable') ? 0 : intval($story_count);
                $current_count = 0;

                foreach ($posts as $post) {
                    $post_id = $post->ID;
                    
                    // Post should be selected if:
                    // 1. It's in current selections (manually checked)
                    // 2. It's in saved posts (previously saved)
                    // 3. It should be preselected based on story count
                    if (isset($current_selections[$post_id]) || 
                        isset($saved_posts[$post_id]) ||
                        ($count > 0 && $current_count < $count)) {
                        
                        $sanitized_block['posts'][$post_id] = [
                            'selected' => true,
                            'order' => isset($current_selections[$post_id]['order']) 
                                ? intval($current_selections[$post_id]['order'])
                                : (isset($saved_posts[$post_id]['order']) 
                                    ? intval($saved_posts[$post_id]['order'])
                                    : $current_count)
                        ];
                        $current_count++;
                    }
                }
            }
        } elseif ($sanitized_block['type'] === 'html') {
            $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
        } elseif ($sanitized_block['type'] === 'wysiwyg') {
            $sanitized_block['wysiwyg'] = wp_kses_post($block['wysiwyg'] ?? '');
        }

        $sanitized_blocks[] = $sanitized_block;
    }

    $preview_content = newsletter_generate_preview_content($newsletter_slug, $sanitized_blocks);

    $preview_html = '<div class="newsletter-preview-container">';
    if (!empty($custom_css)) {
        $preview_html .= '<style type="text/css">';
        $preview_html .= '.newsletter-preview-container {' . $custom_css . '}';
        $preview_html .= '</style>';
    }
    $preview_html .= '<div class="newsletter-content">';
    $preview_html .= $preview_content;
    $preview_html .= '</div></div>';

    wp_send_json_success($preview_html);
}
add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');
