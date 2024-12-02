<?php
// admin/partials/block-item.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Ensure $block and $index are defined
if (!isset($block) || !isset($index)) {
    return;
}
?>
<div class="block-item" data-index="<?php echo esc_attr($index); ?>">
    <h3 class="block-header"><?php echo esc_html($block['title'] ?: __('Block', 'newsletter') . ' ' . ($index + 1)); ?></h3>
    <div class="block-content">
        <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
        <input type="text" name="blocks[<?php echo esc_attr($index); ?>][title]" class="block-title-input" value="<?php echo esc_attr($block['title']); ?>" />

        <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
        <select name="blocks[<?php echo esc_attr($index); ?>][type]" class="block-type">
            <option value="content" <?php selected($block['type'], 'content'); ?>><?php esc_html_e('Content', 'newsletter'); ?></option>
            <option value="advertising" <?php selected($block['type'], 'advertising'); ?>><?php esc_html_e('Advertising', 'newsletter'); ?></option>
        </select>

        <!-- Content Block -->
        <div class="content-block" <?php if ($block['type'] !== 'content') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
            <select name="blocks[<?php echo esc_attr($index); ?>][category]" class="block-category">
                <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                <?php
                if (!empty($all_categories)) {
                    foreach ($all_categories as $category) {
                        $selected = ($block['category'] == $category->term_id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                    }
                } else {
                    echo '<option value="">' . esc_html__('No categories available.', 'newsletter') . '</option>';
                }
                ?>
            </select>

            <!-- Posts Selection -->
            <div class="block-posts">
                <h4><?php esc_html_e('Select Posts:', 'newsletter'); ?></h4>
                <?php
                if ($block['category'] > 0) {
                    $posts_args = [
                        'category'    => $block['category'],
                        'numberposts' => -1,
                        'orderby'     => 'date',
                        'order'       => 'DESC',
                    ];
                    // Apply date filters if set (from the main form)
                    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
                        $posts_args['date_query'] = [
                            [
                                'after'     => sanitize_text_field($_POST['start_date']),
                                'before'    => sanitize_text_field($_POST['end_date']),
                                'inclusive' => true,
                            ],
                        ];
                    }
                    $posts = get_posts($posts_args);
                    if ($posts) {
                        // Ensure $block['posts'] is an array
                        $selected_posts = isset($block['posts']) ? $block['posts'] : [];

                        // Sort posts based on user-defined order
                        usort($posts, function($a, $b) use ($selected_posts) {
                            $order_a = isset($selected_posts[$a->ID]['order']) ? intval($selected_posts[$a->ID]['order']) : PHP_INT_MAX;
                            $order_b = isset($selected_posts[$b->ID]['order']) ? intval($selected_posts[$b->ID]['order']) : PHP_INT_MAX;
                            return $order_a - $order_b;
                        });

                        echo '<ul class="sortable-posts">';
                        foreach ($posts as $post) {
                            $post_id = $post->ID;
                            $checked = isset($selected_posts[$post_id]['selected']) ? 'checked' : '';
                            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '';
                            $order = isset($selected_posts[$post_id]['order']) ? intval($selected_posts[$post_id]['order']) : PHP_INT_MAX;
                            ?>
                            <li data-post-id="<?php echo esc_attr($post_id); ?>">
                                <span class="dashicons dashicons-menu drag-handle" style="cursor: move; margin-right: 10px;"></span>
                                <label>
                                    <input type="checkbox" name="blocks[<?php echo esc_attr($index); ?>][posts][<?php echo esc_attr($post_id); ?>][selected]" value="1" <?php echo esc_attr($checked); ?>> 
                                    <?php if ($thumbnail_url): ?>
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">
                                    <?php endif; ?>
                                    <?php echo esc_html($post->post_title); ?>
                                </label>
                                <input type="hidden" class="post-order" name="blocks[<?php echo esc_attr($index); ?>][posts][<?php echo esc_attr($post_id); ?>][order]" value="<?php echo esc_attr($order); ?>">
                            </li>
                            <?php
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>' . esc_html__('No posts found in this category and date range.', 'newsletter') . '</p>';
                    }
                } else {
                    echo '<p>' . esc_html__('Please select a category to display posts.', 'newsletter') . '</p>';
                }
                ?>
            </div>
        </div>

        <!-- Advertising Block -->
        <div class="advertising-block" <?php if ($block['type'] !== 'advertising') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('Advertising HTML:', 'newsletter'); ?></label>
            <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" style="width:100%;"><?php echo esc_textarea($block['html']); ?></textarea>
        </div>

        <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
    </div>
</div>
