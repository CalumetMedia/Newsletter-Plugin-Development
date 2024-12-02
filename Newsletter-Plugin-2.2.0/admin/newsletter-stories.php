<?php
// Prevent unauthorized access
if (!defined('ABSPATH')) exit;

// Ensure the user has the required capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve the current newsletter ID from the query parameters if not already set
if (!isset($newsletter_id)) {
    $newsletter_id = isset($_GET['newsletter_id']) ? intval($_GET['newsletter_id']) : 0;
}

// Retrieve newsletter settings
$newsletter_list = get_option('newsletter_list', []);
$newsletter_name = isset($newsletter_list[$newsletter_id]) ? $newsletter_list[$newsletter_id] : '';
$assigned_categories = get_option("newsletter_categories_$newsletter_id", []);
$template_id = get_option("newsletter_template_id_$newsletter_id", 'default');

// Retrieve all available templates
$templates = get_option('newsletter_templates', []);
$default_template = get_option('newsletter_default_template');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce for security
    if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'save_blocks_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } else {
        // Process the blocks data
        $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
        // Sanitize and save the blocks data
        $sanitized_blocks = [];
        foreach ($blocks as $block) {
            $sanitized_block = [
                'type'     => sanitize_text_field($block['type']), // 'content' or 'advertising'
                'category' => isset($block['category']) ? intval($block['category']) : null,
                'title'    => sanitize_text_field($block['title']),
                'posts'    => isset($block['posts']) ? array_map('intval', $block['posts']) : [],
                'html'     => isset($block['html']) ? wp_kses_post($block['html']) : '',
            ];
            $sanitized_blocks[] = $sanitized_block;
        }
        update_option("newsletter_blocks_$newsletter_id", $sanitized_blocks);

        echo '<div class="updated"><p>' . esc_html__('Blocks saved successfully.', 'newsletter') . '</p></div>';
    }
}

// Retrieve the blocks data
$blocks = get_option("newsletter_blocks_$newsletter_id", []);

// Retrieve all categories assigned in individual settings
$all_categories = get_categories(['include' => $assigned_categories, 'hide_empty' => false]);

// Enqueue the JavaScript file
wp_enqueue_script(
    'newsletter-admin-js',
    plugin_dir_url(__FILE__) . 'js/newsletter-admin.js',
    ['jquery', 'jquery-ui-datepicker', 'jquery-ui-accordion', 'jquery-ui-sortable'],
    '1.0',
    true // Load in footer
);

// Prepare categories data for JavaScript
$categories_js = [];
foreach ($all_categories as $category) {
    $categories_js[] = [
        'term_id' => $category->term_id,
        'name'    => $category->name,
    ];
}

// Localize script to pass PHP variables to JavaScript
wp_localize_script('newsletter-admin-js', 'newsletterData', [
    'ajaxUrl'                        => admin_url('admin-ajax.php'),
    'nonceLoadPosts'                 => wp_create_nonce('load_block_posts_nonce'),
    'nonceGeneratePreview'           => wp_create_nonce('generate_preview_nonce'),
    'nonceUpdateTemplateSelection'   => wp_create_nonce('update_template_selection_nonce'),
    'newsletterId'                   => $newsletter_id,
    'blockLabel'                     => __('Block', 'newsletter'),
    'blockTypeLabel'                 => __('Block Type:', 'newsletter'),
    'contentLabel'                   => __('Content', 'newsletter'),
    'advertisingLabel'               => __('Advertising', 'newsletter'),
    'blockTitleLabel'                => __('Block Title:', 'newsletter'),
    'selectCategoryLabel'            => __('Select Category:', 'newsletter'),
    'selectCategoryOption'           => __('-- Select Category --', 'newsletter'),
    'selectCategoryPrompt'           => __('Select a category to load posts.', 'newsletter'),
    'advertisingHtmlLabel'           => __('Advertising HTML:', 'newsletter'),
    'removeBlockLabel'               => __('Remove Block', 'newsletter'),
    'categories'                     => $categories_js,
    'newsletterName'                 => $newsletter_name,
]);

