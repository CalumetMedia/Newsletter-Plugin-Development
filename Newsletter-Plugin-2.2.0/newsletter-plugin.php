<?php
/**
 * Plugin Name:       Newsletter Automator v2.2
 * Description:       Current version of the plugin, focused on building blocks of content for newsletter creation as well as visual improvements for settings pages.
 * Version:           2.2
 * Author:            Jon Stewart
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Add the main menu and submenus
 */
add_action('admin_menu', 'newsletter_setup_menu');
function newsletter_setup_menu() {
    // Main menu page with a fancy envelope icon
    add_menu_page(
        'Newsletter',                   // Page title
        'Newsletter v2.2',              // Menu title
        'manage_options',               // Capability
        'newsletter-settings',          // Menu slug
        'newsletter_all_settings_page', // Callback function
        'dashicons-email-alt2',         // Icon (Dashicons envelope icon)
        22                              // Position
    );

    // Submenu for Settings
    add_submenu_page(
        'newsletter-settings',
        'Settings',
        'Settings',
        'manage_options',
        'newsletter-settings',
        'newsletter_all_settings_page'
    );

    // Dynamically add submenu items for each newsletter for the "stories" page
    $newsletters = get_option('newsletter_list', []);
    foreach ($newsletters as $newsletter_id => $newsletter_name) {
        add_submenu_page(
            'newsletter-settings',
            esc_html($newsletter_name) . ' Stories',    // Page title
            esc_html($newsletter_name),                 // Menu title
            'manage_options',
            'newsletter-stories-' . $newsletter_id,     // Unique slug for each newsletter
            function() use ($newsletter_id) {           // Callback function to load the stories page
                newsletter_stories_page($newsletter_id);
            }
        );
    }
}

/**
 * Callback functions for each page
 */
function newsletter_all_settings_page() {
    include plugin_dir_path(__FILE__) . 'admin/newsletter-settings-tabs.php';
}

function newsletter_stories_page($newsletter_id) {
    include plugin_dir_path(__FILE__) . 'admin/newsletter-stories.php';
}

/**
 * Register settings for the subpages
 */
add_action('admin_init', 'newsletter_register_settings');
function newsletter_register_settings() {
    register_setting('newsletter_add_newsletter_group', 'newsletter_list');
}

/**
 * Enqueue scripts and styles for admin pages
 */
add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts');
function newsletter_admin_enqueue_scripts($hook) {
    // Only enqueue scripts and styles on newsletter-related admin pages
    if (strpos($hook, 'newsletter') === false) return;

    // Enqueue jQuery and jQuery UI scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable'); // Enqueue Sortable
    wp_enqueue_script('jquery-ui-accordion'); // Enqueue Accordion
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css'); // Smoothness theme

    // Enqueue admin CSS
    wp_enqueue_style(
        'newsletter-admin-css',
        plugin_dir_url(__FILE__) . 'admin/css/newsletter-admin.css',
        [],
        '2.1'
    );

    // Enqueue phone screen CSS if necessary
    wp_enqueue_style(
        'newsletter-phone-css',
        plugin_dir_url(__FILE__) . 'admin/css/newsletter-phone.css',
        [],
        '1.0'
    );

    // Enqueue the separated JavaScript file
    wp_enqueue_script(
        'newsletter-admin-js',
        plugin_dir_url(__FILE__) . 'admin/js/newsletter-admin.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-accordion'],
        '1.0',
        true // Load in footer
    );

    // Localize script to pass PHP variables to JavaScript
    wp_localize_script('newsletter-admin-js', 'newsletter_ajax', [
        'ajax_url'                       => admin_url('admin-ajax.php'),
        'nonce_load_posts'               => wp_create_nonce('load_block_posts_nonce'),
        'nonce_generate_preview'         => wp_create_nonce('generate_preview_nonce'),
        'nonce_update_template_selection' => wp_create_nonce('update_template_selection_nonce'),
        'nonce_load_campaign'            => wp_create_nonce('load_campaign_nonce'),
        'newsletter_id'                  => isset($_GET['newsletter_id']) ? sanitize_text_field($_GET['newsletter_id']) : '',
    ]);
}

