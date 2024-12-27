<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Generator {
    private $template_id;
    private $newsletter_slug; 
    private $wkhtmltopdf_path = 'wkhtmltopdf'; // Can be configured in settings
    
    public function __construct($newsletter_slug, $template_id = null) {
        $this->newsletter_slug = $newsletter_slug;
        $this->template_id = $template_id ?: get_option("newsletter_pdf_template_id_$newsletter_slug", 'default');
    }
    
    public function generate($blocks) {
        // Get template content
        $templates = get_option('newsletter_templates', []);
        $template = isset($templates[$this->template_id]) ? $templates[$this->template_id] : null;
        
        if (!$template) {
            return new WP_Error('missing_template', 'PDF template not found');
        }

        // Generate HTML content
        $html_content = $this->generate_html_content($blocks, $template);
        
        // Create temporary file
        $temp_html = $this->create_temp_file($html_content, '.html');
        if (!$temp_html) {
            return new WP_Error('temp_file_error', 'Could not create temporary HTML file');
        }
        
        // Generate PDF
        $output_path = $this->get_output_path();
        $result = $this->generate_pdf($temp_html, $output_path);
        
        // Cleanup
        @unlink($temp_html);
        
        if (!$result) {
            return new WP_Error('pdf_generation_failed', 'PDF generation failed');
        }
        
        return $output_path;
    }
    
    private function generate_html_content($blocks, $template) {
        ob_start();
        
        // Include header/custom styles
        $custom_header = get_option("newsletter_custom_header_{$this->newsletter_slug}", '');
        $custom_css = get_option("newsletter_custom_css_{$this->newsletter_slug}", '');
        
        // Get newsletter content
        $newsletter_content = newsletter_generate_preview_content($this->newsletter_slug, $blocks);
        
        // Apply template
        $html = str_replace(
            ['{CUSTOM_HEADER}', '{CONTENT}', '{CUSTOM_CSS}'],
            [$custom_header, $newsletter_content, $custom_css],
            $template['html']
        );
        
        echo $html;
        return ob_get_clean();
    }
    
    private function generate_pdf($input_file, $output_file) {
        $cmd = sprintf(
            '%s --quiet --page-size Letter --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm %s %s',
            escapeshellcmd($this->wkhtmltopdf_path),
            escapeshellarg($input_file),
            escapeshellarg($output_file)
        );
        
        exec($cmd, $output, $return_var);
        return $return_var === 0;
    }
    
    private function create_temp_file($content, $extension) {
        $temp_file = tempnam(sys_get_temp_dir(), 'newsletter');
        if ($temp_file === false) {
            return false;
        }
        
        $temp_file_with_ext = $temp_file . $extension;
        if (!rename($temp_file, $temp_file_with_ext)) {
            @unlink($temp_file);
            return false;
        }
        
        if (file_put_contents($temp_file_with_ext, $content) === false) {
            @unlink($temp_file_with_ext);
            return false;
        }
        
        return $temp_file_with_ext;
    }
    
    private function get_output_path() {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/newsletter-pdfs';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        return $pdf_dir . '/' . $this->newsletter_slug . '-' . date('Y-m-d') . '.pdf';
    }
}