<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Templates {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_template_menu']);
        add_action('admin_init', [$this, 'handle_template_actions']);
    }

    public function add_template_menu() {
        add_submenu_page(
            'newsletter-settings',
            'PDF Templates',
            'PDF Templates',
            'manage_options',
            'newsletter-pdf-templates',
            [$this, 'render_template_page']
        );
    }

    public function render_template_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $templates = get_option('newsletter_pdf_templates', []);
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        switch ($action) {
            case 'edit':
                $this->render_template_editor($_GET['template_id'] ?? '');
                break;
            case 'new':
                $this->render_template_editor();
                break;
            default:
                $this->render_template_list($templates);
                break;
        }
    }

    private function render_template_list($templates) {
        ?>
        <div class="wrap">
            <h1>PDF Templates
                <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-pdf-templates&action=new')); ?>" 
                   class="page-title-action">Add New</a>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $id => $template): ?>
                    <tr>
                        <td><?php echo esc_html($template['name']); ?></td>
                        <td><?php echo esc_html($template['description']); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url("admin.php?page=newsletter-pdf-templates&action=edit&template_id={$id}")); ?>"
                               class="button button-small">Edit</a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url("admin.php?page=newsletter-pdf-templates&action=delete&template_id={$id}"), 'delete_template')); ?>"
                               class="button button-small" 
                               onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_template_editor($template_id = '') {
        $templates = get_option('newsletter_pdf_templates', []);
        $template = isset($templates[$template_id]) ? $templates[$template_id] : [
            'name' => '',
            'description' => '',
            'html' => '',
            'css' => ''
        ];
        ?>
        <div class="wrap">
            <h1><?php echo $template_id ? 'Edit Template' : 'New Template'; ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_pdf_template'); ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="template_name">Template Name</label></th>
                        <td>
                            <input type="text" name="template_name" id="template_name" 
                                   value="<?php echo esc_attr($template['name']); ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="template_description">Description</label></th>
                        <td>
                            <textarea name="template_description" id="template_description" 
                                      class="large-text" rows="3"><?php echo esc_textarea($template['description']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="template_html">HTML Template</label></th>
                        <td>
                            <textarea name="template_html" id="template_html" 
                                      class="large-text code" rows="15"><?php echo esc_textarea($template['html']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="template_css">CSS Styles</label></th>
                        <td>
                            <textarea name="template_css" id="template_css" 
                                      class="large-text code" rows="10"><?php echo esc_textarea($template['css']); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function handle_template_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save_pdf_template')) {
            $this->save_template($_POST);
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' 
            && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_template')) {
            $this->delete_template($_GET['template_id']);
        }
    }

    private function save_template($data) {
        $templates = get_option('newsletter_pdf_templates', []);
        
        $template = [
            'name' => sanitize_text_field($data['template_name']),
            'description' => sanitize_textarea_field($data['template_description']),
            'html' => wp_kses_post($data['template_html']),
            'css' => wp_strip_all_tags($data['template_css'])
        ];

        if (empty($data['template_id'])) {
            $template_id = 'template_' . time();
        } else {
            $template_id = sanitize_key($data['template_id']);
        }

        $templates[$template_id] = $template;
        update_option('newsletter_pdf_templates', $templates);

        wp_redirect(admin_url('admin.php?page=newsletter-pdf-templates&updated=1'));
        exit;
    }

    private function delete_template($template_id) {
        $templates = get_option('newsletter_pdf_templates', []);
        unset($templates[$template_id]);
        update_option('newsletter_pdf_templates', $templates);

        wp_redirect(admin_url('admin.php?page=newsletter-pdf-templates&deleted=1'));
        exit;
    }
}