/**
 * Register AJAX actions for updating stories dynamically
 */
add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');
add_action('wp_ajax_update_template_selection', 'newsletter_update_template_selection');
add_action('wp_ajax_load_block_posts', 'newsletter_load_block_posts');
add_action('wp_ajax_load_campaign', 'newsletter_load_campaign');

/**
 * AJAX Handler: Generate Preview
 */
if (!function_exists('newsletter_generate_preview')) {
    function newsletter_generate_preview() {
        check_ajax_referer('generate_preview_nonce', 'security');

        $newsletter_id = intval($_POST['newsletter_id']);
        $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
        $template_id = sanitize_text_field($_POST['template_id']);

        // Retrieve templates
        $templates = get_option('newsletter_templates', []);
        $default_template = get_option('newsletter_default_template');

        // Fetch the selected template
        if ($template_id === 'default') {
            $selected_template = $default_template;
        } else {
            $selected_template = isset($templates[$template_id]) ? $templates[$template_id] : $default_template;
        }

        ob_start();
        if (!empty($blocks)) {
            foreach ($blocks as $block) {
                if ($block['type'] === 'content') {
                    $category_id = intval($block['category']);
                    $block_title = sanitize_text_field($block['title']);
                    $selected_posts = isset($block['posts']) ? array_map('intval', $block['posts']) : [];
                    $category = get_category($category_id);
                    if ($category) {
                        echo '<h3>' . (!empty($block_title) ? $block_title : esc_html($category->name)) . '</h3>';
                        if (!empty($selected_posts)) {
                            $posts = get_posts([
                                'post__in' => $selected_posts,
                                'orderby' => 'post__in',
                                'posts_per_page' => -1,
                            ]);
                            foreach ($posts as $post) {
                                // Replace placeholders in the template
                                $post_content = isset($selected_template['html']) ? $selected_template['html'] : '<p>{title}</p>';
                                $post_content = str_replace('{title}', esc_html($post->post_title), $post_content);
                                $post_content = str_replace('{excerpt}', esc_html(get_the_excerpt($post)), $post_content);
                                $post_content = str_replace('{permalink}', esc_url(get_permalink($post)), $post_content);
                                echo $post_content;
                            }
                        } else {
                            echo '<p>' . esc_html__('No posts selected in this block.', 'newsletter') . '</p>';
                        }
                    }
                } elseif ($block['type'] === 'advertising') {
                    // Output the raw HTML for advertising block
                    echo wp_kses_post($block['html']);
                }
            }
            wp_send_json_success(ob_get_clean());
        } else {
            wp_send_json_error(__('No blocks added yet.', 'newsletter'));
        }
    }
}

/**
 * AJAX Handler: Update Template Selection
 */
if (!function_exists('newsletter_update_template_selection')) {
    function newsletter_update_template_selection() {
        check_ajax_referer('update_template_selection_nonce', 'security');

        $newsletter_id = intval($_POST['newsletter_id']);
        $template_id = sanitize_text_field($_POST['template_id']);

        // Update the newsletter's template ID
        update_option("newsletter_template_id_$newsletter_id", $template_id);

        wp_send_json_success();
    }
}

/**
 * AJAX Handler: Load Block Posts (Adjusted to accept and apply date filters)
 */
