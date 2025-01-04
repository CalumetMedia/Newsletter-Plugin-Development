<?php
if (!defined('ABSPATH')) exit;

if (!isset($block) || !isset($index)) return;

// Initialize required variables
$block_index = $index;
$manual_override = isset($block['manual_override']) && $block['manual_override'];
$story_count = isset($block['story_count']) ? $block['story_count'] : 'disable';
$saved_selections = isset($block['posts']) ? $block['posts'] : [];

$available_templates = get_option('newsletter_templates', []);
$block_templates = array_filter($available_templates, function($template) {
    return isset($template['type']) && $template['type'] === 'block';
});

if (!isset($block_templates['default'])) {
    $default_template_content = get_option('newsletter_default_template', '');
    $block_templates = array_merge(
        ['default' => [
            'name' => __('Default Template', 'newsletter'),
            'html' => $default_template_content,
            'type' => 'block'
        ]],
        $block_templates
    );
}
?>

<div class="block-item" data-index="<?php echo esc_attr($index); ?>">
    <h3 class="block-header">
        <div style="display: flex; align-items: center; width: 100%;">
            <span class="dashicons dashicons-sort block-drag-handle"></span>
            <span class="block-title" style="flex: 1; font-size: 14px; margin: 0 10px;"><?php echo esc_html($block['title'] ?: __('Block', 'newsletter')); ?></span>
            <!-- Removed the accordion toggle arrow icon here -->
        </div>
    </h3>
    <div class="block-content">
        <!-- Title Row -->
