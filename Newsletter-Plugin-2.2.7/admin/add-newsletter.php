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

            // Redirect to the individual newsletter settings tab
            wp_redirect(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_id));
            exit;
        } else {
            echo '<div class="error"><p>' . __('A newsletter with this name already exists.', 'newsletter') . '</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Add New Newsletter', 'newsletter'); ?></h1>
    <form method="post">
        <?php wp_nonce_field('add_newsletter_action', 'newsletter_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="new_newsletter_name"><?php esc_html_e('Newsletter Name', 'newsletter'); ?></label></th>
                <td><input name="new_newsletter_name" type="text" id="new_newsletter_name" value="" class="regular-text"></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="add_newsletter_submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Add Newsletter', 'newsletter'); ?>">
        </p>
    </form>
</div>