// Display header with newsletter name
echo '<div class="wrap">';
echo '<h1>' . sprintf(esc_html__('%s Stories', 'newsletter'), esc_html($newsletter_name)) . '</h1>';
?>
<!-- Link to the external CSS file -->
<link rel="stylesheet" type="text/css" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'css/newsletter-admin.css'); ?>">

<!-- Include jQuery UI CSS for accordions, datepicker, and sortable -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">

<div class="settings-container">
    <!-- Left Column (Blocks Management) -->
    <div class="settings-section">
        <h2 class="settings-tab"><?php esc_html_e('Blocks Management', 'newsletter'); ?></h2>
        <form method="post" id="blocks-form">
            <?php wp_nonce_field('save_blocks_action', 'newsletter_nonce'); ?>

            <!-- Date Range Selection -->
            <div class="settings-box">
                <h2><?php esc_html_e('Date Range Filter', 'newsletter'); ?></h2>
                <label for="start_date"><?php esc_html_e('Start Date:', 'newsletter'); ?></label>
                <input type="text" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? esc_attr($_POST['start_date']) : ''; ?>" />

                <label for="end_date"><?php esc_html_e('End Date:', 'newsletter'); ?></label>
                <input type="text" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? esc_attr($_POST['end_date']) : ''; ?>" />
            </div>

            <div id="blocks-container">
                <?php
                if (!empty($blocks)) {
                    foreach ($blocks as $index => $block) {
                        ?>
                        <div class="block-item" data-index="<?php echo esc_attr($index); ?>">
                            <h3 class="block-header"><?php esc_html_e('Block', 'newsletter'); ?> <?php echo intval($index) + 1; ?></h3>
                            <div class="block-content">
                                <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
                                <select name="blocks[<?php echo esc_attr($index); ?>][type]" class="block-type">
                                    <option value="content" <?php selected($block['type'], 'content'); ?>><?php esc_html_e('Content', 'newsletter'); ?></option>
                                    <option value="advertising" <?php selected($block['type'], 'advertising'); ?>><?php esc_html_e('Advertising', 'newsletter'); ?></option>
                                </select>

                                <div class="content-block" <?php if ($block['type'] !== 'content') echo 'style="display:none;"'; ?>>
                                    <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
                                    <input type="text" name="blocks[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($block['title']); ?>" />

                                    <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                                    <select name="blocks[<?php echo esc_attr($index); ?>][category]" class="block-category">
                                        <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                                        <?php
                                        foreach ($all_categories as $category) {
                                            $selected = ($block['category'] == $category->term_id) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                                        }
                                        ?>
                                    </select>

                                    <div class="block-posts">
                                        <?php
                                        // Display posts with sortable list
                                        $args = [
                                            'category' => $block['category'],
                                            'numberposts' => -1,
                                        ];
                                        // Apply date filter if set
                                        if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
                                            $args['date_query'] = [
                                                [
                                                    'after'     => sanitize_text_field($_POST['start_date']),
                                                    'before'    => sanitize_text_field($_POST['end_date']),
                                                    'inclusive' => true,
                                                ],
                                            ];
                                        }
                                        $posts = get_posts($args);
                                        if ($posts) {
                                            $selected_posts = $block['posts'];

                                            // Pre-select first 5 posts if no posts are selected
                                            if (empty($selected_posts)) {
                                                $selected_posts = array_slice(wp_list_pluck($posts, 'ID'), 0, 5);
                                            }

                                            echo '<ul class="sortable-posts">';
                                            foreach ($selected_posts as $post_id) {
                                                $post = get_post($post_id);
                                                if ($post) {
                                                    echo '<li data-post-id="' . esc_attr($post->ID) . '"><span class="dashicons dashicons-menu"></span> ' . esc_html($post->post_title) . ' <input type="hidden" name="blocks[' . esc_attr($index) . '][posts][]" value="' . esc_attr($post->ID) . '"></li>';
                                                }
                                            }
                                            echo '</ul>';

                                            echo '<label>' . esc_html__('Available Posts:', 'newsletter') . '</label>';
                                            echo '<ul class="available-posts">';
                                            foreach ($posts as $post) {
                                                if (!in_array($post->ID, $selected_posts)) {
                                                    echo '<li data-post-id="' . esc_attr($post->ID) . '"><span class="dashicons dashicons-plus"></span> ' . esc_html($post->post_title) . '</li>';
                                                }
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<p>' . esc_html__('No posts found in this category and date range.', 'newsletter') . '</p>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="advertising-block" <?php if ($block['type'] !== 'advertising') echo 'style="display:none;"'; ?>>
                                    <label><?php esc_html_e('Advertising HTML:', 'newsletter'); ?></label>
                                    <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" style="width:100%;"><?php echo esc_textarea($block['html']); ?></textarea>
                                </div>

                                <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
                            </div>
                            <hr>
                        </div>
                        <?php
                    }
                } else {
                    // Display a default block if none exist
                    ?>
                    <div class="block-item" data-index="0">
                        <h3 class="block-header"><?php esc_html_e('Block 1', 'newsletter'); ?></h3>
                        <div class="block-content">
                            <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
                            <select name="blocks[0][type]" class="block-type">
                                <option value="content"><?php esc_html_e('Content', 'newsletter'); ?></option>
                                <option value="advertising"><?php esc_html_e('Advertising', 'newsletter'); ?></option>
                            </select>

                            <div class="content-block">
                                <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
                                <input type="text" name="blocks[0][title]" value="" />

                                <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                                <select name="blocks[0][category]" class="block-category">
                                    <option value=""><?php esc_html_e('-- Select Category --', 'newsletter'); ?></option>
                                    <?php
                                    foreach ($all_categories as $category) {
                                        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                    }
                                    ?>
                                </select>

                                <div class="block-posts">
                                    <p><?php esc_html_e('Select a category to load posts.', 'newsletter'); ?></p>
                                </div>
                            </div>

                            <div class="advertising-block" style="display:none;">
                                <label><?php esc_html_e('Advertising HTML:', 'newsletter'); ?></label>
                                <textarea name="blocks[0][html]" rows="5" style="width:100%;"></textarea>
                            </div>

                            <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
                        </div>
                        <hr>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" id="add-block" class="button button-secondary"><?php esc_html_e('Add Block', 'newsletter'); ?></button>
            <br><br>
            <input type="submit" name="save_blocks" class="button button-primary" value="<?php esc_attr_e('Save Blocks', 'newsletter'); ?>">
        </form>

        <!-- Template Selection -->
        <div class="settings-box">
            <h2><?php esc_html_e('Template Settings', 'newsletter'); ?></h2>
            <form method="post" id="template-form">
                <?php wp_nonce_field('save_template_action', 'template_nonce'); ?>
                <label for="selected_template_id"><?php esc_html_e('Select Template:', 'newsletter'); ?></label>
                <select name="selected_template_id" id="selected_template_id">
                    <option value="default" <?php selected($template_id, 'default'); ?>><?php esc_html_e('Default Template', 'newsletter'); ?></option>
                    <?php
                    if (!empty($templates)) {
                        foreach ($templates as $index => $template) {
                            // Ensure 'name' key exists
                            $template_name = isset($template['name']) ? $template['name'] : __('Untitled Template', 'newsletter');
                            $selected_attr = ($template_id == $index) ? 'selected' : '';
                            echo '<option value="' . esc_attr($index) . '" ' . esc_attr($selected_attr) . '>' . esc_html($template_name) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>' . esc_html__('No templates available', 'newsletter') . '</option>';
                    }
                    ?>
                </select>
            </form>
        </div>
    </div>
    <!-- Right Column (Preview) -->
    <div class="settings-section">
        <h2 class="settings-tab"><?php esc_html_e('Newsletter Preview', 'newsletter'); ?></h2>
        <!-- Mobile Phone Preview -->
        <div id="story-preview">
            <div id="preview-content">
                <!-- Preview content will be loaded here via AJAX -->
            </div>
            <img id="phone-overlay" src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'images/iphone.png'); ?>" alt="Phone Overlay">
        </div>
    </div>
</div>

<!-- Include jQuery and jQuery UI for accordions, datepicker, and sortable -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
jQuery(document).ready(function($) {
    var blockIndex = $('#blocks-container .block-item').length || 0;

    // Initialize Datepicker
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown",
        onSelect: function() {
            // Reload posts in all blocks when date range changes
            $('#blocks-container .block-item').each(function() {
                var block = $(this);
                var blockType = block.find('.block-type').val();
                var categoryId = block.find('.block-category').val();
                var blockIndex = block.data('index');

                if (blockType === 'content' && categoryId) {
                    // Load posts for existing blocks
                    loadBlockPosts(block, categoryId, blockIndex);
                }
            });
        }
    });

    // Initialize Accordions
    $("#blocks-container").accordion({
        header: ".block-header",
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    // Initialize Sortable for selected posts
    $(document).on('mouseenter', '.sortable-posts', function() {
        $(this).sortable({
            handle: '.dashicons-menu',
            update: function(event, ui) {
                // Update the hidden input fields' order
                var inputs = '';
                $(this).find('li').each(function() {
                    var postId = $(this).data('post-id');
                    inputs += '<input type="hidden" name="' + $(this).closest('.block-item').find('.block-type').attr('name').replace('type', 'posts[]') + '" value="' + postId + '">';
                });
                $(this).html(inputs);
                updatePreview();
            }
        });
    });

    // Add Block
    $('#add-block').click(function() {
        var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
        blockHtml += '<h3 class="block-header">' + newsletterData.blockLabel + ' ' + (blockIndex + 1) + '</h3>';
        blockHtml += '<div class="block-content">';
        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="advertising">' + newsletterData.advertisingLabel + '</option>';
        blockHtml += '</select>';

        blockHtml += '<div class="content-block">';
        blockHtml += '<label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" value="" />';

        blockHtml += '<label>' + newsletterData.selectCategoryLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][category]" class="block-category">';
        blockHtml += '<option value="">' + newsletterData.selectCategoryOption + '</option>';
        $.each(newsletterData.categories, function(index, category) {
            blockHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });
        blockHtml += '</select>';

        blockHtml += '<div class="block-posts">';
        blockHtml += '<p>' + newsletterData.selectCategoryPrompt + '</p>';
        blockHtml += '</div>';
        blockHtml += '</div>';

        blockHtml += '<div class="advertising-block" style="display:none;">';
        blockHtml += '<label>' + newsletterData.advertisingHtmlLabel + '</label>';
        blockHtml += '<textarea name="blocks[' + blockIndex + '][html]" rows="5" style="width:100%;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<button type="button" class="button remove-block">' + newsletterData.removeBlockLabel + '</button>';
        blockHtml += '</div>';
        blockHtml += '<hr>';
        blockHtml += '</div>';

        $('#blocks-container').append(blockHtml);

        // Refresh Accordion
        $("#blocks-container").accordion("refresh");
        blockIndex++;
    });

    // Remove Block
    $(document).on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();

        // Refresh Accordion
        $("#blocks-container").accordion("refresh");

        // Update Preview
        updatePreview();
    });

    // Update Block Indices
    function updateBlockIndices() {
        $('#blocks-container .block-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.block-header').text(newsletterData.blockLabel + ' ' + (index + 1));
            $(this).find('input[type="text"]').attr('name', 'blocks[' + index + '][title]');
            $(this).find('.block-type').attr('name', 'blocks[' + index + '][type]');
            $(this).find('.block-category').attr('name', 'blocks[' + index + '][category]');
            $(this).find('.block-posts input[type="hidden"]').each(function() {
                $(this).attr('name', 'blocks[' + index + '][posts][]');
            });
            $(this).find('.advertising-block textarea').attr('name', 'blocks[' + index + '][html]');
        });
        blockIndex = $('#blocks-container .block-item').length;
    }

    // Handle Block Type Change
    $(document).on('change', '.block-type', function() {
        var block = $(this).closest('.block-item');
        var blockType = $(this).val();

        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.advertising-block').hide();
        } else if (blockType === 'advertising') {
            block.find('.content-block').hide();
            block.find('.advertising-block').show();
        }

        // Update Preview
        updatePreview();
    });

    // Load Posts when Category Changes
    $(document).on('change', '.block-category', function() {
        var block = $(this).closest('.block-item');
        var categoryId = $(this).val();
        var blockIndex = block.data('index');

        if (categoryId) {
            // Load posts for existing blocks
            loadBlockPosts(block, categoryId, blockIndex);
        } else {
            block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
        }
    });

    // Function to Load Block Posts
    function loadBlockPosts(block, categoryId, blockIndex) {
        // Fetch posts via AJAX
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_block_posts',
                category_id: categoryId,
                block_index: blockIndex,
                start_date: startDate,
                end_date: endDate,
                security: newsletterData.nonceLoadPosts
            },
            success: function(response) {
                if (response.success) {
                    block.find('.block-posts').html(response.data);

                    // Initialize Sortable for the new list
                    block.find('.sortable-posts').sortable({
                        handle: '.dashicons-menu',
                        update: function(event, ui) {
                            updatePreview();
                        }
                    });

                    // Update Preview
                    updatePreview();
                } else {
                    block.find('.block-posts').html('<p>' + response.data + '</p>');
                }
            }
        });
    }

    // Add Post from Available Posts to Selected Posts
    $(document).on('click', '.available-posts li', function() {
        var postId = $(this).data('post-id');
        var postTitle = $(this).text();
        var block = $(this).closest('.block-item');
        var blockIndex = block.data('index');

        var listItem = '<li data-post-id="' + postId + '"><span class="dashicons dashicons-menu"></span> ' + postTitle + ' <input type="hidden" name="blocks[' + blockIndex + '][posts][]" value="' + postId + '"></li>';
        block.find('.sortable-posts').append(listItem);

        // Remove from available posts
        $(this).remove();

        // Initialize Sortable
        block.find('.sortable-posts').sortable({
            handle: '.dashicons-menu',
            update: function(event, ui) {
                updatePreview();
            }
        });

        // Update Preview
        updatePreview();
    });

    // Update Preview on Form Changes
    $('#blocks-form').on('change', 'input, select, textarea', function() {
        updatePreview();
    });

    $('#selected_template_id').change(function() {
        // Update the template selection
        var selectedTemplateId = $(this).val();
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'update_template_selection',
                newsletter_id: newsletterData.newsletterId,
                template_id: selectedTemplateId,
                security: newsletterData.nonceUpdateTemplateSelection
            },
            success: function(response) {
                if (response.success) {
                    updatePreview();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Function to Update Preview
    function updatePreview() {
        // Gather form data
        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_id', value: newsletterData.newsletterId });
        formData.push({ name: 'template_id', value: $('#selected_template_id').val() });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#preview-content').html(response.data);
                } else {
                    $('#preview-content').html('<p>' + response.data + '</p>');
                }
            }
        });
    }

    // Initial Posts Load and Preview Update
    $('#blocks-container .block-item').each(function() {
        var block = $(this);
        var blockType = block.find('.block-type').val();
        var categoryId = block.find('.block-category').val();
        var blockIndex = block.data('index');

        if (blockType === 'content' && categoryId) {
            // Load posts for existing blocks
            loadBlockPosts(block, categoryId, blockIndex);
        }
    });

    // Update Preview on Page Load
    updatePreview();
});
</script>
<?php
// Enqueue the JavaScript file
wp_enqueue_script(
    'newsletter-admin-js',
    plugin_dir_url(__FILE__) . 'js/newsletter-admin.js',
    ['jquery', 'jquery-ui-datepicker', 'jquery-ui-accordion', 'jquery-ui-sortable'],
    '1.0',
    true // Load in footer
);