<div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">
    <div style="width: 25%;">
        <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
        <input type="text" 
               name="blocks[<?php echo esc_attr($index); ?>][title]" 
               class="block-title-input" 
               value="<?php echo esc_attr(wp_unslash($block['title'])); ?>" 
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

            <div style="width: 200px;" class="category-select" <?php if ($block['type'] === 'html' || $block['type'] === 'wysiwyg') echo 'style="display:none;"'; ?>>
                <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][category]" 
                        class="block-category" 
                        style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                    <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                    <?php
                    if (!empty($all_categories)) {
                        foreach ($all_categories as $category) {
                            $selected = (isset($block['category']) && $block['category'] == $category->term_id) ? 'selected' : '';
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
    
    <div style="width: 200px;" class="template-select" <?php if ($block['type'] === 'html' || $block['type'] === 'wysiwyg') echo 'style="display:none;"'; ?>>
        <label><?php esc_html_e('Template:', 'newsletter'); ?></label>
        <select name="blocks[<?php echo esc_attr($index); ?>][template_id]" 
                class="block-template" 
                style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
            <?php
            foreach ($block_templates as $tid => $template) {
                $selected = (isset($block['template_id']) && $block['template_id'] == $tid) ? 'selected' : '';
                echo '<option value="' . esc_attr($tid) . '" ' . $selected . '>' . esc_html($template['name']) . '</option>';
            }
            ?>
        </select>
    </div>
        </div>

        <!-- Date Range and Story Count Row -->
        <div class="date-range-row" style="display: flex; gap: 15px; margin-bottom: 10px;" <?php if ($block['type'] === 'html' || $block['type'] === 'wysiwyg') echo 'style="display:none;"'; ?>>
            <div>
                <label><?php esc_html_e('Date Range:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][date_range]" 
                        class="block-date-range" 
                        style="width: 200px; height: 36px; line-height: 1.4; padding: 0 6px;">
                    <option value="1" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 1); ?>><?php esc_html_e('Previous 1 Day', 'newsletter'); ?></option>
                    <option value="2" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 2); ?>><?php esc_html_e('Previous 2 Days', 'newsletter'); ?></option>
                    <option value="3" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 3); ?>><?php esc_html_e('Previous 3 Days', 'newsletter'); ?></option>
                    <option value="5" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 5); ?>><?php esc_html_e('Previous 5 Days', 'newsletter'); ?></option>
                    <option value="7" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 7); ?>><?php esc_html_e('Previous 7 Days', 'newsletter'); ?></option>
                    <option value="14" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 14); ?>><?php esc_html_e('Previous 14 Days', 'newsletter'); ?></option>
                    <option value="30" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 30); ?>><?php esc_html_e('Previous 30 Days', 'newsletter'); ?></option>
                    <option value="60" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 60); ?>><?php esc_html_e('Previous 60 Days', 'newsletter'); ?></option>
                    <option value="90" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 90); ?>><?php esc_html_e('Previous 90 Days', 'newsletter'); ?></option>
                    <option value="0" <?php selected(isset($block['date_range']) ? $block['date_range'] : 7, 0); ?>><?php esc_html_e('All', 'newsletter'); ?></option>
                </select>
            </div>

            <div>
                <?php $current_story_count = isset($block['story_count']) ? $block['story_count'] : 'disable'; ?>
                <label><?php esc_html_e('Number of Stories:', 'newsletter'); ?></label>
                <select name="blocks[<?php echo esc_attr($index); ?>][story_count]" 
                        class="block-story-count" 
                        style="width: 200px; height: 36px; line-height: 1.4; padding: 0 6px;">
                    <option value="disable" <?php selected($current_story_count, 'disable'); ?>><?php esc_html_e('All', 'newsletter'); ?></option>
                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($current_story_count, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="display: flex; align-items: flex-end; padding-bottom: 8px;">
                <label>
                    <input type="checkbox" 
                           name="blocks[<?php echo esc_attr($index); ?>][manual_override]" 
                           class="manual-override-toggle" 
                           value="1" 
                           <?php checked(isset($block['manual_override']) && $block['manual_override']); ?>>
                    <?php esc_html_e('Manual Override Stories', 'newsletter'); ?>
                </label>
            </div>
        </div>

        <!-- WYSIWYG Block -->
        <div class="wysiwyg-block" <?php if ($block['type'] !== 'wysiwyg') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('WYSIWYG Content:', 'newsletter'); ?></label>
            <?php
            $editor_id = 'wysiwyg-editor-' . $index;
            $wysiwyg_content = isset($block['wysiwyg']) ? wp_kses_post($block['wysiwyg']) : '';
            
            wp_editor(
                $wysiwyg_content,
                $editor_id,
                array(
                    'textarea_name' => 'blocks[' . esc_attr($index) . '][wysiwyg]',
                    'media_buttons' => true,
                    'textarea_rows' => 15,
                    'editor_class' => 'wysiwyg-editor-content',
                    'tinymce' => array(
                        'init_instance_callback' => "function(editor) {
                            editor.on('change', function(e) {
                                editor.save();
                                jQuery(editor.getElement()).trigger('change');
                            });
                        }",
                        'setup' => "function(editor) {
                            editor.on('change', function() {
                                editor.save();
                            });
                        }"
                    ),
                    'quicktags' => true
                )
            );
            ?>
        </div>

        <!-- Content Block Section -->
        <div class="content-block" <?php if ($block['type'] !== 'content') echo 'style="display:none;"'; ?>>
            <!-- Posts Selection Section -->
            <div class="block-posts">
                <h4><?php esc_html_e('Posts:', 'newsletter'); ?></h4>
                <?php
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
                        echo '<ul class="sortable-posts"' . (!$manual_override ? ' style="pointer-events: none; opacity: 0.7;"' : '') . '>';
                        
                        foreach ($posts as $post_index => $post) {
                            $post_id = $post->ID;
                            $checked = '';
                            
                            if ($manual_override) {
                                // In manual mode, use saved selections
                                if (isset($saved_selections[$post_id])) {
                                    $checked = (!empty($saved_selections[$post_id]['selected']) && $saved_selections[$post_id]['selected'] == 1) ? 'checked' : '';
                                }
                            } else {
                                // In automatic mode, select based on story count
                                if ($story_count !== 'disable') {
                                    $checked = ($post_index < intval($story_count)) ? 'checked' : '';
                                } else {
                                    $checked = 'checked';
                                }
                            }

                            // Get the order value from saved selections or use index
                            $order = isset($saved_selections[$post_id]['order']) ? intval($saved_selections[$post_id]['order']) : $post_index;
                            
                            ?>
                            <li data-post-id="<?php echo esc_attr($post_id); ?>" class="sortable-post-item">
                                <span class="dashicons dashicons-sort story-drag-handle" style="cursor: <?php echo !$manual_override ? 'default' : 'move'; ?>; margin-right: 10px;"></span>
                                <label>
                                    <input type="checkbox" 
                                           name="blocks[<?php echo esc_attr($block_index); ?>][posts][<?php echo esc_attr($post_id); ?>][selected]" 
                                           value="1" 
                                           <?php echo $checked; ?> 
                                           <?php echo !$manual_override ? 'disabled' : ''; ?>> 
                                    <?php 
                                    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
                                    if ($thumbnail_url) {
                                        echo '<img src="' . esc_url($thumbnail_url) . '" 
                                                  alt="' . esc_attr($post->post_title) . '" 
                                                  style="width:50px; height:auto; margin-right:10px; vertical-align: middle;">';
                                    }
                                    echo esc_html($post->post_title);
                                    if ($post->post_status === 'future') {
                                        echo '<span class="newsletter-status schedule" style="margin-left:10px;">SCHEDULED</span>';
                                    }
                                    ?>
                                </label>
                                <input type="hidden" 
                                       class="post-order" 
                                       name="blocks[<?php echo esc_attr($block_index); ?>][posts][<?php echo esc_attr($post_id); ?>][order]" 
                                       value="<?php echo esc_attr($order); ?>">
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

        <!-- Manual Override Script -->
        <script>
        jQuery(document).ready(function($) {
            var blockIndex = <?php echo esc_js($index); ?>;
            var $manualOverride = $('input[name="blocks[' + blockIndex + '][manual_override]"]');
            var $postsList = $manualOverride.closest('.block-item').find('.sortable-posts');
            var $checkboxes = $postsList.find('input[type="checkbox"]');
            
            function updateInteractiveState(isManual) {
                $postsList.css({
                    'pointer-events': isManual ? 'auto' : 'none',
                    'opacity': isManual ? '1' : '0.7'
                });
                $checkboxes.prop('disabled', !isManual);
                $postsList.find('.story-drag-handle').css('cursor', isManual ? 'move' : 'default');
            }

            updateInteractiveState($manualOverride.prop('checked'));

            $manualOverride.on('change', function() {
                var isManual = $(this).prop('checked');
                updateInteractiveState(isManual);
                
                if (!isManual) {
                    const dateRange = $(this).closest('.block-item').find('.block-date-range').val();
                    const categoryId = $(this).closest('.block-item').find('.block-category').val();
                    const storyCount = $(this).closest('.block-item').find('.block-story-count').val();
                    
                    if (categoryId) {
                        window.loadBlockPosts($(this).closest('.block-item'), categoryId, blockIndex, dateRange, storyCount);
                    }
                }
            });
        });
        </script>

        <!-- HTML Block Section -->
        <div class="html-block" <?php if ($block['type'] !== 'html') echo 'style="display:none;"'; ?>>
            <label><?php esc_html_e('Custom HTML:', 'newsletter'); ?></label>
            <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" style="width:100%;"><?php echo isset($block['html']) ? esc_textarea($block['html']) : ''; ?></textarea>
        </div>

        <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
    </div>
</div>
