<?php
if (!defined('ABSPATH')) { exit; }

// $newsletter_slug is set by newsletter-settings-tabs.php
if (empty($newsletter_slug)) {
    wp_die(__('Invalid newsletter selected.', 'newsletter'));
}

// Check if the newsletter slug exists in the newsletter list
$newsletter_list = get_option('newsletter_list', []);
if (!array_key_exists($newsletter_slug, $newsletter_list)) {
    error_log('individual-settings.php: Invalid newsletter slug - ' . esc_attr($newsletter_slug));
    wp_die(__('Invalid newsletter selected.', 'newsletter'));
}

// Check user capability
if (!current_user_can('manage_options')) {
    error_log('individual-settings.php: Current user lacks manage_options capability.');
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve current settings for the selected newsletter
$reply_to        = get_option("newsletter_reply_to_$newsletter_slug", '');
$from_name       = get_option("newsletter_from_name_$newsletter_slug", '');
$is_ad_hoc       = get_option("newsletter_is_ad_hoc_$newsletter_slug", 0);
$send_days       = get_option("newsletter_send_days_$newsletter_slug", []);
$send_time       = get_option("newsletter_send_time_$newsletter_slug", '');
$custom_subject  = get_option("newsletter_custom_subject_$newsletter_slug", 0);
$track_opens     = get_option("newsletter_track_opens_$newsletter_slug", 1);
$track_clicks    = get_option("newsletter_track_clicks_$newsletter_slug", 1);
$pdf_template_id = get_option("newsletter_pdf_template_id_$newsletter_slug", '');
$opt_into_pdf    = get_option("newsletter_opt_into_pdf_$newsletter_slug", 0);

// Retrieve all available templates
$templates = get_option('newsletter_templates', []);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce for security
    if (!isset($_POST['settings_nonce']) || !wp_verify_nonce($_POST['settings_nonce'], 'save_settings_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } else {
        // Update all options with sanitized input
        update_option("newsletter_reply_to_$newsletter_slug", sanitize_email($_POST['newsletter_reply_to']));
        update_option("newsletter_from_name_$newsletter_slug", sanitize_text_field($_POST['newsletter_from_name']));
        update_option("newsletter_is_ad_hoc_$newsletter_slug", !empty($_POST['newsletter_is_ad_hoc']) ? 1 : 0);
        update_option("newsletter_send_days_$newsletter_slug", array_map('sanitize_text_field', $_POST['newsletter_send_days'] ?? []));
        update_option("newsletter_send_time_$newsletter_slug", sanitize_text_field($_POST['newsletter_send_time']));
        update_option("newsletter_custom_subject_$newsletter_slug", !empty($_POST['newsletter_custom_subject']) ? 1 : 0);
        update_option("newsletter_track_opens_$newsletter_slug", !empty($_POST['newsletter_track_opens']) ? 1 : 0);
        update_option("newsletter_track_clicks_$newsletter_slug", !empty($_POST['newsletter_track_clicks']) ? 1 : 0);
        update_option("newsletter_pdf_template_id_$newsletter_slug", sanitize_text_field($_POST['newsletter_pdf_template_id']));
        update_option("newsletter_opt_into_pdf_$newsletter_slug", !empty($_POST['newsletter_opt_into_pdf']) ? 1 : 0);

        echo '<div class="updated"><p>' . esc_html__('Newsletter settings updated successfully.', 'newsletter') . '</p></div>';

        // Refresh variables after update
        $reply_to        = get_option("newsletter_reply_to_$newsletter_slug", '');
        $from_name       = get_option("newsletter_from_name_$newsletter_slug", '');
        $is_ad_hoc       = get_option("newsletter_is_ad_hoc_$newsletter_slug", 0);
        $send_days       = get_option("newsletter_send_days_$newsletter_slug", []);
        $send_time       = get_option("newsletter_send_time_$newsletter_slug", '');
        $custom_subject  = get_option("newsletter_custom_subject_$newsletter_slug", 0);
        $track_opens     = get_option("newsletter_track_opens_$newsletter_slug", 1);
        $track_clicks    = get_option("newsletter_track_clicks_$newsletter_slug", 1);
        $pdf_template_id = get_option("newsletter_pdf_template_id_$newsletter_slug", '');
        $opt_into_pdf    = get_option("newsletter_opt_into_pdf_$newsletter_slug", 0);
    }
}
?>

<div class="wrap">
    <h1><?php printf(esc_html__('%s Settings', 'newsletter'), esc_html($newsletter_list[$newsletter_slug])); ?></h1>

    <!-- Link to the external CSS file -->
    <style>
        <?php echo file_get_contents(plugin_dir_path(__FILE__) . 'assets/css/newsletter-admin.css'); ?>
    </style>

    <form method="post">
        <?php wp_nonce_field('save_settings_action', 'settings_nonce'); ?>
        <div class="settings-boxes">
            <!-- Mailchimp Settings Box -->
            <div class="settings-box">
                <h2><?php esc_html_e('Mailchimp Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="newsletter_reply_to"><?php esc_html_e('Reply-to Email', 'newsletter'); ?></label></th>
                        <td><input type="email" id="newsletter_reply_to" name="newsletter_reply_to" value="<?php echo esc_attr($reply_to); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="newsletter_from_name"><?php esc_html_e('From Name', 'newsletter'); ?></label></th>
                        <td><input type="text" id="newsletter_from_name" name="newsletter_from_name" value="<?php echo esc_attr($from_name); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Track Opens', 'newsletter'); ?></th>
                        <td>
                            <label><input type="checkbox" name="newsletter_track_opens" value="1" <?php checked($track_opens, 1); ?> /> <?php esc_html_e('Enable tracking of email opens', 'newsletter'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Track Clicks', 'newsletter'); ?></th>
                        <td>
                            <label><input type="checkbox" name="newsletter_track_clicks" value="1" <?php checked($track_clicks, 1); ?> /> <?php esc_html_e('Enable tracking of link clicks', 'newsletter'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- PDF Settings Box -->
            <div class="settings-box">
                <h2><?php esc_html_e('PDF Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Enable PDF Export', 'newsletter'); ?></th>
                        <td>
                            <label><input type="checkbox" id="newsletter_opt_into_pdf" name="newsletter_opt_into_pdf" value="1" <?php checked($opt_into_pdf, 1); ?> /> <?php esc_html_e('Enable PDF Export', 'newsletter'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="newsletter_pdf_template_id"><?php esc_html_e('PDF Template', 'newsletter'); ?></label></th>
                        <td>
                            <select id="newsletter_pdf_template_id" name="newsletter_pdf_template_id" <?php echo ($opt_into_pdf ? '' : 'disabled'); ?>>
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
                </table>
            </div>

            <!-- Scheduling Settings Box -->
            <div class="settings-box">
                <h2><?php esc_html_e('Scheduling Settings', 'newsletter'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Send as Ad Hoc', 'newsletter'); ?></th>
                        <td>
                            <label><input type="checkbox" id="newsletter_is_ad_hoc" name="newsletter_is_ad_hoc" value="1" <?php checked($is_ad_hoc, 1); ?> /> <?php esc_html_e('Enable ad hoc sending', 'newsletter'); ?></label>
                        </td>
                    </tr>
                </table>
                <div class="scheduled-settings">
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Send Days', 'newsletter'); ?></th>
                            <td>
                                <div>
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
                                        echo '<div><label><input type="checkbox" name="newsletter_send_days[]" value="' . esc_attr($value) . '" ' . $checked . ' /> ' . esc_html($label) . '</label></div>';
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="newsletter_send_time"><?php esc_html_e('Send Time', 'newsletter'); ?></label></th>
                            <td><input type="time" id="newsletter_send_time" name="newsletter_send_time" value="<?php echo esc_attr($send_time); ?>" /></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:20px; margin-top:20px;">
            <button type="submit" class="button button-primary button-large action-button" style="width:200px; padding:10px 20px; text-align:left;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-save" style="vertical-align:middle; margin-right:8px;">
                    <path d="M19 21H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2
                    2-2h11l5 5v11c0 1.1-.9 2-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <strong><?php esc_html_e('SAVE SETTINGS', 'newsletter'); ?></strong>
            </button>

            <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-stories-' . $newsletter_slug)); ?>"
               class="button button-large action-button"
               style="width:200px; padding:10px 20px; background-color:#28a745; color:#fff; text-align:left;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-zap" style="vertical-align:middle; margin-right:8px;">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <strong><?php esc_html_e('GENERATOR', 'newsletter'); ?></strong>
            </a>
        </div>

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
    toggleScheduledSettings();
    $('#newsletter_is_ad_hoc').change(function() {
        toggleScheduledSettings();
    });

    // Enable or disable PDF template select based on checkbox
    $('#newsletter_opt_into_pdf').change(function() {
        if ($(this).is(':checked')) {
            $('#newsletter_pdf_template_id').prop('disabled', false);
        } else {
            $('#newsletter_pdf_template_id').prop('disabled', true);
        }
    });
});
</script>
