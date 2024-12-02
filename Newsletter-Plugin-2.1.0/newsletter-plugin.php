<?php
/**
 * Plugin Name:       Newsletter Automator v2.1
 * Description:       New ability to create, edit, and delete templates and apply them to newsletters. Newsletter story lineup now includes drag and drop to reorder.  Temporarily moving all settings to wordpress nav while rebuilding in a more modular setup.
 * Version:           2.1
 * Author:            Jon Stewart
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Add the main menu and submenus
 */
add_action('admin_menu', 'newsletter_setup_menu_v21');
function newsletter_setup_menu_v21() {
    // Main menu page with a fancy envelope icon
    add_menu_page(
        'Newsletter v2.1',                // Updated main menu header
        'Newsletter v2.1',            // Menu title
        'manage_options',                 // Capability
        'newsletter-settings-v21',        // Menu slug (unique to v2.1)
        'newsletter_all_settings_page_v21',  // Callback function
        'dashicons-email-alt2',           // Icon (Dashicons envelope icon)
        21                                // Position
    );

    // Submenus for main operations
    add_submenu_page('newsletter-settings-v21', 'Settings', 'Settings', 'manage_options', 'newsletter-settings-v21', 'newsletter_all_settings_page_v21');
    add_submenu_page('newsletter-settings-v21', 'Add Newsletter', 'Add Newsletter', 'manage_options', 'newsletter-add-newsletter-v21', 'newsletter_add_newsletter_page_v21');
    add_submenu_page('newsletter-settings-v21', 'Delete Newsletter', 'Delete Newsletter', 'manage_options', 'newsletter-delete-newsletter-v21', 'newsletter_delete_newsletter_page_v21');
    add_submenu_page('newsletter-settings-v21', 'Templates', 'Templates', 'manage_options', 'newsletter-templates-v21', 'newsletter_templates_page_v21');

    // Dynamically add submenu items for each newsletter for the "stories" page
    $newsletters = get_option('newsletter_list', []);
    foreach ($newsletters as $newsletter_id => $newsletter_name) {
        add_submenu_page(
            'newsletter-settings-v21',
            esc_html($newsletter_name) . ' Stories',    // Page title
            esc_html($newsletter_name),                 // Menu title
            'manage_options',
            'newsletter-stories-v21-' . $newsletter_id,     // Unique slug for each newsletter in v2.1
            function() use ($newsletter_id) {           // Callback function to load the stories page
                newsletter_stories_page_v21($newsletter_id);
            }
        );
    }
}

/**
 * Callback functions for each page
 */
function newsletter_all_settings_page_v21() {
    include plugin_dir_path(__FILE__) . 'admin/newsletter-settings-tabs.php';
}

function newsletter_add_newsletter_page_v21() {
    include plugin_dir_path(__FILE__) . 'admin/add-newsletter.php';
}

function newsletter_delete_newsletter_page_v21() {
    include plugin_dir_path(__FILE__) . 'admin/delete-newsletter.php';
}

function newsletter_stories_page_v21($newsletter_id) {
    include plugin_dir_path(__FILE__) . 'admin/newsletter-stories.php';
}

function newsletter_templates_page_v21() {
    include plugin_dir_path(__FILE__) . 'admin/newsletter-templates.php';
}

/**
 * Register settings for the subpages
 */
add_action('admin_init', 'newsletter_register_settings_v21');
function newsletter_register_settings_v21() {
    register_setting('newsletter_add_newsletter_group_v21', 'newsletter_list');
}

/**
 * Enqueue scripts and styles for admin pages
 */
add_action('admin_enqueue_scripts', 'newsletter_admin_enqueue_scripts_v21');
function newsletter_admin_enqueue_scripts_v21($hook) {
    // Only enqueue scripts and styles on newsletter-related admin pages
    if (strpos($hook, 'newsletter') === false) return;

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable'); // Enqueue Sortable
    wp_enqueue_style('jquery-ui-datepicker-style', '//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css'); // Smoothness theme

    wp_enqueue_style(
        'newsletter-admin-css',
        plugin_dir_url(__FILE__) . 'admin/css/newsletter-admin.css',
        [],
        '2.1'
    );

    wp_enqueue_style(
        'newsletter-phone-css',
        plugin_dir_url(__FILE__) . 'admin/css/newsletter-phone.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'newsletter-admin-js',
        plugin_dir_url(__FILE__) . 'admin/js/newsletter-admin.js',
        ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'],
        '1.0',
        true
    );

    wp_localize_script('newsletter-admin-js', 'newsletterData', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'ajaxNonce'    => wp_create_nonce('newsletter_nonce'),
        'newsletterId' => isset($_GET['newsletter']) ? sanitize_text_field($_GET['newsletter']) : '',
    ]);
}