if (!function_exists('newsletter_load_block_posts')) {
    function newsletter_load_block_posts() {
        check_ajax_referer('load_block_posts_nonce', 'security');

        $category_id = intval($_POST['category_id']);
        $block_index = intval($_POST['block_index']);
        $selected_posts = isset($_POST['selected_posts']) ? array_map('intval', $_POST['selected_posts']) : [];

        // Retrieve date filters from AJAX request
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        if (empty($category_id)) {
            wp_send_json_error(__('Invalid category ID.', 'newsletter'));
        }

        $args = [
            'category' => $category_id,
            'numberposts' => -1,
        ];

        if (!empty($start_date) && !empty($end_date)) {
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
            ob_start();
            echo '<ul class="sortable-posts">';
            foreach ($posts as $post) {
                if (in_array($post->ID, $selected_posts)) {
                    // Already selected posts are listed above
                    continue;
                }
                echo '<li data-post-id="' . esc_attr($post->ID) . '"><span class="dashicons dashicons-plus"></span> ' . esc_html($post->post_title) . '</li>';
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

            $content = ob_get_clean();
            wp_send_json_success($content);
        } else {
            wp_send_json_error(__('No posts found in this category and date range.', 'newsletter'));
        }
    }
}

/**
 * AJAX Handler: Load Campaign
 */
if (!function_exists('newsletter_load_campaign')) {
    function newsletter_load_campaign() {
        check_ajax_referer('load_campaign_nonce', 'security');

        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        $campaigns = get_option('newsletter_campaigns', []);

        if (isset($campaigns[$campaign_name])) {
            $campaign_blocks = $campaigns[$campaign_name];

            ob_start();
            // Render the blocks as HTML to replace the current blocks
            foreach ($campaign_blocks as $index => $block) {
                ?>
                <div class="block-item" data-index="<?php echo esc_attr($index); ?>">
                    <h3 class="block-header"><?php esc_html_e('Block', 'newsletter'); ?> <?php echo intval($index) + 1; ?></h3>
                    <div class="block-content">
                        <label><?php esc_html_e('Block Type:', 'newsletter'); ?></label>
                        <select name="blocks[<?php echo esc_attr($index); ?>][type]" class="block-type">
                            <option value="content" <?php selected($block['type'], 'content'); ?>><?php esc_html_e('Content', 'newsletter'); ?></option>
                            <option value="advertising" <?php selected($block['type'], 'advertising'); ?>><?php esc_html_e('Advertising', 'newsletter'); ?></option>
                        </select>

                        <?php if ($block['type'] === 'content'): ?>
                        <div class="content-block">
                            <label><?php esc_html_e('Block Title:', 'newsletter'); ?></label>
                            <input type="text" name="blocks[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($block['title']); ?>" />

                            <label><?php esc_html_e('Select Category:', 'newsletter'); ?></label>
                            <select name="blocks[<?php echo esc_attr($index); ?>][category]" class="block-category">
                                <?php
                                $all_categories = get_categories(['hide_empty' => false]);
                                foreach ($all_categories as $category) {
                                    $selected = ($block['category'] == $category->term_id) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>

                            <div class="block-posts">
                                <?php
                                // Display posts with checkboxes
                                $posts_args = [
                                    'category' => $block['category'],
                                    'numberposts' => -1,
                                ];
                                // Apply date filters if set in the campaign (optional)
                                if (isset($block['start_date']) && isset($block['end_date'])) {
                                    $posts_args['date_query'] = [
                                        [
                                            'after'     => $block['start_date'],
                                            'before'    => $block['end_date'],
                                            'inclusive' => true,
                                        ],
                                    ];
                                }
                                $posts = get_posts($posts_args);
                                if ($posts) {
                                    $selected_posts = $block['posts'];

                                    // Pre-select first 5 posts if no posts are selected
                                    if (empty($selected_posts)) {
                                        $selected_posts = array_slice(wp_list_pluck($posts, 'ID'), 0, 5);
                                    }

                                    foreach ($posts as $post) {
                                        $checked = in_array($post->ID, $selected_posts) ? 'checked' : '';
                                        echo '<label><input type="checkbox" name="blocks[' . esc_attr($index) . '][posts][]" value="' . esc_attr($post->ID) . '" ' . $checked . '> ' . esc_html($post->post_title) . '</label><br>';
                                    }
                                } else {
                                    echo '<p>' . esc_html__('No posts found in this category.', 'newsletter') . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="advertising-block">
                            <label><?php esc_html_e('Advertising HTML:', 'newsletter'); ?></label>
                            <textarea name="blocks[<?php echo esc_attr($index); ?>][html]" rows="5" style="width:100%;"><?php echo esc_textarea($block['html']); ?></textarea>
                        </div>
                        <?php endif; ?>

                        <button type="button" class="button remove-block"><?php esc_html_e('Remove Block', 'newsletter'); ?></button>
                    </div>
                    <hr>
                </div>
                <?php
            }
            $content = ob_get_clean();
            wp_send_json_success($content);
        } else {
            wp_send_json_error(__('Campaign not found.', 'newsletter'));
        }
    }
}

/**
 * Existing AJAX Handlers (Retained)
 */
add_action('wp_ajax_update_stories_list', 'update_stories_list_callback');
add_action('wp_ajax_load_story_preview', 'load_story_preview_callback');

/**
 * AJAX Handler: Update Stories List
 */
if (!function_exists('update_stories_list_callback')) {
    function update_stories_list_callback() {
        // Verify the nonce for security
        check_ajax_referer('newsletter_nonce', 'security');

        // Check required POST parameters and user capability
        if (
            !isset($_POST['start_date']) ||
            !isset($_POST['end_date']) ||
            !isset($_POST['newsletter_id']) ||
            !current_user_can('manage_options')
        ) {
            wp_send_json_error(__('Invalid request.', 'newsletter'));
        }

        // Sanitize and process request
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $newsletter_id = sanitize_text_field($_POST['newsletter_id']);

        // Retrieve categories for the newsletter from settings
        $categories = get_option("newsletter_categories_$newsletter_id", []);

        // Ensure categories are an array of integers
        if (is_string($categories)) {
            $categories = explode(',', $categories);
            $categories = array_map('intval', $categories);
        } elseif (is_array($categories)) {
            $categories = array_map('intval', $categories);
        } else {
            wp_send_json_error(__('Invalid categories format.', 'newsletter'));
        }

        // Prepare the date query
        $date_query = [
            [
                'after'     => $start_date,
                'before'    => $end_date,
                'inclusive' => true,
            ],
        ];

        // Prepare WP_Query arguments to fetch all posts within date range and selected categories
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'date_query'     => $date_query,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (!empty($categories)) {
            $args['category__in'] = $categories; // Use category__in for category IDs
        }

        // Query posts
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            ob_start();
            echo '<ul class="stories-sortable">';
            echo '<li class="story-item"><label><input type="checkbox" id="select-all"> ' . esc_html__('Select All', 'newsletter') . '</label></li>';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li class="story-item" data-post-id="' . esc_attr(get_the_ID()) . '">';
                echo '<label><input type="checkbox" class="story-checkbox"> ' . esc_html(get_the_title()) . '</label>';
                echo '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
            $html = ob_get_clean();
            wp_send_json_success($html);
        } else {
            wp_send_json_error(__('No stories found for the selected criteria.', 'newsletter'));
        }
    }
}

/**
 * AJAX Handler: Load Story Preview
 */
if (!function_exists('load_story_preview_callback')) {
    function load_story_preview_callback() {
        // Verify the nonce for security
        check_ajax_referer('newsletter_nonce', 'security');

        // Check required POST parameters and user capability
        if (
            !isset($_POST['post_ids']) ||
            !isset($_POST['newsletter_id']) ||
            !isset($_POST['template_id']) ||
            !current_user_can('manage_options')
        ) {
            wp_send_json_error(__('Invalid request.', 'newsletter'));
        }

        // Sanitize and process request
        $post_ids = array_map('intval', $_POST['post_ids']);
        $newsletter_id = sanitize_text_field($_POST['newsletter_id']);
        $template_id = sanitize_text_field($_POST['template_id']);

        if (empty($post_ids)) {
            wp_send_json_error(__('No posts selected.', 'newsletter'));
        }

        // Retrieve the selected template for the newsletter
        if ($template_id !== 'default' && !empty($template_id)) {
            $templates = get_option('newsletter_templates', []);
            if (isset($templates[$template_id])) {
                $selected_template = $templates[$template_id];
            } else {
                wp_send_json_error(__('Selected template does not exist.', 'newsletter'));
            }
        } else {
            // Use the newsletter's default template
            $template_id_stored = get_option("newsletter_template_id_$newsletter_id", 'default');
            $templates = get_option('newsletter_templates', []);
            if ($template_id_stored !== 'default' && isset($templates[$template_id_stored])) {
                $selected_template = $templates[$template_id_stored];
            } else {
                // Define a default template if none is set
                $selected_template = [
                    'html' => '
                        <div class="newsletter-content">
                            {stories_loop}
                            <div class="newsletter-footer">
                                <p>
                                    *|LIST:DESCRIPTION|* <br /><br />
                                    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list.<br /><br />
                                    Our mailing address is:<br />
                                    *|HTML:LIST_ADDRESS_HTML|*<br /><br />
                                    &copy; *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.<br /><br />
                                    <a href="*|FORWARD|*">Forward</a> this email to a friend<br />
                                    <a href="*|UPDATE_PROFILE|*">Update your preferences</a><br /><br />
                                    *|IF:REWARDS|* *|HTML:REWARDS|* *|END:IF|*
                                </p>
                            </div>
                        </div>
                    ',
                    'css' => '' // No custom CSS for default template
                ];
            }
        }

        // Extract the stories loop content
        if (preg_match('/\{stories_loop\}(.*)\{\/stories_loop\}/s', $selected_template['html'], $matches)) {
            $story_template = $matches[1];
        } else {
            wp_send_json_error(__('Invalid template format. Missing {stories_loop} placeholder.', 'newsletter'));
        }

        $stories_output = '';

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if (!$post) {
                continue; // Skip if the post doesn't exist
            }

            // Prepare post data
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium') ?: '';
            $title = $post->post_title;
            $excerpt = $post->post_excerpt;
            if (empty($excerpt)) {
                $excerpt = wp_trim_words($post->post_content, 50, '...');
            }
            $permalink = get_permalink($post_id);

            // Replace placeholders in the story template
            $story_html = $story_template;
            $story_html = str_replace('{thumbnail_url}', esc_url($thumbnail_url), $story_html);
            $story_html = str_replace('{title}', esc_html($title), $story_html);
            $story_html = str_replace('{excerpt}', wp_kses_post($excerpt), $story_html);
            $story_html = str_replace('{permalink}', esc_url($permalink), $story_html);

            $stories_output .= $story_html;
        }

        // Replace the stories loop placeholder with actual stories
        $full_output = str_replace('{stories_loop}' . $story_template . '{/stories_loop}', $stories_output, $selected_template['html']);

        // Insert the Mailchimp footer if not already in the template
        if (strpos($full_output, 'newsletter-footer') === false) {
            $mailchimp_footer = '
                <div class="newsletter-footer">
                    <p>
                        *|LIST:DESCRIPTION|* <br /><br />
                        <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list.<br /><br />
                        Our mailing address is:<br />
                        *|HTML:LIST_ADDRESS_HTML|*<br /><br />
                        &copy; *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.<br /><br />
                        <a href="*|FORWARD|*">Forward</a> this email to a friend<br />
                        <a href="*|UPDATE_PROFILE|*">Update your preferences</a><br /><br />
                        *|IF:REWARDS|* *|HTML:REWARDS|* *|END:IF|*
                    </p>
                </div>
            ';
            $full_output .= $mailchimp_footer;
        }

        // Include the custom CSS in a <style> tag if the template has custom CSS
        if (!empty($selected_template['css'])) {
            $final_output = '<style>' . $selected_template['css'] . '</style>' . $full_output;
        } else {
            $final_output = $full_output;
        }

        if (!empty($final_output)) {
            wp_send_json_success($final_output);
        } else {
            wp_send_json_error(__('No valid posts found.', 'newsletter'));
        }
    }
}
?>
