<?php
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve the list of newsletters
$newsletter_list = get_option('newsletter_list', []);

// Handle deletion
if (isset($_POST['delete_newsletter']) && !empty($_POST['newsletter_ids'])) {
    // Verify nonce for security
    if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'delete_newsletter_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } else {
        $newsletter_ids = array_map('sanitize_text_field', $_POST['newsletter_ids']);
        $deleted = [];
        $not_found = [];

        foreach ($newsletter_ids as $newsletter_id) {
            if (isset($newsletter_list[$newsletter_id])) {
                unset($newsletter_list[$newsletter_id]);
                $deleted[] = $newsletter_id;
            } else {
                $not_found[] = $newsletter_id;
            }
        }

        // Update the option in the database
        $updated = update_option('newsletter_list', $newsletter_list);

        // Confirmation messages based on success of update
        if ($updated) {
            if (!empty($deleted)) {
                echo '<div class="updated"><p>' . sprintf(__('%d newsletter(s) deleted successfully.', 'newsletter'), count($deleted)) . '</p></div>';
            }
            if (!empty($not_found)) {
                echo '<div class="error"><p>' . __('Some selected newsletters do not exist.', 'newsletter') . '</p></div>';
            }
        } else {
            echo '<div class="error"><p>' . __('Failed to delete the selected newsletters. Please try again.', 'newsletter') . '</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Delete Newsletter', 'newsletter'); ?></h1>
    <?php if (empty($newsletter_list)) : ?>
        <p><?php esc_html_e('No newsletters available for deletion.', 'newsletter'); ?></p>
    <?php else : ?>
        <form method="post">
            <?php
            // Add nonce field for security
            wp_nonce_field('delete_newsletter_action', 'newsletter_nonce');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Select Newsletter(s)', 'newsletter'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Select Newsletter(s)', 'newsletter'); ?></span></legend>
                            <?php foreach ($newsletter_list as $id => $name) : ?>
                                <label>
                                    <input type="checkbox" name="newsletter_ids[]" value="<?php echo esc_attr($id); ?>">
                                    <?php echo esc_html($name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description"><?php esc_html_e('Select one or more newsletters to delete.', 'newsletter'); ?></p>
                    </td>
                </tr>
            </table>
            <?php
            // Change the button text to indicate multiple deletions
            submit_button(__('Delete Selected Newsletter(s)', 'newsletter'), 'delete', 'delete_newsletter');
            ?>
        </form>
    <?php endif; ?>
</div>
