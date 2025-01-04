<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Prevent unauthorized access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$editing_template = false;
$template_index = null; // Store the index of the template being edited

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_template']) && check_admin_referer('newsletter_add_template', 'newsletter_template_nonce')) {
        // Add new template 
        $templates = get_option('newsletter_templates', []);
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = wp_kses_post($_POST['newsletter_template_html']);
        $templates[] = [
            'name' => $template_name,
            'html' => $template_html,
        ];
        update_option('newsletter_templates', $templates);
        $template_index = count($templates) - 1; // Get the index of the newly added template
        $editing_template = true; // Switch to edit mode
    } elseif (isset($_POST['update_template']) && check_admin_referer('newsletter_update_template', 'newsletter_template_nonce')) {
        // Update existing template
        $templates = get_option('newsletter_templates', []);
        $template_index = intval($_POST['template_index']);
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = wp_kses_post($_POST['newsletter_template_html']);
        if (isset($templates[$template_index])) {
            $templates[$template_index] = [
                'name' => $template_name,
                'html' => $template_html,
            ];
            update_option('newsletter_templates', $templates);
            echo '<div class="updated"><p>Template updated successfully.</p></div>';
            $editing_template = true; // Stay in edit mode 
        }
    } elseif (isset($_POST['edit_template']) && check_admin_referer('newsletter_edit_template', 'newsletter_template_nonce')) {
        $editing_template = true;
        $template_index = intval($_POST['template_index']); 
    } elseif (isset($_POST['delete_template']) && check_admin_referer('newsletter_delete_template', 'newsletter_template_nonce')) {
        // Delete template 
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

    <div style="display: flex;">
        <div style="width: 50%; padding-right: 20px;">
            <h2><?php echo $editing_template ? esc_html_e('Edit Template', 'newsletter') : esc_html_e('Add New Template', 'newsletter'); ?></h2>
            <form method="post">
                <?php 
                if ($editing_template) {
                    wp_nonce_field('newsletter_update_template', 'newsletter_template_nonce'); 
                    echo '<input type="hidden" name="template_index" value="' . intval($template_index) . '">';
                } else {
                    wp_nonce_field('newsletter_add_template', 'newsletter_template_nonce'); 
                }
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="template_name"><?php esc_html_e('Template Name', 'newsletter'); ?></label></th>
                        <td>
                            <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php echo $editing_template ? esc_attr($templates[$template_index]['name']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="newsletter_template_html"><?php esc_html_e('Template HTML', 'newsletter'); ?></label></th>
                        <td>
                            <textarea name="newsletter_template_html" id="newsletter_template_html" rows="10" class="large-text code"><?php echo $editing_template ? esc_textarea($templates[$template_index]['html']) : ''; ?></textarea>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="<?php echo $editing_template ? 'update_template' : 'add_template'; ?>" id="submit" class="button button-primary" value="<?php echo $editing_template ? esc_attr_e('Update Template', 'newsletter') : esc_attr_e('Add Template', 'newsletter'); ?>">
                </p>
                <?php if ($editing_template): ?> 
                    <p class="submit">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-templates')); ?>" class="button"><?php esc_html_e('Add New Template', 'newsletter'); ?></a>
                    </p>
                <?php endif; ?>
            </form>
        </div>

        <div style="width: 50%;">
            <h2><?php esc_html_e('Existing Templates', 'newsletter'); ?></h2> 
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
        </div>
    </div>

    <div class="template-placeholders">
        <h3><?php esc_html_e('Available Placeholders', 'newsletter'); ?></h3>
        <ul>
            <li><code>{title}</code> - <?php esc_html_e('Post title', 'newsletter'); ?></li>
            <li><code>{excerpt}</code> - <?php esc_html_e('Post excerpt', 'newsletter'); ?></li>
            <li><code>{content}</code> - <?php esc_html_e('Full post content', 'newsletter'); ?></li>
            <li><code>{permalink}</code> - <?php esc_html_e('URL of the post', 'newsletter'); ?></li>
            <li><code>{thumbnail_url}</code> - <?php esc_html_e('URL of the featured image', 'newsletter'); ?></li>
            <li><code>{if_thumbnail}</code>...<code>{/if_thumbnail}</code> - <?php esc_html_e('Conditional content if thumbnail exists', 'newsletter'); ?></li>
            <li><code>{author}</code> - <?php esc_html_e('Post author name', 'newsletter'); ?></li>
            <li><code>{author_email}</code> - <?php esc_html_e('Post author email', 'newsletter'); ?></li>
            <li><code>{date}</code> - <?php esc_html_e('Post date', 'newsletter'); ?></li> 
            <li><code>{categories}</code> - <?php esc_html_e('Post categories', 'newsletter'); ?></li>
            <li><code>{tags}</code> - <?php esc_html_e('Post tags', 'newsletter'); ?></li>
            <li><code>{comments_number}</code> - <?php esc_html_e('Number of comments', 'newsletter'); ?></li> 
        </ul>
    </div>
</div>