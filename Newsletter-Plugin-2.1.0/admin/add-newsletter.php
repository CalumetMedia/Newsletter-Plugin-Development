<?php
// Ensure this file is being included by a parent file.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve the existing newsletter list
$newsletter_list = get_option('newsletter_list', []);

// Handle form submission for adding a new newsletter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_newsletter_submit'])) {
    // Verify nonce for security
    if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'add_newsletter_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } elseif (empty($_POST['new_newsletter_name'])) {
        echo '<div class="error"><p>' . __('Newsletter Name is required.', 'newsletter') . '</p></div>';
    } else {
        $newsletter_name = sanitize_text_field($_POST['new_newsletter_name']);
        $newsletter_id = sanitize_title($newsletter_name);

        if (!isset($newsletter_list[$newsletter_id])) {
            // Add the new newsletter
            $newsletter_list[$newsletter_id] = $newsletter_name;
            update_option('newsletter_list', $newsletter_list);

            // Save existing fields from version 2.0
            update_option("newsletter_segment_id_$newsletter_id", sanitize_text_field($_POST['new_newsletter_segment_id']));
            update_option("newsletter_reply_to_$newsletter_id", sanitize_email($_POST['new_newsletter_reply_to']));
            update_option("newsletter_is_ad_hoc_$newsletter_id", !empty($_POST['new_newsletter_is_ad_hoc']) ? 1 : 0);
            update_option("newsletter_send_days_$newsletter_id", array_map('sanitize_text_field', $_POST['new_newsletter_send_days'] ?? []));
            update_option("newsletter_send_time_$newsletter_id", sanitize_text_field($_POST['new_newsletter_send_time']));
            update_option("newsletter_categories_$newsletter_id", array_map('absint', $_POST['new_newsletter_categories'] ?? []));

            // Save new fields
            update_option("newsletter_opt_into_pdf_$newsletter_id", !empty($_POST['newsletter_opt_into_pdf']) ? 1 : 0);
            update_option("newsletter_template_id_$newsletter_id", sanitize_text_field($_POST['newsletter_template'] ?? ''));
            update_option("newsletter_custom_subject_$newsletter_id", !empty($_POST['newsletter_custom_subject']) ? 1 : 0);
            update_option("newsletter_custom_intro_$newsletter_id", !empty($_POST['newsletter_custom_intro']) ? 1 : 0);

            // Retrieve all templates to get the selected template name
            $templates = get_option('newsletter_templates', []);
            $selected_template_id = sanitize_text_field($_POST['newsletter_template'] ?? '');
            if ($selected_template_id !== '' && isset($templates[$selected_template_id])) {
                $selected_template_name = $templates[$selected_template_id]['name'];
            } else {
                $selected_template_name = __('None', 'newsletter');
            }

            // Feedback message with details
            echo '<div class="updated"><p>' . __('Newsletter added successfully.', 'newsletter') . '</p>';
            echo '<p>' . __('Newsletter Name: ') . esc_html($newsletter_name) . '</p>';
            echo '<p>' . __('PDF Opt-in: ') . (!empty($_POST['newsletter_opt_into_pdf']) ? __('Yes', 'newsletter') : __('No', 'newsletter')) . '</p>';
            echo '<p>' . __('Selected Template: ') . esc_html($selected_template_name) . '</p>';
            echo '<p>' . __('Custom Subject Line: ') . (!empty($_POST['newsletter_custom_subject']) ? __('Enabled', 'newsletter') : __('Disabled', 'newsletter')) . '</p>';
            echo '<p>' . __('Custom Intro Block: ') . (!empty($_POST['newsletter_custom_intro']) ? __('Enabled', 'newsletter') : __('Disabled', 'newsletter')) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="error"><p>' . __('A newsletter with this name already exists.', 'newsletter') . '</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Add Newsletter', 'newsletter'); ?></h1>
    <form method="post" action="">
        <?php
        // Add nonce field for security
        wp_nonce_field('add_newsletter_action', 'newsletter_nonce');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="new_newsletter_name"><?php esc_html_e('Newsletter Name', 'newsletter'); ?></label></th>
                <td>
                    <input type="text" id="new_newsletter_name" name="new_newsletter_name" class="regular-text" required />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="new_newsletter_segment_id"><?php esc_html_e('Segment ID', 'newsletter'); ?></label></th>
                <td>
                    <input type="text" id="new_newsletter_segment_id" name="new_newsletter_segment_id" class="regular-text" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="new_newsletter_reply_to"><?php esc_html_e('Reply-to Email', 'newsletter'); ?></label></th>
                <td>
                    <input type="email" id="new_newsletter_reply_to" name="new_newsletter_reply_to" class="regular-text" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Send as Ad Hoc', 'newsletter'); ?></th>
                <td>
                    <input type="checkbox" id="new_newsletter_is_ad_hoc" name="new_newsletter_is_ad_hoc" value="1" />
                    <label for="new_newsletter_is_ad_hoc"><?php esc_html_e('Enable ad hoc (non-scheduled) sending.', 'newsletter'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Send Days', 'newsletter'); ?></th>
                <td>
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
                        echo '<label><input type="checkbox" name="new_newsletter_send_days[]" value="' . esc_attr($value) . '" /> ' . esc_html($label) . '</label><br>';
                    }
                    ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="new_newsletter_send_time"><?php esc_html_e('Send Time', 'newsletter'); ?></label></th>
                <td>
                    <input type="time" id="new_newsletter_send_time" name="new_newsletter_send_time" class="regular-text" />
                    <p class="description"><?php esc_html_e('Specify the time to send the newsletter.', 'newsletter'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="new_newsletter_categories"><?php esc_html_e('Categories', 'newsletter'); ?></label></th>
                <td>
                    <select id="new_newsletter_categories" name="new_newsletter_categories[]" multiple="multiple" class="regular-text">
                        <?php
                        $categories = get_categories(['hide_empty' => false]);
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Select categories to filter content for this newsletter.', 'newsletter'); ?></p>
                </td>
            </tr>

            <!-- New Fields Start Here -->

            <!-- Checkbox to Opt into PDF -->
            <tr valign="top">
                <th scope="row"><label for="newsletter_opt_into_pdf"><?php esc_html_e('Opt into PDF', 'newsletter'); ?></label></th>
                <td>
                    <input type="checkbox" id="newsletter_opt_into_pdf" name="newsletter_opt_into_pdf" value="1" />
                    <label for="newsletter_opt_into_pdf"><?php esc_html_e('Enable PDF attachment for this newsletter.', 'newsletter'); ?></label>
                </td>
            </tr>

            <!-- Dropdown Menu for Existing Templates -->
            <tr valign="top">
                <th scope="row"><label for="newsletter_template"><?php esc_html_e('Newsletter Template', 'newsletter'); ?></label></th>
                <td>
                    <select id="newsletter_template" name="newsletter_template" class="regular-text">
                        <option value=""><?php esc_html_e('-- Select a Template --', 'newsletter'); ?></option>
                        <?php
                        // Retrieve all available templates from the option
                        $templates = get_option('newsletter_templates', []);

                        if (!empty($templates) && is_array($templates)) {
                            foreach ($templates as $index => $template_item) {
                                // Ensure 'name' key exists
                                if (isset($template_item['name'])) {
                                    $template_name = $template_item['name'];
                                } else {
                                    $template_name = __('Untitled Template', 'newsletter');
                                }

                                // Check if this template was selected previously (for sticky forms)
                                $selected = (isset($_POST['newsletter_template']) && $_POST['newsletter_template'] === strval($index)) ? 'selected' : '';

                                echo '<option value="' . esc_attr($index) . '" ' . esc_attr($selected) . '>' . esc_html($template_name) . '</option>';
                            }
                        } else {
                            echo '<option value="">' . esc_html__('No templates found.', 'newsletter') . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Select an existing template for this newsletter.', 'newsletter'); ?></p>
                </td>
            </tr>

            <!-- Checkbox for Custom Subject Line -->
            <tr valign="top">
                <th scope="row"><label for="newsletter_custom_subject"><?php esc_html_e('Custom Subject Line', 'newsletter'); ?></label></th>
                <td>
                    <input type="checkbox" id="newsletter_custom_subject" name="newsletter_custom_subject" value="1" />
                    <label for="newsletter_custom_subject"><?php esc_html_e('Enable a custom subject line for this newsletter.', 'newsletter'); ?></label>
                </td>
            </tr>

            <!-- Checkbox for Custom Intro Block -->
            <tr valign="top">
                <th scope="row"><label for="newsletter_custom_intro"><?php esc_html_e('Custom Intro Block', 'newsletter'); ?></label></th>
                <td>
                    <input type="checkbox" id="newsletter_custom_intro" name="newsletter_custom_intro" value="1" />
                    <label for="newsletter_custom_intro"><?php esc_html_e('Enable a custom introductory block for this newsletter.', 'newsletter'); ?></label>
                </td>
            </tr>

            <!-- New Fields End Here -->

        </table>

        <?php
        // Submit button
        submit_button(__('Add Newsletter', 'newsletter'), 'primary', 'add_newsletter_submit');
        ?>
    </form>
</div>
