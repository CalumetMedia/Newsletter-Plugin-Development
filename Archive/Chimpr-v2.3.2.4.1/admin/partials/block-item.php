<?php
// Ensure variables are set
$index = isset($index) ? $index : 0;
$block = isset($block) ? $block : [];

// Default values
$block_title = isset($block['title']) ? $block['title'] : '';
$block_type = isset($block['type']) ? $block['type'] : 'content';
$block_category = isset($block['category']) ? $block['category'] : '';
$block_template = isset($block['template_id']) ? $block['template_id'] : 'default';
$block_date_range = isset($block['date_range']) ? $block['date_range'] : '7';
$block_story_count = isset($block['story_count']) ? $block['story_count'] : 'disable';
$block_manual_override = isset($block['manual_override']) ? $block['manual_override'] : false;
$block_show_title = isset($block['show_title']) ? $block['show_title'] : false;
?>

<div class="block-item" data-index="<?php echo esc_attr($index); ?>" data-block-index="<?php echo esc_attr($index); ?>">
    <h3 class="block-header">
        <div class="block-header-content">
            <span class="dashicons dashicons-sort block-drag-handle"></span>
            <span class="block-title"><?php echo esc_html($block_title ?: __('New Block', 'newsletter')); ?></span>
        </div>
    </h3>
    
    <div class="block-content">
        <!-- Title Row -->
        <div class="block-row">
            <div class="block-field">
                <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
                <input type="text" name="blocks[<?php echo esc_attr($index); ?>][title]" 
                       class="block-title-input" value="<?php echo esc_attr($block_title); ?>">
            </div>
            <div class="block-checkbox">
                <input type="checkbox" name="blocks[<?php echo esc_attr($index); ?>][show_title]" 
                       class="show-title-toggle toggle-switch" value="1" id="show-title-<?php echo esc_attr($index); ?>"
                       <?php checked($block_show_title, true); ?>>
                <label for="show-title-<?php echo esc_attr($index); ?>">
                    <?php esc_html_e('Show Title in Preview', 'newsletter'); ?>
                </label>
            </div>
        </div>
        
        <!-- Block Type and Template Row -->
        <div class="block-row">
            <div class="block-field">
                <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][type]" class="block-type">
                    <option value="content" <?php selected($block_type, 'content'); ?>><?php esc_html_e('Content', 'newsletter'); ?></option>
                    <option value="html" <?php selected($block_type, 'html'); ?>><?php esc_html_e('HTML', 'newsletter'); ?></option>
                    <option value="wysiwyg" <?php selected($block_type, 'wysiwyg'); ?>><?php esc_html_e('WYSIWYG Editor', 'newsletter'); ?></option>
                    <option value="pdf_link" <?php selected($block_type, 'pdf_link'); ?>><?php esc_html_e('PDF Link', 'newsletter'); ?></option>
                </select>
            </div>

            <div class="block-field category-select">
                <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][category]" class="block-category"
                        <?php echo ($block_type !== 'content') ? 'disabled' : ''; ?>>
                    <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                    <?php
                    foreach ($all_categories as $category) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($category->term_id),
                            selected($block_category, $category->term_id, false),
                            esc_html($category->name)
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="block-field template-select">
                <label><?php esc_html_e('Template:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][template_id]" class="block-template"
                        <?php echo ($block_type !== 'content') ? 'disabled' : ''; ?>>
                    <?php
                    foreach ($available_templates as $template_id => $template) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($template_id),
                            selected($block_template, $template_id, false),
                            esc_html($template['name'])
                        );
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Date Range and Story Count Row -->
        <div class="block-row">
            <div class="block-field">
                <label><?php esc_html_e('Date Range:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][date_range]" class="block-date-range"
                        <?php echo ($block_type !== 'content') ? 'disabled' : ''; ?>>
                    <?php
                    $date_ranges = [
                        '1' => __('Previous 1 Day', 'newsletter'),
                        '2' => __('Previous 2 Days', 'newsletter'),
                        '3' => __('Previous 3 Days', 'newsletter'),
                        '5' => __('Previous 5 Days', 'newsletter'),
                        '7' => __('Previous 7 Days', 'newsletter'),
                        '14' => __('Previous 14 Days', 'newsletter'),
                        '30' => __('Previous 30 Days', 'newsletter'),
                        '60' => __('Previous 60 Days', 'newsletter'),
                        '90' => __('Previous 90 Days', 'newsletter'),
                        '0' => __('All', 'newsletter'),
                    ];

                    foreach ($date_ranges as $value => $label) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($value),
                            selected($block_date_range, $value, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="block-field">
                <label><?php esc_html_e('Number of Stories:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][story_count]" class="block-story-count"
                        data-block-index="<?php echo esc_attr($index); ?>"
                        <?php echo ($block_type !== 'content') ? 'disabled' : ''; ?>>
                    <option value="disable" <?php selected($block_story_count, 'disable'); ?>><?php esc_html_e('All', 'newsletter'); ?></option>
                    <?php
                    for ($i = 1; $i <= 10; $i++) {
                        printf(
                            '<option value="%d" %s>%d</option>',
                            $i,
                            selected($block_story_count, $i, false),
                            $i
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="block-checkbox">
                <input type="checkbox" name="blocks[<?php echo esc_attr($index); ?>][manual_override]"
                       class="manual-override-toggle toggle-switch" value="1" id="manual-override-<?php echo esc_attr($index); ?>"
                       <?php checked($block_manual_override, true); ?>
                       <?php echo ($block_type !== 'content') ? 'disabled' : ''; ?>>
                <label for="manual-override-<?php echo esc_attr($index); ?>">
                    <?php esc_html_e('Manual Override Stories', 'newsletter'); ?>
                </label>
            </div>
        </div>

        <!-- Content Block Section -->
        <div class="content-block<?php echo ($block_type !== 'content') ? ' hidden' : ''; ?>">
            <div class="block-posts">
                <h4><?php esc_html_e('Posts:', 'newsletter'); ?></h4>
                <?php if (empty($block_category)): ?>
                    <p class="no-posts-message"><?php esc_html_e('Please select a category to display posts.', 'newsletter'); ?></p>
                <?php endif; ?>
                <?php if (!empty($block['posts'])): ?>
                    <ul class="sortable-posts">
                        <?php foreach ($block['posts'] as $post_id => $post): ?>
                            <li class="story-item" data-post-id="<?php echo esc_attr($post_id); ?>">
                                <span class="dashicons dashicons-sort story-drag-handle"></span>
                                <label>
                                    <input type="checkbox" name="blocks[<?php echo esc_attr($index); ?>][posts][<?php echo esc_attr($post_id); ?>][checked]" 
                                           value="1" <?php checked(isset($post['checked']), true); ?>>
                                    <?php if (!empty($post['thumbnail'])): ?>
                                        <img class="post-thumbnail" src="<?php echo esc_url($post['thumbnail']); ?>" 
                                             alt="<?php echo esc_attr($post['title'] ?? ''); ?>">
                                    <?php endif; ?>
                                    <span class="post-title"><?php echo esc_html($post['title'] ?? ''); ?></span>
                                </label>
                                <input type="hidden" class="post-order" 
                                       name="blocks[<?php echo esc_attr($index); ?>][posts][<?php echo esc_attr($post_id); ?>][order]" 
                                       value="<?php echo esc_attr($post['order'] ?? ''); ?>">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- HTML Block Section -->
        <div class="html-block<?php echo ($block_type !== 'html') ? ' hidden' : ''; ?>">
            <div class="block-field">
                <label><?php esc_html_e('Custom HTML:', 'newsletter'); ?></label>
                <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" 
                          class="block-html"><?php echo esc_textarea($block['html'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- WYSIWYG Block -->
        <div class="wysiwyg-block<?php echo ($block_type !== 'wysiwyg') ? ' hidden' : ''; ?>">
            <div class="block-field">
                <label><?php esc_html_e('WYSIWYG Content:', 'newsletter'); ?></label>
                <?php
                $editor_content = isset($block['wysiwyg']) ? $block['wysiwyg'] : '';
                $editor_id = 'wysiwyg-editor-' . $index;
                wp_editor($editor_content, $editor_id, [
                    'textarea_name' => "blocks[$index][wysiwyg]",
                    'textarea_rows' => 15,
                    'media_buttons' => true,
                    'teeny' => true,
                    'quicktags' => true,
                ]);
                ?>
            </div>
        </div>

        <button type="button" 
                class="button button-large action-button remove-button block-remove-btn" 
                data-block-index="<?php echo esc_attr($index); ?>">
            <span class="dashicons dashicons-trash button-icon"></span>
            <strong><?php esc_html_e('REMOVE BLOCK', 'newsletter'); ?></strong>
        </button>
    </div>
</div>
