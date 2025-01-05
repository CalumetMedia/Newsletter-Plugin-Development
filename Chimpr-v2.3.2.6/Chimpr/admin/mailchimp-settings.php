<?php

// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Handle form submission for Mailchimp settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle test connection
    if (isset($_POST['test_connection'])) {
        $mailchimp = new Newsletter_Mailchimp_API();
        $result = $mailchimp->validate_connection();

        if (!is_wp_error($result)) {
            echo '<div class="updated"><p>' . esc_html__('Successfully connected to Mailchimp!', 'newsletter') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Failed to connect to Mailchimp: ', 'newsletter') . esc_html($result->get_error_message()) . '</p></div>';
        }
    } 
    // Handle settings save
    elseif (isset($_POST['mailchimp_settings_nonce']) && wp_verify_nonce($_POST['mailchimp_settings_nonce'], 'update_mailchimp_settings')) {
        update_option('mailchimp_api_key', sanitize_text_field($_POST['mailchimp_api_key']));
        update_option('mailchimp_list_id', sanitize_text_field($_POST['mailchimp_list_id']));

        echo '<div class="updated"><p>' . esc_html__('Mailchimp settings updated successfully.', 'newsletter') . '</p></div>';
    } else {
        echo '<div class="error"><p>' . esc_html__('Security check failed. Mailchimp settings not saved.', 'newsletter') . '</p></div>';
    }
}

// Retrieve current Mailchimp settings
$mailchimp_api_key = get_option('mailchimp_api_key', '');
$mailchimp_list_id = get_option('mailchimp_list_id', '');

?>

<style>
    .mailchimp-settings {
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        margin-top: 20px;
    }
    .mailchimp-settings h2 {
        margin-top: 0;
    }
    .mailchimp-settings table.form-table th {
        width: 200px;
        padding: 10px 5px;
        vertical-align: top;
    }
    .mailchimp-settings table.form-table td {
        padding: 10px 5px;
    }
    .mailchimp-settings input[type="text"],
    .mailchimp-settings input[type="email"],
    .mailchimp-settings input[type="password"],
    .mailchimp-settings select {
        width: 50%;
    }
</style>

<div class="mailchimp-settings">
    <h2><?php esc_html_e('Mailchimp Settings', 'newsletter'); ?></h2>
    <form method="post">
        <?php wp_nonce_field('update_mailchimp_settings', 'mailchimp_settings_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Mailchimp API Key', 'newsletter'); ?></th>
                <td>
                    <input type="text" name="mailchimp_api_key" value="<?php echo esc_attr($mailchimp_api_key); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e('Enter your Mailchimp API key.', 'newsletter'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Mailchimp List ID', 'newsletter'); ?></th>
                <td>
                    <input type="text" name="mailchimp_list_id" value="<?php echo esc_attr($mailchimp_list_id); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e('Enter your Mailchimp List ID.', 'newsletter'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php submit_button(__('Test Connection', 'newsletter'), 'secondary', 'test_connection', false); ?>
            <?php submit_button(__('Save Mailchimp Settings', 'newsletter'), 'primary', 'submit', false); ?>
        </p>
    </form>
</div>