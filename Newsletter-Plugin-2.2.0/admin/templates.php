<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Prevent unauthorized access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$editing_template = false;
$default_template_index = 'default'; // Using 'default' as the index for clarity
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
        $template_index = $_POST['template_index'];
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = wp_kses_post($_POST['newsletter_template_html']);
        $template_css = wp_strip_all_tags($_POST['newsletter_template_css']);

        if ($template_index === 'default') {
            // Update default template
            $default_template = [
                'name' => 'Default Template',
                'html' => $template_html,
                'css' => $template_css,
            ];
            update_option('newsletter_default_template', $default_template);
        } else {
            // Update custom template
            $templates = get_option('newsletter_templates', []);
            $template_index = intval($template_index);

            if (isset($templates[$template_index])) {
                $templates[$template_index] = [
                    'name' => $template_name,
                    'html' => $template_html,
                    'css' => $template_css,
                ];

                update_option('newsletter_templates', $templates);
            }
        }

        echo '<div class="updated"><p>Template updated successfully.</p></div>';

    } elseif (isset($_POST['edit_template']) && check_admin_referer('newsletter_edit_template', 'newsletter_template_nonce')) {
        // Prepare to edit existing template
        $editing_template = true;
        $template_index = $_POST['template_index'];

    } elseif (isset($_POST['delete_template']) && check_admin_referer('newsletter_delete_template', 'newsletter_template_nonce')) {
        // Delete template
        $templates = get_option('newsletter_templates', []);
        $template_index = intval($_POST['template_index']);

        if (isset($templates[$template_index])) {
            unset($templates[$template_index]);
            $templates = array_values($templates); // Reindex array
            update_option('newsletter_templates', $templates);

            echo '<div class="updated"><p>Template deleted successfully.</p></div>';
        }
    }
}

// Retrieve existing templates
$templates = get_option('newsletter_templates', []);
$default_template = get_option('newsletter_default_template');

// Ensure there's a default template
if (empty($default_template)) {
    $default_template = [
        'name' => 'Default Template',
        'html' => '<html><body>{stories_loop}<h1>{title}</h1><p>{excerpt}</p>{/stories_loop}</body></html>',
        'css' => 'h1 { font-size: 24px; } p { font-size: 16px; }',
    ];
    update_option('newsletter_default_template', $default_template);
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Manage Newsletter Templates', 'newsletter'); ?></h1>

    <!-- Link to the external CSS file -->
    <link rel="stylesheet" type="text/css" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'css/newsletter-admin.css'); ?>">

    <div class="template-management">
        <!-- Template Editor -->
        <div class="template-editor">
            <?php
            // Check if editing a template
            if ($editing_template) {
                if ($template_index === 'default') {
                    $template = $default_template;
                } else {
                    $templates = get_option('newsletter_templates', []);
                    $template_index = intval($template_index);
                    $template = $templates[$template_index];
                }
                $form_action = 'update_template';
                $submit_label = __('Update Template', 'newsletter');
            } else {
                // Add new template
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
                    <?php if ($template_index !== 'default'): ?>
                    <tr>
                        <th scope="row"><label for="template_name"><?php esc_html_e('Template Name', 'newsletter'); ?></label></th>
                        <td><input name="template_name" type="text" id="template_name" value="<?php echo esc_attr($template['name']); ?>" class="regular-text"></td>
                    </tr>
                    <?php else: ?>
                        <input type="hidden" name="template_name" value="Default Template">
                    <?php endif; ?>
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
            <h2><?php esc_html_e('Templates', 'newsletter'); ?></h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Template Name', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Actions', 'newsletter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Default Template -->
                    <tr>
                        <td><strong><?php esc_html_e('Default Template', 'newsletter'); ?></strong></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('newsletter_edit_template', 'newsletter_template_nonce'); ?>
                                <input type="hidden" name="template_index" value="default">
                                <input type="submit" name="edit_template" class="button button-primary" value="<?php esc_attr_e('Edit', 'newsletter'); ?>">
                            </form>
                        </td>
                    </tr>
                    <!-- Custom Templates -->
                    <?php if (!empty($templates)): ?>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><?php esc_html_e('No custom templates found.', 'newsletter'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Placeholder Lexicon -->
            <div class="template-placeholders">
                <h3><?php esc_html_e('Available Placeholders', 'newsletter'); ?></h3>
                <!-- Placeholders list remains the same -->
                <h4><?php esc_html_e('WordPress Data', 'newsletter'); ?></h4>
                <ul>
                    <li><code>{title}</code> - <?php esc_html_e('Post title', 'newsletter'); ?></li>
                    <li><code>{excerpt}</code> - <?php esc_html_e('Post excerpt or first 50 words', 'newsletter'); ?></li>
                    <li><code>{content}</code> - <?php esc_html_e('Full post content', 'newsletter'); ?></li>
                    <li><code>{permalink}</code> - <?php esc_html_e('URL of the post', 'newsletter'); ?></li>
                    <li><code>{author}</code> - <?php esc_html_e('Post author', 'newsletter'); ?></li>
                    <li><code>{date}</code> - <?php esc_html_e('Publish date', 'newsletter'); ?></li>
                    <li><code>{thumbnail_url}</code> - <?php esc_html_e('URL of the featured image', 'newsletter'); ?></li>
                    <li><code>{category}</code> - <?php esc_html_e('Post category', 'newsletter'); ?></li>
                    <li><code>{tags}</code> - <?php esc_html_e('Post tags', 'newsletter'); ?></li>
                    <li><code>{comments_count}</code> - <?php esc_html_e('Number of comments', 'newsletter'); ?></li>
                    <li><code>{current_date}</code> - <?php esc_html_e('Current date', 'newsletter'); ?></li>
                </ul>
                <h4><?php esc_html_e('Mailchimp Data', 'newsletter'); ?></h4>
                <ul>
                    <li><code>*|FNAME|*</code> - <?php esc_html_e('Subscriber first name', 'newsletter'); ?></li>
                    <li><code>*|LNAME|*</code> - <?php esc_html_e('Subscriber last name', 'newsletter'); ?></li>
                    <li><code>*|EMAIL|*</code> - <?php esc_html_e('Subscriber email address', 'newsletter'); ?></li>
                    <li><code>*|CURRENT_YEAR|*</code> - <?php esc_html_e('Current year', 'newsletter'); ?></li>
                    <li><code>*|CURRENT_DATE|*</code> - <?php esc_html_e('Current date', 'newsletter'); ?></li>
                </ul>
                <h4><?php esc_html_e('Custom Tags', 'newsletter'); ?></h4>
                <ul>
                    <li><code>{stories_loop}</code>...<code>{/stories_loop}</code> - <?php esc_html_e('Wrap around the stories section', 'newsletter'); ?></li>
                    <li><code>{custom_intro}</code> - <?php esc_html_e('Custom introduction text', 'newsletter'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
