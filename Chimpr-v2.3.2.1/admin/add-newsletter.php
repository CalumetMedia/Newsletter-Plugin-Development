<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Handle form submission
if (isset($_POST['add_newsletter_submit']) && current_user_can('manage_options')) {
    if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'add_newsletter_action')) {
        set_transient('newsletter_add_error', __('Security check failed. Please try again.', 'newsletter'), 30);
        wp_redirect(admin_url('admin.php?page=newsletter-settings&tab=add_newsletter'));
        exit;
    }

    $new_name = sanitize_text_field($_POST['new_newsletter_name']);
    if (empty($new_name)) {
        set_transient('newsletter_add_error', __('Please enter a newsletter name.', 'newsletter'), 30);
        wp_redirect(admin_url('admin.php?page=newsletter-settings&tab=add_newsletter'));
        exit;
    }

    $newsletter_list = get_option('newsletter_list', []);
    $slug = sanitize_title($new_name);
    $original_slug = $slug;
    $i = 1;
    
    while (array_key_exists($slug, $newsletter_list)) {
        $slug = $original_slug . '-' . $i;
        $i++;
    }

    $newsletter_list[$slug] = $new_name;
    update_option('newsletter_list', $newsletter_list);
    wp_redirect(admin_url('admin.php?page=newsletter-settings&tab=' . urlencode($slug)));
    exit;
}

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