/**
 * Register AJAX actions for updating stories dynamically
 */
add_action('wp_ajax_update_stories_list', 'update_stories_list_callback_v21');
add_action('wp_ajax_load_story_preview', 'load_story_preview_callback_v21');

/**
 * AJAX Handler: Update Stories List
 */
function update_stories_list_callback_v21() {
    check_ajax_referer('newsletter_nonce', 'security');

    if (
        !isset($_POST['start_date']) ||
        !isset($_POST['end_date']) ||
        !isset($_POST['newsletter_id']) ||
        !current_user_can('manage_options')
    ) {
        wp_send_json_error(__('Invalid request.', 'newsletter'));
    }

    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $newsletter_id = sanitize_text_field($_POST['newsletter_id']);
    $categories = get_option("newsletter_categories_$newsletter_id", []);

    if (is_string($categories)) {
        $categories = explode(',', $categories);
        $categories = array_map('intval', $categories);
    } elseif (is_array($categories)) {
        $categories = array_map('intval', $categories);
    } else {
        wp_send_json_error(__('Invalid categories format.', 'newsletter'));
    }

    $date_query = [
        [
            'after'     => $start_date,
            'before'    => $end_date,
            'inclusive' => true,
        ],
    ];

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'date_query'     => $date_query,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if (!empty($categories)) {
        $args['category__in'] = $categories;
    }

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

/**
 * AJAX Handler: Load Story Preview
 */
function load_story_preview_callback_v21() {
    check_ajax_referer('newsletter_nonce', 'security');

    if (
        !isset($_POST['post_ids']) ||
        !isset($_POST['newsletter_id']) ||
        !isset($_POST['template_id']) ||
        !current_user_can('manage_options')
    ) {
        wp_send_json_error(__('Invalid request.', 'newsletter'));
    }

    $post_ids = array_map('intval', $_POST['post_ids']);
    $newsletter_id = sanitize_text_field($_POST['newsletter_id']);
    $template_id = sanitize_text_field($_POST['template_id']);

    if (empty($post_ids)) {
        wp_send_json_error(__('No posts selected.', 'newsletter'));
    }

    if ($template_id !== 'default' && !empty($template_id)) {
        $templates = get_option('newsletter_templates', []);
        if (isset($templates[$template_id])) {
            $selected_template = $templates[$template_id];
        } else {
            wp_send_json_error(__('Selected template does not exist.', 'newsletter'));
        }
    } else {
        $template_id_stored = get_option("newsletter_template_id_$newsletter_id", 'default');
        $templates = get_option('newsletter_templates', []);
        if ($template_id_stored !== 'default' && isset($templates[$template_id_stored])) {
            $selected_template = $templates[$template_id_stored];
        } else {
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
                'css' => ''
            ];
        }
    }

    if (preg_match('/\{stories_loop\}(.*)\{\/stories_loop\}/s', $selected_template['html'], $matches)) {
        $story_template = $matches[1];
    } else {
        wp_send_json_error(__('Invalid template format. Missing {stories_loop} placeholder.', 'newsletter'));
    }

    $stories_output = '';

    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);

        if (!$post) {
            continue;
        }

        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium') ?: '';
        $title = $post->post_title;
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($post->post_content, 50, '...');
        }
        $permalink = get_permalink($post_id);

        $story_html = $story_template;
        $story_html = str_replace('{thumbnail_url}', esc_url($thumbnail_url), $story_html);
        $story_html = str_replace('{title}', esc_html($title), $story_html);
        $story_html = str_replace('{excerpt}', wp_kses_post($excerpt), $story_html);
        $story_html = str_replace('{permalink}', esc_url($permalink), $story_html);

        $stories_output .= $story_html;
    }

    $full_output = str_replace('{stories_loop}' . $story_template . '{/stories_loop}', $stories_output, $selected_template['html']);

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
?>
