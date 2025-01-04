<?php
// Ensure this file is being included by a parent file.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve any error message stored in transient by the form handler
$error_message = get_transient('newsletter_add_error');
if ($error_message) {
    delete_transient('newsletter_add_error');
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Add New Newsletter', 'newsletter'); ?></h1>

    <?php if (!empty($error_message)): ?>
        <div class="error"><p><?php echo esc_html($error_message); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('add_newsletter_action', 'newsletter_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="new_newsletter_name"><?php esc_html_e('Newsletter Name', 'newsletter'); ?></label></th>
                <td><input name="new_newsletter_name" type="text" id="new_newsletter_name" class="regular-text"></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="add_newsletter_submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Add Newsletter', 'newsletter'); ?>">
        </p>
    </form>
</div>
