<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get newsletter slug from URL parameters
$newsletter_slug = '';
if (isset($_GET['tab'])) {
    $newsletter_slug = sanitize_text_field($_GET['tab']);
} elseif (isset($_GET['page'])) {
    $page = sanitize_text_field($_GET['page']);
    if (strpos($page, 'newsletter-stories-') === 0) {
        $newsletter_slug = str_replace('newsletter-stories-', '', $page);
    }
}

// Check if the newsletter slug exists in the newsletter list
$newsletter_list = get_option('newsletter_list', []);
if (empty($newsletter_slug) || !array_key_exists($newsletter_slug, $newsletter_list)) {
    error_log('individual-settings.php: Invalid newsletter slug - ' . esc_attr($newsletter_slug));
    wp_die(__('Invalid newsletter selected.', 'newsletter'));
}

// Check if the user has the required capability
if (!current_user_can('manage_options')) {
    error_log('individual-settings.php: Current user lacks manage_options capability.');
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve current settings for the selected newsletter
$segment_id        = get_option("newsletter_segment_id_$newsletter_slug", '');
$reply_to          = get_option("newsletter_reply_to_$newsletter_slug", '');
$from_name         = get_option("newsletter_from_name_$newsletter_slug", '');
$default_subject   = get_option("newsletter_default_subject_$newsletter_slug", '');
$is_ad_hoc         = get_option("newsletter_is_ad_hoc_$newsletter_slug", 0);
$send_days         = get_option("newsletter_send_days_$newsletter_slug", []);
$send_time         = get_option("newsletter_send_time_$newsletter_slug", '');
$categories        = get_option("newsletter_categories_$newsletter_slug", []);
$custom_post_types = get_option("newsletter_custom_post_types_$newsletter_slug", []);
$custom_subject    = get_option("newsletter_custom_subject_$newsletter_slug", 0);
$custom_intro      = get_option("newsletter_custom_intro_$newsletter_slug", 0);
$track_opens       = get_option("newsletter_track_opens_$newsletter_slug", 1);
$track_clicks      = get_option("newsletter_track_clicks_$newsletter_slug", 1);
$template_id       = get_option("newsletter_template_id_$newsletter_slug", '');
$pdf_template_id   = get_option("newsletter_pdf_template_id_$newsletter_slug", '');
$opt_into_pdf      = get_option("newsletter_opt_into_pdf_$newsletter_slug", 0);

// Retrieve all available templates
$templates = get_option('newsletter_templates', []);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce for security
    if (!isset($_POST['settings_nonce']) || !wp_verify_nonce($_POST['settings_nonce'], 'save_settings_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } else {
        // Update all options with sanitized input
        update_option("newsletter_segment_id_$newsletter_slug", sanitize_text_field($_POST['newsletter_segment_id']));
        update_option("newsletter_reply_to_$newsletter_slug", sanitize_email($_POST['newsletter_reply_to']));
        update_option("newsletter_from_name_$newsletter_slug", sanitize_text_field($_POST['newsletter_from_name']));
        update_option("newsletter_default_subject_$newsletter_slug", sanitize_text_field($_POST['newsletter_default_subject']));
        update_option("newsletter_is_ad_hoc_$newsletter_slug", !empty($_POST['newsletter_is_ad_hoc']) ? 1 : 0);
        update_option("newsletter_send_days_$newsletter_slug", array_map('sanitize_text_field', $_POST['newsletter_send_days'] ?? []));
        update_option("newsletter_send_time_$newsletter_slug", sanitize_text_field($_POST['newsletter_send_time']));
        update_option("newsletter_categories_$newsletter_slug", array_map('absint', $_POST['newsletter_categories'] ?? []));
        update_option("newsletter_custom_post_types_$newsletter_slug", array_map('sanitize_text_field', $_POST['newsletter_custom_post_types'] ?? []));
        update_option("newsletter_custom_subject_$newsletter_slug", !empty($_POST['newsletter_custom_subject']) ? 1 : 0);
        update_option("newsletter_custom_intro_$newsletter_slug", !empty($_POST['newsletter_custom_intro']) ? 1 : 0);
        update_option("newsletter_track_opens_$newsletter_slug", !empty($_POST['newsletter_track_opens']) ? 1 : 0);
        update_option("newsletter_track_clicks_$newsletter_slug", !empty($_POST['newsletter_track_clicks']) ? 1 : 0);
        update_option("newsletter_template_id_$newsletter_slug", sanitize_text_field($_POST['newsletter_template_id']));
        update_option("newsletter_pdf_template_id_$newsletter_slug", sanitize_text_field($_POST['newsletter_pdf_template_id']));
        update_option("newsletter_opt_into_pdf_$newsletter_slug", !empty($_POST['newsletter_opt_into_pdf']) ? 1 : 0);

        echo '<div class="updated"><p>' . esc_html__('Newsletter settings updated successfully.', 'newsletter') . '</p></div>';

        // Refresh variables after update
        $segment_id        = get_option("newsletter_segment_id_$newsletter_slug", '');
        $reply_to          = get_option("newsletter_reply_to_$newsletter_slug", '');
        $from_name         = get_option("newsletter_from_name_$newsletter_slug", '');
        $default_subject   = get_option("newsletter_default_subject_$newsletter_slug", '');
        $is_ad_hoc         = get_option("newsletter_is_ad_hoc_$newsletter_slug", 0);
        $send_days         = get_option("newsletter_send_days_$newsletter_slug", []);
        $send_time         = get_option("newsletter_send_time_$newsletter_slug", '');
        $categories        = get_option("newsletter_categories_$newsletter_slug", []);
        $custom_post_types = get_option("newsletter_custom_post_types_$newsletter_slug", []);
        $custom_subject    = get_option("newsletter_custom_subject_$newsletter_slug", 0);
        $custom_intro      = get_option("newsletter_custom_intro_$newsletter_slug", 0);
        $track_opens       = get_option("newsletter_track_opens_$newsletter_slug", 1);
        $track_clicks      = get_option("newsletter_track_clicks_$newsletter_slug", 1);
        $template_id       = get_option("newsletter_template_id_$newsletter_slug", '');
        $pdf_template_id   = get_option("newsletter_pdf_template_id_$newsletter_slug", '');
        $opt_into_pdf      = get_option("newsletter_opt_into_pdf_$newsletter_slug", 0);
    }
}
?>
<div class="wrap">
    <h2><?php printf(esc_html__('%s Settings', 'newsletter'), esc_html($newsletter_list[$newsletter_slug])); ?></h2>

    <!-- Link to the external CSS file -->
    <link rel="stylesheet" type="text/css" href="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/css/newsletter-admin.css'); ?>">

    <form method="post">
        <?php wp_nonce_field('save_settings_action', 'settings_nonce'); ?>
        <div class="settings-container">
            <!-- Mailchimp Settings Section -->
            <div class="settings-section">
                <h2 class="settings-tab mailchimp-tab">
                    <img src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/images/mailchimp-logo.webp'); ?>" alt="<?php esc_attr_e('Mailchimp', 'newsletter'); ?>" style="height: 20px; vertical-align: middle;">
                </h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Segment ID', 'newsletter'); ?>
                        </th>
                        <td>
                            <input type="text" name="newsletter_segment_id" value="<?php echo esc_attr($segment_id); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Reply-to Email', 'newsletter'); ?>
                        </th>
                        <td>
                            <input type="email" name="newsletter_reply_to" value="<?php echo esc_attr($reply_to); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('From Name', 'newsletter'); ?>
                        </th>
                        <td>
                            <input type="text" name="newsletter_from_name" value="<?php echo esc_attr($from_name); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Default Subject Line', 'newsletter'); ?>
                        </th>
                        <td>
                            <input type="text" name="newsletter_default_subject" value="<?php echo esc_attr($default_subject); ?>" class="regular-text" />
                            <br>
                            <label>
                                <input type="checkbox" name="newsletter_custom_subject" value="1" <?php checked($custom_subject, 1); ?> />
                                <?php esc_html_e('Allow custom subject line (can be changed on scheduling page)', 'newsletter'); ?>
                            </label>
                        </td>
                    </tr>
                    <!-- Additional Mailchimp Settings -->
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Track Opens', 'newsletter'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="newsletter_track_opens" value="1" <?php checked($track_opens, 1); ?> />
                                <?php esc_html_e('Enable tracking of email opens', 'newsletter'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Track Clicks', 'newsletter'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="newsletter_track_clicks" value="1" <?php checked($track_clicks, 1); ?> />
                                <?php esc_html_e('Enable tracking of link clicks', 'newsletter'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Style Settings Section -->
            <div class="settings-section">
                <h2 class="settings-tab"><?php esc_html_e('Style Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="newsletter_template_id"><?php esc_html_e('Newsletter Template', 'newsletter'); ?></label>
                        </th>
                        <td>
                            <select id="newsletter_template_id" name="newsletter_template_id" class="regular-text">
                                <option value="default" <?php selected($template_id, 'default'); ?>><?php esc_html_e('Default Template', 'newsletter'); ?></option>
                                <?php
                                if (!empty($templates) && is_array($templates)) {
                                    foreach ($templates as $index => $template_item) {
                                        // Ensure 'name' key exists
                                        $template_name = isset($template_item['name']) ? $template_item['name'] : __('Untitled Template', 'newsletter');
                                        // Ensure 'id' or unique identifier is used as the value
                                        $template_value = isset($template_item['id']) ? $template_item['id'] : $index;
                                        // Determine if this option should be selected
                                        $selected_attr = ($template_value === $template_id) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($template_value) . '" ' . esc_attr($selected_attr) . '>' . esc_html($template_name) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">' . esc_html__('No templates found.', 'newsletter') . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <!-- Export to PDF Template -->
                    <tr valign="top">
                        <th scope="row">
                            <label for="newsletter_pdf_template_id"><?php esc_html_e('Export to PDF Template', 'newsletter'); ?></label>
                            <br>
                            <label>
                                <input type="checkbox" id="newsletter_opt_into_pdf" name="newsletter_opt_into_pdf" value="1" <?php checked($opt_into_pdf, 1); ?> />
                                <?php esc_html_e('Enable PDF', 'newsletter'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="newsletter_pdf_template_id" name="newsletter_pdf_template_id" class="regular-text" <?php echo ($opt_into_pdf ? '' : 'disabled'); ?>>
                                <option value="default" <?php selected($pdf_template_id, 'default'); ?>><?php esc_html_e('Default PDF Template', 'newsletter'); ?></option>
                                <?php
                                if (!empty($templates) && is_array($templates)) {
                                    foreach ($templates as $index => $template_item) {
                                        $template_name = isset($template_item['name']) ? $template_item['name'] : __('Untitled Template', 'newsletter');
                                        $selected = ($pdf_template_id === strval($index)) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($index) . '" ' . esc_attr($selected) . '>' . esc_html($template_name) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">' . esc_html__('No templates found.', 'newsletter') . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <!-- Custom Intro Block -->
                    <tr valign="top">
                        <th scope="row">
                            <label for="newsletter_custom_intro"><?php esc_html_e('Custom Intro Block', 'newsletter'); ?></label>
                        </th>
                        <td>
                        <label>
                                <input type="checkbox" id="newsletter_custom_intro" name="newsletter_custom_intro" value="1" <?php checked($custom_intro, 1); ?> />
                                <?php esc_html_e('Enable custom intro block', 'newsletter'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Scheduling Settings Section -->
            <div class="settings-section">
                <h2 class="settings-tab"><?php esc_html_e('Scheduling Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="newsletter_is_ad_hoc"><?php esc_html_e('Send as Ad Hoc', 'newsletter'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="newsletter_is_ad_hoc" name="newsletter_is_ad_hoc" value="1" <?php checked($is_ad_hoc, 1); ?> />
                                <?php esc_html_e('Enable ad hoc sending', 'newsletter'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top" class="scheduled-settings">
                        <th scope="row"><?php esc_html_e('Send Days', 'newsletter'); ?></th>
                        <td>
                            <div class="days-grid">
                                <?php
                                $days = [
                                    'monday'    => __('Monday', 'newsletter'),
                                    'tuesday'   => __('Tuesday', 'newsletter'),
                                    'wednesday' => __('Wednesday', 'newsletter'),
                                    'thursday'  => __('Thursday', 'newsletter'),
                                    'friday'    => __('Friday', 'newsletter'),
                                    'saturday'  => __('Saturday', 'newsletter'),
                                    'sunday'    => __('Sunday', 'newsletter'),
                                ];
                                foreach ($days as $value => $label) {
                                    $checked = in_array($value, $send_days) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="newsletter_send_days[]" value="' . esc_attr($value) . '" ' . $checked . ' /> ' . esc_html($label) . '</label>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top" class="scheduled-settings">
                        <th scope="row"><label for="newsletter_send_time"><?php esc_html_e('Send Time', 'newsletter'); ?></label></th>
                        <td>
                            <input type="time" id="newsletter_send_time" name="newsletter_send_time" value="<?php echo esc_attr($send_time); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Content Settings Section -->
            <div class="settings-section">
                <h2 class="settings-tab"><?php esc_html_e('Content Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <!-- Categories as checkboxes -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Post Categories', 'newsletter'); ?></th>
                        <td>
                            <div class="categories-grid">
                                <?php
                                $all_categories = get_categories(['hide_empty' => false]);
                                foreach ($all_categories as $category) {
                                    $checked = in_array($category->term_id, $categories) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="newsletter_categories[]" value="' . esc_attr($category->term_id) . '" ' . $checked . ' /> ' . esc_html($category->name) . '</label>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <!-- Custom Post Types (Premium Feature) -->
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Custom Post Types', 'newsletter'); ?>
                            <span class="premium-icon dashicons dashicons-lock"></span>
                            <p class="description premium-description"><?php esc_html_e('Support for Custom Post Types is available in the Premium Version.', 'newsletter'); ?></p>
                        </th>
                        <td>
                            <div class="custom-post-types-grid">
                                <?php
                                $args = ['public' => true, '_builtin' => false];
                                $output = 'names';
                                $operator = 'and';
                                $post_types = get_post_types($args, $output, $operator);

                                if (!empty($post_types)) {
                                    foreach ($post_types as $post_type) {
                                        $checked = in_array($post_type, $custom_post_types) ? 'checked' : '';
                                        echo '<label><input type="checkbox" name="newsletter_custom_post_types[]" value="' . esc_attr($post_type) . '" ' . $checked . ' disabled /> ' . esc_html($post_type) . '</label>';
                                    }
                                } else {
                                    echo '<p>' . esc_html__('No custom post types found.', 'newsletter') . '</p>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php submit_button(__('Save Settings', 'newsletter')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function toggleScheduledSettings() {
        if ($('#newsletter_is_ad_hoc').is(':checked')) {
            $('.scheduled-settings').hide();
        } else {
            $('.scheduled-settings').show();
        }
    }

    // On page load
    toggleScheduledSettings();

    // On checkbox change
    $('#newsletter_is_ad_hoc').change(function() {
        toggleScheduledSettings();
    });
});
</script>