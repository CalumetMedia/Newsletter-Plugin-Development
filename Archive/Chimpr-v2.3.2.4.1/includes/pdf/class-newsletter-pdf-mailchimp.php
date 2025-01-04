<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Mailchimp {
    public function __construct() {
        add_filter('newsletter_campaign_content', [$this, 'add_pdf_link_to_campaign'], 10, 2);
        add_filter('newsletter_template_tags', [$this, 'add_pdf_template_tags']);
    }

    public function add_pdf_link_to_campaign($content, $newsletter_slug) {
        if (!newsletter_pdf_enabled($newsletter_slug)) {
            return $content;
        }

        $pdf_url = get_option("newsletter_current_pdf_url_$newsletter_slug");
        if (!$pdf_url) {
            return $content;
        }

        // Replace PDF link placeholder or add to bottom
        if (strpos($content, '{PDF_LINK}') !== false) {
            $content = str_replace('{PDF_LINK}', $this->get_pdf_link_html($pdf_url), $content);
        } else {
            $content .= $this->get_pdf_link_html($pdf_url);
        }

        return $content;
    }

    private function get_pdf_link_html($pdf_url) {
        return sprintf(
            '<div class="pdf-download" style="margin: 20px 0; padding: 15px; background: #f5f5f5; text-align: center;">
                <p style="margin-bottom: 10px;">Download this newsletter as PDF</p>
                <a href="%s" style="display: inline-block; padding: 10px 20px; background: #d65d23; color: #ffffff; text-decoration: none; border-radius: 3px;">Download PDF</a>
            </div>',
            esc_url($pdf_url)
        );
    }

    public function add_pdf_template_tags($tags) {
        $tags['{PDF_LINK}'] = 'PDF download link (if enabled)';
        return $tags;
    }
}