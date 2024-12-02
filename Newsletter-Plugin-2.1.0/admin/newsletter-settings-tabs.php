<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Ensure only authorized users can access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get all newsletters
$newsletter_list = get_option('newsletter_list', []);

// Verify that a newsletter is selected and exists
$newsletter_id = isset($_GET['newsletter']) ? sanitize_text_field($_GET['newsletter']) : null;
$newsletter_name = $newsletter_list[$newsletter_id] ?? null;
?>
<div class="wrap">
    <h1><?php esc_html_e('Newsletter Settings', 'newsletter'); ?></h1>

    <!-- Tabs for Mailchimp and Each Newsletter -->
    <h2 class="nav-tab-wrapper">
        <!-- Mailchimp Settings Tab -->
        <a href="?page=newsletter-settings&tab=mailchimp" 
           class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'mailchimp') ? 'nav-tab-active' : ''; ?>">
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'images/mailchimp-logo.webp'); ?>" 
                 alt="<?php esc_attr_e('Mailchimp Settings', 'newsletter'); ?>" 
                 style="height: 20px; vertical-align: middle;">
        </a>

        <!-- Newsletter Tabs -->
        <?php foreach ($newsletter_list as $id => $name) : ?>
            <a href="?page=newsletter-settings&newsletter=<?php echo esc_attr($id); ?>" 
               class="nav-tab <?php echo ($newsletter_id === $id && (!isset($_GET['tab']) || $_GET['tab'] !== 'mailchimp')) ? 'nav-tab-active' : ''; ?>">
               <?php echo esc_html($name); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <?php
    // Determine active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'newsletter';

    if ($active_tab === 'mailchimp') {
        // Include Mailchimp Settings
        include plugin_dir_path(__FILE__) . 'mailchimp-settings.php';
    } elseif ($newsletter_name) {
        // Handle form submission for newsletter settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify nonce for security
            if (
                !isset($_POST['newsletter_settings_nonce']) ||
                !wp_verify_nonce($_POST['newsletter_settings_nonce'], 'update_newsletter_settings')
            ) {
                echo '<div class="error"><p>' . esc_html__('Security check failed. Settings not saved.', 'newsletter') . '</p></div>';
            } else {
                update_option("newsletter_segment_id_$newsletter_id", sanitize_text_field($_POST['newsletter_segment_id']));
                update_option("newsletter_reply_to_$newsletter_id", sanitize_email($_POST['newsletter_reply_to']));
                update_option("newsletter_is_ad_hoc_$newsletter_id", !empty($_POST['newsletter_is_ad_hoc']) ? 1 : 0);
                update_option("newsletter_send_days_$newsletter_id", array_map('sanitize_text_field', $_POST['newsletter_send_days'] ?? []));
                update_option("newsletter_send_time_$newsletter_id", sanitize_text_field($_POST['newsletter_send_time']));
                
                // Store categories as integers
                update_option("newsletter_categories_$newsletter_id", array_map('intval', $_POST['newsletter_categories'] ?? []));
                
                update_option("newsletter_template_id_$newsletter_id", intval($_POST['newsletter_template_id']));

                echo '<div class="updated"><p>' . esc_html__('Settings updated successfully for ', 'newsletter') . esc_html($newsletter_name) . '.</p></div>';
            }
        }

        // Retrieve current settings
        $segment_id = get_option("newsletter_segment_id_$newsletter_id", '');
        $reply_to = get_option("newsletter_reply_to_$newsletter_id", '');
        $is_ad_hoc = get_option("newsletter_is_ad_hoc_$newsletter_id", 0);
        $send_days = get_option("newsletter_send_days_$newsletter_id", []);
        $send_time = get_option("newsletter_send_time_$newsletter_id", '');
        $categories = get_option("newsletter_categories_$newsletter_id", []);
        $template_id = get_option("newsletter_template_id_$newsletter_id", 'default');
        ?>

        <!-- Settings Form for Selected Newsletter -->
        <form method="post">
            <?php wp_nonce_field('update_newsletter_settings', 'newsletter_settings_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Segment ID', 'newsletter'); ?></th>
                    <td>
                        <input type="text" name="newsletter_segment_id" value="<?php echo esc_attr($segment_id); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Reply-to Email', 'newsletter'); ?></th>
                    <td>
                        <input type="email" name="newsletter_reply_to" value="<?php echo esc_attr($reply_to); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Send as Ad Hoc', 'newsletter'); ?></th>
                    <td>
                        <input type="checkbox" name="newsletter_is_ad_hoc" value="1" <?php checked($is_ad_hoc, 1); ?> />
                        <p class="description"><?php esc_html_e('Enable ad hoc (non-scheduled) sending.', 'newsletter'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Send Days', 'newsletter'); ?></th>
                    <td>
                        <?php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        foreach ($days as $day) {
                            echo '<label><input type="checkbox" name="newsletter_send_days[]" value="' . esc_attr($day) . '" ' . (in_array($day, $send_days) ? 'checked' : '') . ' /> ' . ucfirst($day) . '</label><br>';
                        }
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Send Time', 'newsletter'); ?></th>
                    <td>
                        <input type="time" name="newsletter_send_time" value="<?php echo esc_attr($send_time); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Specify the time to send the newsletter.', 'newsletter'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Categories', 'newsletter'); ?></th>
                    <td>
                        <select name="newsletter_categories[]" multiple="multiple" class="regular-text" size="5">
                            <?php
                            $all_categories = get_categories(['hide_empty' => false]);
                            foreach ($all_categories as $category) {
                                $selected = in_array($category->term_id, $categories) ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select categories to filter content for this newsletter.', 'newsletter'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Template', 'newsletter'); ?></th>
                    <td>
                        <select name="newsletter_template_id" class="regular-text">
                            <option value="default" <?php selected($template_id, 'default'); ?>><?php esc_html_e('Default Template', 'newsletter'); ?></option>
                            <?php
                            $templates = get_option('newsletter_templates', []);
                            foreach ($templates as $id => $template) {
                                $selected_template = selected($template_id, $id, false);
                                $template_name = isset($template['name']) ? $template['name'] : $id;
                                echo '<option value="' . esc_attr($id) . '" ' . $selected_template . '>' . esc_html($template_name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select a template for this newsletter.', 'newsletter'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'newsletter')); ?>
        </form>
    <?php } else { ?>
        <p><?php esc_html_e('Select a newsletter tab to view its settings.', 'newsletter'); ?></p>
    <?php } ?>
</div>
