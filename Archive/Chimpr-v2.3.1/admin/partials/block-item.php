<?php
if (!defined('ABSPATH')) exit;

if (!isset($block) || !isset($index)) return;

$available_templates = get_option('newsletter_templates', []);

if (!isset($available_templates['default'])) {
    $default_template_content = get_option('newsletter_default_template', '');
    $available_templates = array_merge(
        ['default' => [
            'name' => __('Default Template', 'newsletter'),
            'html' => $default_template_content,
        ]],
        $available_templates
    );
}
?>

<div class="block-item" data-index="<?php echo esc_attr($index); ?>">
    <h3 class="block-header">
        <span class="dashicons dashicons-sort block-drag-handle"></span>
        <span class="block-title" style="font-size: 14px;"><?php echo esc_html($block['title'] ?: __('Block', 'newsletter')); ?></span>
        <span class="dashicons dashicons-arrow-down-alt2 block-accordion-toggle"></span>
    </h3>
    
    <div class="block-content">
        <!-- Title Row -->
        <div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">
            <div style="width: 25%;">
                <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
                <input type="text" 
                       name="blocks[<?php echo esc_attr($index); ?>][title]" 
                       class="block-title-input" 
                       value="<?php echo esc_attr($block['title']); ?>" 
                       style="width: 100%; height: 36px;" />
            </div>
            <div style="margin-left: 15px;">
                <label>
                    <input type="checkbox" 
                           name="blocks[<?php echo esc_attr($index); ?>][show_title]" 
                           class="show-title-toggle" 
                           value="1" 
                           <?php checked(!isset($block['show_title']) || $block['show_title']); ?>>
                    <?php esc_html_e('Show Title in Preview', 'newsletter'); ?>
                </label>
            </div>
        </div>
        
        <!-- Block Type and Template Row -->
        <div style="display: flex; gap: 15px; margin-bottom: 10px;">
            <div style="width: 200px;">
                <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][type]" 
                        class="block-type" 
                        style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                    <option value="content" <?php selected($block['type'], 'content'); ?>><?php esc_html_e('Content', 'newsletter'); ?></option>
                    <option value="html" <?php selected($block['type'], 'html'); ?>><?php esc_html_e('HTML', 'newsletter'); ?></option>
                    <option value="wysiwyg" <?php selected($block['type'], 'wysiwyg'); ?>><?php esc_html_e('WYSIWYG Editor', 'newsletter'); ?></option>
                </select>
            </div>
            
            <div style="width: 200px;" class="template-select" <?php if ($block['type'] === 'html' || $block['type'] === 'wysiwyg') echo 'style="display:none;"'; ?>>
                <label><?php esc_html_e('Template:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][template_id]" 
                        class="block-template" 
                        style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                    <?php
                    foreach ($available_templates as $tid => $template) {
                        $selected = (isset($block['template_id']) && $block['template_id'] == $tid) ? 'selected' : '';
                        echo '<option value="' . esc_attr($tid) . '" ' . $selected . '>' . esc_html($template['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- WYSIWYG Block -->
        <div class="wysiwyg-block" <?php if ($block['type'] !== 'wysiwyg') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('WYSIWYG Content:', 'newsletter'); ?></label>
            <?php
            $editor_id = 'wysiwyg-editor-' . $index;
            $wysiwyg_content = isset($block['wysiwyg']) ? $block['wysiwyg'] : '';
            ?>
            <textarea id="<?php echo esc_attr($editor_id); ?>"
                      class="wysiwyg-editor"
                      name="blocks[<?php echo esc_attr($index); ?>][wysiwyg]"
                      rows="15"
                      style="width:100%; height:600px;"><?php echo esc_textarea($wysiwyg_content); ?></textarea>
        </div>

        <!-- Content Block Section -->
        <div class="content-block" <?php if ($block['type'] !== 'content') echo 'style="display:none;"'; ?>>
            <!-- Category Row Only -->
            <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                <div style="width: 200px;">
                    <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                    <select name="blocks[<?php echo esc_attr($index); ?>][category]" 
                            class="block-category" 
                            style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                        <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                        <?php
                        if (!empty($all_categories)) {
                            foreach ($all_categories as $category) {
                                $selected = (isset($block['category']) && $block['category'] == $category->term_id) ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Posts Selection Section -->
            <div class="block-posts">
                <h4><?php esc_html_e('Posts:', 'newsletter'); ?></h4>
                <?php
                // Always fetch 20 posts
                if (!empty($block['category'])) {
                    $posts_args = [
                        'post_type'   => 'post',
                        'category'    => $block['category'],
                        'numberposts' => 20,
                        'orderby'     => 'date',
                        'order'       => 'DESC',
                        'post_status' => 'publish'
                    ];

                    $posts = get_posts($posts_args);

                    if ($posts) {
                        $selected_posts = isset($block['posts']) ? $block['posts'] : [];
                        // If there is any ordering, retain it, else just show in default order.
                        usort($posts, function ($a, $b) use ($selected_posts) {
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
                                <span class="dashicons dashicons-sort story-drag-handle" style="cursor: move; margin-right: 10px;"></span>
                                <label>
                                    <input type="checkbox" name="blocks[<?php echo esc_attr($index); ?>][posts][<?php echo esc_attr($post_id); ?>][selected]" value="1" <?php echo $checked; ?>> 
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
                        echo '<p>' . esc_html__('No posts found in this category.', 'newsletter') . '</p>';
                    }
                } else {
                    echo '<p>' . esc_html__('Please select a category to display posts.', 'newsletter') . '</p>';
                }
                ?>
            </div>
        </div>

        <!-- HTML Block Section -->
        <div class="html-block" <?php if ($block['type'] !== 'html') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('Custom HTML:', 'newsletter'); ?></label>
            <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" style="width:100%;"><?php echo isset($block['html']) ? esc_textarea($block['html']) : ''; ?></textarea>
        </div>

        <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
    </div>
</div>