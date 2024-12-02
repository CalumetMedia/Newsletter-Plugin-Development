<?php
// Ensure this file is only loaded within the context of the tabs page
if (!isset($newsletter_id) || !current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Retrieve the list of newsletters
$newsletter_list = get_option('newsletter_list', []);

// Retrieve current settings for the selected newsletter
$segment_id   = get_option("newsletter_segment_id_$newsletter_id", '');
$reply_to     = get_option("newsletter_reply_to_$newsletter_id", '');
$is_ad_hoc    = get_option("newsletter_is_ad_hoc_$newsletter_id", 0);
$send_days    = get_option("newsletter_send_days_$newsletter_id", []);
$send_time    = get_option("newsletter_send_time_$newsletter_id", '');
$categories   = get_option("newsletter_categories_$newsletter_id", []);
$template_id  = get_option("newsletter_template_id_$newsletter_id", '');

// Retrieve all available templates
$templates = get_option('newsletter_templates', []);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_option("newsletter_segment_id_$newsletter_id", sanitize_text_field($_POST['new_newsletter_segment_id']));
    update_option("newsletter_reply_to_$newsletter_id", sanitize_email($_POST['new_newsletter_reply_to']));
    update_option("newsletter_is_ad_hoc_$newsletter_id", !empty($_POST['new_newsletter_is_ad_hoc']) ? 1 : 0);
    update_option("newsletter_send_days_$newsletter_id", array_map('sanitize_text_field', $_POST['new_newsletter_send_days'] ?? []));
    update_option("newsletter_send_time_$newsletter_id", sanitize_text_field($_POST['new_newsletter_send_time']));
    update_option("newsletter_categories_$newsletter_id", array_map('sanitize_text_field', $_POST['new_newsletter_categories'] ?? []));
    update_option("newsletter_template_id_$newsletter_id", intval($_POST['new_newsletter_template_id']));

    echo '<div class="updated"><p>Newsletter settings updated successfully.</p></div>';

    // Refresh variables after update
    $segment_id   = get_option("newsletter_segment_id_$newsletter_id", '');
    $reply_to     = get_option("newsletter_reply_to_$newsletter_id", '');
    $is_ad_hoc    = get_option("newsletter_is_ad_hoc_$newsletter_id", 0);
    $send_days    = get_option("newsletter_send_days_$newsletter_id", []);
    $send_time    = get_option("newsletter_send_time_$newsletter_id", '');
    $categories   = get_option("newsletter_categories_$newsletter_id", []);
    $template_id  = get_option("newsletter_template_id_$newsletter_id", '');
}
?>

<div class="wrap">
    <h2><?php printf(esc_html__('%s Settings', 'newsletter'), esc_html($newsletter_list[$newsletter_id])); ?></h2>
    <form method="post">
        <table class="form-table">
            <!-- Existing settings fields -->
            <!-- ... (Other settings fields like Segment ID, Reply-to Email, etc.) ... -->

            <!-- Template Selection -->
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Template', 'newsletter'); ?></th>
                <td>
                    <select name="new_newsletter_template_id" class="regular-text">
                        <option value=""><?php esc_html_e('Default Template', 'newsletter'); ?></option>
                        <?php
                        if (!empty($templates)) {
                            foreach ($templates as $index => $template) {
                                $selected = ($template_id == $index) ? 'selected' : '';
                                echo '<option value="' . esc_attr($index) . '" ' . $selected . '>' . esc_html($template['name']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>' . esc_html__('No templates available', 'newsletter') . '</option>';
                        }
                        ?>
                    </select>
                    <?php if (empty($templates)): ?>
                        <p><?php esc_html_e('No templates found. Please create templates in the Templates tab.', 'newsletter'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- ... (Continue with other settings fields if any) ... -->
        </table>

        <?php submit_button('Save Settings'); ?>
    </form>
</div>
