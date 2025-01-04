<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Admin {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('newsletter_admin_after_preview', [$this, 'render_pdf_controls']);
        add_action('wp_ajax_preview_newsletter_pdf', [$this, 'ajax_preview_pdf']);
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'newsletter-stories') === false) {
            return;
        }

        wp_enqueue_script(
            'newsletter-pdf-admin',
            NEWSLETTER_PLUGIN_URL . 'assets/js/pdf-admin.js',
            ['jquery'],
            NEWSLETTER_PLUGIN_VERSION,
            true
        );

        wp_localize_script('newsletter-pdf-admin', 'newsletterPDF', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('newsletter_pdf_preview'),
        ]);
    }

    public function render_pdf_controls() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="pdf-controls" style="margin: 20px 0;">
            <button type="button" class="button button-secondary preview-pdf">
                Preview PDF
            </button>
            <button type="button" class="button button-primary generate-pdf">
                Generate PDF
            </button>
            <span class="spinner" style="float: none; margin: 0 10px;"></span>
            <div class="pdf-status"></div>
        </div>
        <?php
    }

    public function ajax_preview_pdf() {
        check_ajax_referer('newsletter_pdf_preview', 'nonce');
        
        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : '';
        if (!$newsletter_slug) {
            wp_send_json_error('Invalid newsletter');
        }

        // Generate preview
        require_once plugin_dir_path(__FILE__) . 'views/pdf-preview.php';
        ob_start();
        render_pdf_preview($newsletter_slug);
        $preview = ob_get_clean();

        wp_send_json_success(['preview' => $preview]);
    }
}