// Localize script to pass PHP variables to JavaScript
wp_localize_script('newsletter-admin-js', 'newsletter_ajax', [
    'ajax_url'                       => admin_url('admin-ajax.php'),
    'nonce_load_posts'               => wp_create_nonce('load_block_posts_nonce'),
    'nonce_generate_preview'         => wp_create_nonce('generate_preview_nonce'),
    'nonce_update_template_selection' => wp_create_nonce('update_template_selection_nonce'),
    'newsletter_id'                  => $newsletter_id,
]);

echo '</div>'; // Close wrap div

// Handle AJAX for loading posts with date filter applied
add_action('wp_ajax_load_block_posts', 'load_block_posts');
function load_block_posts() {
    check_ajax_referer('load_block_posts_nonce', 'security');

    $category_id = intval($_POST['category_id']);
    $block_index = intval($_POST['block_index']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    $args = [
        'category' => $category_id,
        'numberposts' => -1,
    ];

    if ($start_date && $end_date) {
        $args['date_query'] = [
            [
                'after'     => $start_date,
                'before'    => $end_date,
                'inclusive' => true,
            ],
        ];
    }

    $posts = get_posts($args);

    if ($posts) {
        $response = '';
        $response .= '<ul class="sortable-posts">';
        foreach ($posts as $post) {
            // Initially, no posts are selected in the sortable list
        }
        $response .= '</ul>';

        $response .= '<label>' . esc_html__('Available Posts:', 'newsletter') . '</label>';
        $response .= '<ul class="available-posts">';
        foreach ($posts as $post) {
            $response .= '<li data-post-id="' . esc_attr($post->ID) . '"><span class="dashicons dashicons-plus"></span> ' . esc_html($post->post_title) . '</li>';
        }
        $response .= '</ul>';

        wp_send_json_success($response);
    } else {
        wp_send_json_error(__('No posts found in this category and date range.', 'newsletter'));
    }
}

// Handle AJAX for generating preview
add_action('wp_ajax_generate_preview', 'generate_preview');
function generate_preview() {
    check_ajax_referer('generate_preview_nonce', 'security');

    $newsletter_id = intval($_POST['newsletter_id']);
    $template_id = sanitize_text_field($_POST['template_id']);
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    // Build the preview content based on blocks and template
    // This is a simplified example; you need to implement the actual rendering logic
    $preview_content = '<div class="newsletter-preview">';
    foreach ($blocks as $block) {
        if ($block['type'] === 'content') {
            $preview_content .= '<h2>' . esc_html($block['title']) . '</h2>';
            if (!empty($block['posts'])) {
                foreach ($block['posts'] as $post_id) {
                    $post = get_post($post_id);
                    if ($post) {
                        $preview_content .= '<p>' . esc_html($post->post_title) . '</p>';
                    }
                }
            }
        } elseif ($block['type'] === 'advertising') {
            $preview_content .= '<div class="advertising-block">';
            $preview_content .= wp_kses_post($block['html']);
            $preview_content .= '</div>';
        }
    }
    $preview_content .= '</div>';

    wp_send_json_success($preview_content);
}

// Handle AJAX for updating template selection
add_action('wp_ajax_update_template_selection', 'update_template_selection');
function update_template_selection() {
    check_ajax_referer('update_template_selection_nonce', 'security');

    $newsletter_id = intval($_POST['newsletter_id']);
    $template_id = sanitize_text_field($_POST['template_id']);

    // Update the newsletter's template ID
    update_option("newsletter_template_id_$newsletter_id", $template_id);

    wp_send_json_success();
}
?>
