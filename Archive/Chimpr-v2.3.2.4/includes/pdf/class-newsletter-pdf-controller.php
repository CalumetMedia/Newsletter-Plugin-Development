<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Controller {
    private $generator;

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_post_generate_newsletter_pdf', [$this, 'handle_pdf_generation']);
        add_action('newsletter_after_campaign_send', [$this, 'auto_generate_pdf']);
        add_filter('newsletter_templates_list', [$this, 'add_pdf_templates']);
    }

    public function init() {
        // Only load generator when needed
        if (isset($_GET['action']) && $_GET['action'] === 'generate_newsletter_pdf') {
            require_once plugin_dir_path(__FILE__) . 'pdf/class-newsletter-pdf-generator.php';
            $this->generator = new Newsletter_PDF_Generator();
        }
    }

    public function handle_pdf_generation() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $newsletter_slug = isset($_GET['newsletter_slug']) ? sanitize_text_field($_GET['newsletter_slug']) : '';
        if (!$newsletter_slug) {
            wp_die('Invalid newsletter');
        }

        // Get newsletter blocks
        $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        
        // Initialize generator with slug and template
        $this->generator = new Newsletter_PDF_Generator($newsletter_slug);
        
        // Generate PDF
        $pdf_path = $this->generator->generate($blocks);
        
        if (is_wp_error($pdf_path)) {
            wp_die($pdf_path->get_error_message());
        }

        // Store PDF path for newsletter
        update_option("newsletter_latest_pdf_$newsletter_slug", $pdf_path);

        // Send PDF download headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdf_path) . '"');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        exit;
    }

    public function auto_generate_pdf($newsletter_slug) {
        // Auto-generate PDF after campaign send if enabled
        if (get_option("newsletter_auto_pdf_$newsletter_slug", false)) {
            $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
            $this->generator = new Newsletter_PDF_Generator($newsletter_slug);
            $pdf_path = $this->generator->generate($blocks);
            
            if (!is_wp_error($pdf_path)) {
                update_option("newsletter_latest_pdf_$newsletter_slug", $pdf_path);
            }
        }
    }

    public function add_pdf_templates($templates) {
        // Add PDF-specific templates to template list
        $pdf_templates = get_option('newsletter_pdf_templates', []);
        foreach ($pdf_templates as $id => $template) {
            $templates[$id] = array_merge($template, ['type' => 'pdf']);
        }
        return $templates;
    }
}

// Initialize controller
new Newsletter_PDF_Controller();