<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Prevent unauthorized access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$editing_template = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_template']) && check_admin_referer('newsletter_add_template', 'newsletter_template_nonce')) {
        // Add new template
        $templates = get_option('newsletter_templates', []);
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = wp_kses_post($_POST['newsletter_template_html']);
        $template_css = wp_strip_all_tags($_POST['newsletter_template_css']);

        $templates[] = [
            'name' => $template_name,
            'html' => $template_html,
            'css' => $template_css,
        ];

        update_option('newsletter_templates', $templates);
        echo '<div class="updated"><p>Template added successfully.</p></div>';

    } elseif (isset($_POST['update_template']) && check_admin_referer('newsletter_update_template', 'newsletter_template_nonce')) {
        // Update existing template
        $templates = get_option('newsletter_templates', []);
        $template_index = intval($_POST['template_index']);
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = wp_kses_post($_POST['newsletter_template_html']);
        $template_css = wp_strip_all_tags($_POST['newsletter_template_css']);

        if (isset($templates[$template_index])) {
            $templates[$template_index] = [
                'name' => $template_name,
                'html' => $template_html,
                'css' => $template_css,
            ];

            update_option('newsletter_templates', $templates);
            echo '<div class="updated"><p>Template updated successfully.</p></div>';
        }

    } elseif (isset($_POST['edit_template']) && check_admin_referer('newsletter_edit_template', 'newsletter_template_nonce')) {
        $editing_template = true;
        $template_index = intval($_POST['template_index']);
    } elseif (isset($_POST['delete_template']) && check_admin_referer('newsletter_delete_template', 'newsletter_template_nonce')) {
        $templates = get_option('newsletter_templates', []);
        $template_index = intval($_POST['template_index']);

        if (isset($templates[$template_index])) {
            unset($templates[$template_index]);
            $templates = array_values($templates);
            update_option('newsletter_templates', $templates);
            echo '<div class="updated"><p>Template deleted successfully.</p></div>';
        }
    }
}

// Retrieve existing templates
$templates = get_option('newsletter_templates', []);
?>

<div class="wrap">
    <h1><?php esc_html_e('Manage Newsletter Templates', 'newsletter'); ?></h1>

    <div class="template-management">
        <!-- Template Editor -->
        <div class="template-editor">
            <?php
            if ($editing_template) {
                $templates = get_option('newsletter_templates', []);
                $template_index = intval($template_index);
                $template = $templates[$template_index];
                $form_action = 'update_template';
                $submit_label = __('Update Template', 'newsletter');
            } else {
                $template = ['name' => '', 'html' => '', 'css' => ''];
                $template_index = '';
                $form_action = 'add_template';
                $submit_label = __('Add Template', 'newsletter');
            }
            ?>
            <h2><?php echo esc_html($submit_label); ?></h2>
            <form method="post">
                <?php wp_nonce_field('newsletter_' . $form_action, 'newsletter_template_nonce'); ?>
                <input type="hidden" name="template_index" value="<?php echo esc_attr($template_index); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="template_name"><?php esc_html_e('Template Name', 'newsletter'); ?></label></th>
                        <td><input name="template_name" type="text" id="template_name" value="<?php echo esc_attr($template['name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="newsletter_template_html"><?php esc_html_e('Template HTML', 'newsletter'); ?></label></th>
                        <td><textarea name="newsletter_template_html" id="newsletter_template_html" rows="10" class="large-text code"><?php echo esc_textarea($template['html']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="newsletter_template_css"><?php esc_html_e('Template CSS', 'newsletter'); ?></label></th>
                        <td><textarea name="newsletter_template_css" id="newsletter_template_css" rows="5" class="large-text code"><?php echo esc_textarea($template['css']); ?></textarea></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="<?php echo esc_attr($form_action); ?>" id="submit" class="button button-primary" value="<?php echo esc_attr($submit_label); ?>">
                </p>
            </form>
        </div>

        <!-- Existing Templates List -->
        <div class="template-list">
            <h2><?php esc_html_e('Custom Templates', 'newsletter'); ?></h2>
            <?php if (!empty($templates)): ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Template Name', 'newsletter'); ?></th>
                            <th><?php esc_html_e('Actions', 'newsletter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $index => $template_item): ?>
                            <tr>
                                <td><?php echo esc_html($template_item['name']); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('newsletter_edit_template', 'newsletter_template_nonce'); ?>
                                        <input type="hidden" name="template_index" value="<?php echo intval($index); ?>">
                                        <input type="submit" name="edit_template" class="button button-primary" value="<?php esc_attr_e('Edit', 'newsletter'); ?>">
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('newsletter_delete_template', 'newsletter_template_nonce'); ?>
                                        <input type="hidden" name="template_index" value="<?php echo intval($index); ?>">
                                        <input type="submit" name="delete_template" class="button button-secondary" value="<?php esc_attr_e('Delete', 'newsletter'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this template?', 'newsletter'); ?>');">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php esc_html_e('No custom templates found.', 'newsletter'); ?></p>
            <?php endif; ?>

            <!-- Template Placeholders Documentation -->
            <div class="template-placeholders">
                <h3><?php esc_html_e('Available Placeholders', 'newsletter'); ?></h3>
                <ul>
                    <li><code>{title}</code> - <?php esc_html_e('Post title', 'newsletter'); ?></li>
                    <li><code>{excerpt}</code> - <?php esc_html_e('Post excerpt', 'newsletter'); ?></li>
                    <li><code>{content}</code> - <?php esc_html_e('Full post content', 'newsletter'); ?></li>
                    <li><code>{permalink}</code> - <?php esc_html_e('URL of the post', 'newsletter'); ?></li>
                    <li><code>{thumbnail_url}</code> - <?php esc_html_e('URL of the featured image', 'newsletter'); ?></li>
                    <li><code>{if_thumbnail}</code>...<code>{/if_thumbnail}</code> - <?php esc_html_e('Conditional content if thumbnail exists', 'newsletter'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>