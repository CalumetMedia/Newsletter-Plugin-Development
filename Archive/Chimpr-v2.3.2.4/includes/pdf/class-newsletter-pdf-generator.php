<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Generator {
    private $template_id;
    private $newsletter_slug; 
    
    public function __construct($newsletter_slug, $template_id = null) {
        error_log("[PDF Generator] Initialized with newsletter slug: $newsletter_slug");

        $this->newsletter_slug = $newsletter_slug;
        $this->template_id = $template_id ?: get_option("newsletter_pdf_template_id_$newsletter_slug", 'default');

        error_log("[PDF Generator] Using template_id: " . $this->template_id);
    }
    
    public function generate($blocks) {
        error_log("[PDF Generator] Starting PDF generation process...");

        // Ensure TCPDF is available
        if (!class_exists('TCPDF')) {
            error_log("[PDF Generator] TCPDF class not found. Attempting to load TCPDF...");
            // Adjust this path as needed to match where TCPDF is stored
            // For example:
            // require_once(NEWSLETTER_PLUGIN_DIR . 'includes/lib/tcpdf_min/tcpdf.php');
            require_once ABSPATH . 'wp-content/plugins/your-plugin-path/includes/lib/tcpdf_min/tcpdf.php';
            if (!class_exists('TCPDF')) {
                error_log("[PDF Generator] Could not load TCPDF. Check the path to tcpdf.php.");
                return new WP_Error('tcpdf_missing', 'TCPDF not found or failed to load.');
            } else {
                error_log("[PDF Generator] TCPDF successfully loaded after manual require_once.");
            }
        } else {
            error_log("[PDF Generator] TCPDF class already available.");
        }

        // Get templates
        $templates = get_option('newsletter_templates', []);
        if (empty($templates)) {
            error_log("[PDF Generator] No templates found in newsletter_templates option.");
        } else {
            error_log("[PDF Generator] Templates retrieved. Keys: " . implode(', ', array_keys($templates)));
        }

        $template = isset($templates[$this->template_id]) ? $templates[$this->template_id] : null;
        
        if (!$template) {
            error_log("[PDF Generator] PDF template (ID: {$this->template_id}) not found.");
            return new WP_Error('missing_template', 'PDF template not found');
        }

        if (!isset($template['content'])) {
            error_log("[PDF Generator] Template found but 'content' key missing. Template data: " . print_r($template, true));
            return new WP_Error('invalid_template_format', 'Template does not have a content key.');
        }

        // Generate HTML content
        error_log("[PDF Generator] Generating HTML content...");
        $html_content = $this->generate_html_content($blocks, $template);
        error_log("[PDF Generator] HTML content length: " . strlen($html_content));

        // Generate output path
        $output_path = $this->get_output_path();
        error_log("[PDF Generator] Output path set to: " . $output_path);

        try {
            // Create new PDF document
            error_log("[PDF Generator] Creating TCPDF instance...");
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            error_log("[PDF Generator] TCPDF instance created.");

            // Set document information
            $pdf->SetCreator('Newsletter Plugin');
            $pdf->SetAuthor('Newsletter Plugin');
            $pdf->SetTitle($this->newsletter_slug . ' Newsletter');

            // Remove default headers/footers
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins and auto page break
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);

            // Add a page
            $pdf->AddPage();
            error_log("[PDF Generator] Page added to the PDF.");

            // Set font
            $pdf->SetFont('helvetica', '', 10);
            error_log("[PDF Generator] Font set to helvetica.");

            // Write HTML content
            $pdf->writeHTML($html_content, true, false, true, false, '');
            error_log("[PDF Generator] HTML content written to PDF.");

            // Save PDF
            $pdf->Output($output_path, 'F');
            error_log("[PDF Generator] PDF output attempted at: " . $output_path);

            if (file_exists($output_path)) {
                error_log("[PDF Generator] PDF file successfully created. File size: " . filesize($output_path));
                return $output_path;
            } else {
                error_log("[PDF Generator] PDF file not found after generation attempt.");
                return new WP_Error('pdf_not_found', 'PDF file was not created');
            }
            
        } catch (Exception $e) {
            error_log("[PDF Generator] PDF generation failed with exception: " . $e->getMessage());
            error_log("[PDF Generator] Exception trace: " . $e->getTraceAsString());
            return new WP_Error('pdf_generation_failed', 'PDF generation failed: ' . $e->getMessage());
        }
    }
    
    private function generate_html_content($blocks, $template) {
        error_log("[PDF Generator] Entering generate_html_content...");
        // We'll add logging for the content generation steps
        ob_start();

        $custom_header = get_option("newsletter_custom_header_{$this->newsletter_slug}", '');
        $custom_css    = get_option("newsletter_custom_css_{$this->newsletter_slug}", '');
        error_log("[PDF Generator] Custom header length: " . strlen($custom_header));
        error_log("[PDF Generator] Custom CSS length: " . strlen($custom_css));

        // Generate newsletter content
        // Make sure newsletter_generate_preview_content is defined and functioning
        $newsletter_content = newsletter_generate_preview_content($this->newsletter_slug, $blocks);
        if (empty($newsletter_content)) {
            error_log("[PDF Generator] newsletter_generate_preview_content returned empty content.");
        } else {
            error_log("[PDF Generator] newsletter_generate_preview_content returned content of length " . strlen($newsletter_content));
        }

        $html = str_replace(
            ['{CUSTOM_HEADER}', '{CONTENT}', '{CUSTOM_CSS}'],
            [$custom_header, $newsletter_content, $custom_css],
            $template['content']
        );

        // Add basic PDF styling
        $style = "
            <style>
                body { font-family: helvetica; font-size: 10pt; }
                h1 { font-size: 18pt; }
                h2 { font-size: 14pt; }
                h3 { font-size: 12pt; }
                $custom_css
            </style>
        ";

        $html = $style . $html;
        echo $html;

        $content = ob_get_clean();
        error_log("[PDF Generator] Final HTML length: " . strlen($content));
        return $content;
    }
    
    private function get_output_path() {
        $upload_dir = wp_upload_dir();
        $secure_dir = $upload_dir['basedir'] . '/secure';

        if (!file_exists($secure_dir)) {
            error_log("[PDF Generator] Secure directory not found, attempting to create: " . $secure_dir);
            $created = wp_mkdir_p($secure_dir);
            error_log("[PDF Generator] Directory created: " . ($created ? 'yes' : 'no'));
        }

        $filename = $this->newsletter_slug . '-' . date('Y-m-d') . '.pdf';
        $output_path = $secure_dir . '/' . $filename;
        error_log("[PDF Generator] PDF output path will be: " . $output_path);

        return $output_path;
    }
}
