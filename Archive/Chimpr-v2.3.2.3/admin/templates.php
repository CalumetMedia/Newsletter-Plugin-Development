<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Prevent unauthorized access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Allowed template types
$template_types = [
    'header' => __('Header Template', 'newsletter'),
    'footer' => __('Footer Template', 'newsletter'),
    'block'  => __('Block Template', 'newsletter'),
    'pdf'    => __('PDF Template', 'newsletter'),
];

$templates = get_option('newsletter_templates', []);

// Determine current action (list, new, edit)
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';

function render_template_list($templates, $template_types) {
    // Group templates by type
    $grouped_templates = [
        'block'  => [],
        'header' => [],
        'footer' => [],
        'pdf'    => [],
    ];

    foreach ($templates as $id => $template) {
        $type = isset($template['type']) ? $template['type'] : 'block';
        if (!isset($grouped_templates[$type])) {
            $grouped_templates[$type] = [];
        }
        $grouped_templates[$type][$id] = $template;
    }
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e('Templates', 'newsletter'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-templates&action=new')); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'newsletter'); ?>
            </a>
        </h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="updated"><p><?php esc_html_e('Template saved successfully.', 'newsletter'); ?></p></div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="updated"><p><?php esc_html_e('Template deleted successfully.', 'newsletter'); ?></p></div>
        <?php endif; ?>

        <?php foreach ($template_types as $type_key => $type_label): ?>
            <h2><?php echo esc_html($type_label); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Type', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Actions', 'newsletter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($grouped_templates[$type_key])): ?>
                    <?php foreach ($grouped_templates[$type_key] as $id => $template): ?>
                        <tr>
                            <td><?php echo esc_html($template['name']); ?></td>
                            <td><?php echo isset($template_types[$type_key]) ? esc_html($template_types[$type_key]) : esc_html($type_key); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url("admin.php?page=newsletter-templates&action=edit&template_id={$id}")); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'newsletter'); ?>
                                </a>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=delete_template')); ?>" style="display:inline;">
                                    <?php wp_nonce_field('delete_template', '_wpnonce_delete'); ?>
                                    <input type="hidden" name="template_index" value="<?php echo intval($id); ?>">
                                    <input type="submit" name="delete_template" class="button button-small" 
                                           value="<?php esc_attr_e('Delete', 'newsletter'); ?>" 
                                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this template?', 'newsletter'); ?>');">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3"><?php esc_html_e('No templates found.', 'newsletter'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_template_form($template, $template_id, $template_types) {
    $is_edit = $template_id !== '';
    $action_title = $is_edit ? __('Edit Template', 'newsletter') : __('New Template', 'newsletter');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($action_title); ?></h1>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=save_template')); ?>">
            <?php wp_nonce_field('save_template'); ?>
            <?php if ($is_edit): ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th><label for="template_name"><?php esc_html_e('Template Name', 'newsletter'); ?></label></th>
                    <td><input type="text" name="template_name" id="template_name" class="regular-text" 
                               value="<?php echo esc_attr(isset($template['name']) ? $template['name'] : ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="template_type"><?php esc_html_e('Template Type', 'newsletter'); ?></label></th>
                    <td>
                        <select name="template_type" id="template_type">
                            <?php 
                            $current_type = isset($template['type']) ? $template['type'] : 'block';
                            foreach ($template_types as $type_key => $type_label): ?>
                                <option value="<?php echo esc_attr($type_key); ?>" 
                                        <?php selected($current_type, $type_key); ?>>
                                    <?php echo esc_html($type_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="template_html"><?php esc_html_e('Template HTML', 'newsletter'); ?></label></th>
                    <td>
                        <textarea name="template_html" id="template_html" class="large-text code" rows="10"><?php 
                            echo esc_textarea(isset($template['html']) ? $template['html'] : ''); 
                        ?></textarea>

                        <p class="description"><?php esc_html_e('Use the placeholders below as needed.', 'newsletter'); ?></p>
                        <p class="description">
                            <a href="https://developers.google.com/gmail/design/css" target="_blank">
                                <?php esc_html_e('Style Best Practices in Email', 'newsletter'); ?>
                            </a>
                        </p>
                        
                        <h3><?php esc_html_e('Available Placeholders:', 'newsletter'); ?></h3>
                        <ul>
                            <li><code>{thumbnail_url}</code> - <?php esc_html_e('URL of the featured image', 'newsletter'); ?></li>
                            <li><code>{title}</code> - <?php esc_html_e('Post title', 'newsletter'); ?></li>
                            <li><code>{excerpt}</code> - <?php esc_html_e('Post excerpt', 'newsletter'); ?></li>
                            <li><code>{permalink}</code> - <?php esc_html_e('URL of the post', 'newsletter'); ?></li>
                            <li><code>{stories_loop}</code>...<code>{/stories_loop}</code> - <?php esc_html_e('Wrap around multiple stories', 'newsletter'); ?></li>
                            <li><code>{content}</code> - <?php esc_html_e('Full post content', 'newsletter'); ?></li>
                            <li><code>{if_thumbnail}</code>...<code>{/if_thumbnail}</code> - <?php esc_html_e('Conditional content if thumbnail exists', 'newsletter'); ?></li>
                            <li><code>{author}</code> - <?php esc_html_e('Post author name', 'newsletter'); ?></li>
                            <li><code>{author_email}</code> - <?php esc_html_e('Post author email', 'newsletter'); ?></li>
                            <li><code>{date}</code> - <?php esc_html_e('Post date', 'newsletter'); ?></li>
                            <li><code>{categories}</code> - <?php esc_html_e('Post categories', 'newsletter'); ?></li>
                            <li><code>{tags}</code> - <?php esc_html_e('Post tags', 'newsletter'); ?></li>
                            <li><code>{comments_number}</code> - <?php esc_html_e('Number of comments', 'newsletter'); ?></li>
                            <li><code>{CONTENT}</code> - <?php esc_html_e('Main content area (for PDF templates)', 'newsletter'); ?></li>
                        </ul>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Route actions based on $_GET['action']
switch ($action) {
    case 'new':
        $template = [
            'name' => '',
            'type' => 'block',
            'html' => '',
        ];
        render_template_form($template, '', $template_types);
        break;

    case 'edit':
        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : '';
        if ($template_id !== '' && isset($templates[$template_id])) {
            $template = $templates[$template_id];
            if (isset($template['css'])) {
                unset($template['css']);
            }
            render_template_form($template, $template_id, $template_types);
        } else {
            wp_safe_redirect(admin_url('admin.php?page=newsletter-templates'));
            exit;
        }
        break;

    default:
        render_template_list($templates, $template_types);
        break;
}
