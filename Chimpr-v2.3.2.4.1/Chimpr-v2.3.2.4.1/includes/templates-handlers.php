<?php
if (!defined('ABSPATH')) exit;

function handle_template_save() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_template')) {
        wp_die(__('Security check failed.', 'chimpr-newsletter'));
    }

    $templates = get_option('newsletter_templates', []);
    $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
    $template_name = sanitize_text_field($_POST['template_name']);
    $template_type = isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : 'block';
    $template_html = wp_kses_post($_POST['template_html']);

    error_log('Template ID: ' . $template_id);
    error_log('Current templates: ' . print_r($templates, true));

    if ($template_id === '') {
        // New template
        $templates[] = [
            'name' => $template_name,
            'type' => $template_type,
            'html' => $template_html,
        ];
    } else {
        // Editing existing template
        $template_index = intval($template_id);
        $templates[$template_index] = [
            'name' => $template_name,
            'type' => $template_type,
            'html' => $template_html,
        ];
    }

    error_log('Updated templates: ' . print_r($templates, true));
    update_option('newsletter_templates', $templates);

    wp_safe_redirect(admin_url('admin.php?page=newsletter-templates&updated=1'));
    exit;
}

function handle_template_delete() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (!check_admin_referer('delete_template', '_wpnonce_delete')) {
        wp_die(__('Security check failed.', 'chimpr-newsletter'));
    }

    $templates = get_option('newsletter_templates', []);
    $template_index = intval($_POST['template_index']);
    
    if (isset($templates[$template_index])) {
        unset($templates[$template_index]);
        update_option('newsletter_templates', $templates);
    }

    wp_safe_redirect(admin_url('admin.php?page=newsletter-templates&deleted=1'));
    exit;
}

add_action('admin_post_save_template', 'handle_template_save');
add_action('admin_post_delete_template', 'handle_template_delete');