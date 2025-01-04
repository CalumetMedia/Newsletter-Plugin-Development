<?php
if (!defined('ABSPATH')) exit;

/**
 * Helper functions for PDF generation
 */

function newsletter_get_pdf_url($newsletter_slug) {
    $pdf_path = get_option("newsletter_latest_pdf_$newsletter_slug");
    if (!$pdf_path) {
        return false;
    }

    $upload_dir = wp_upload_dir();
    $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $pdf_path);
    return $pdf_url;
}

function newsletter_pdf_enabled($newsletter_slug) {
    return get_option("newsletter_opt_into_pdf_$newsletter_slug", false);
}

function newsletter_generate_pdf_filename($newsletter_slug) {
    $newsletter_name = get_option("newsletter_name_$newsletter_slug", '');
    $safe_name = sanitize_file_name($newsletter_name);
    return $safe_name . '-' . date('Y-m-d') . '.pdf';
}

function newsletter_get_pdf_generation_status($newsletter_slug) {
    return get_option("newsletter_pdf_status_$newsletter_slug", 'none');
}

function newsletter_set_pdf_generation_status($newsletter_slug, $status) {
    update_option("newsletter_pdf_status_$newsletter_slug", $status);
}

function newsletter_cleanup_old_pdfs() {
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/newsletter-pdfs';
    
    if (!is_dir($pdf_dir)) {
        return;
    }

    // Delete PDFs older than 30 days
    $files = glob($pdf_dir . '/*.pdf');
    $now = time();
    
    foreach ($files as $file) {
        if ($now - filemtime($file) >= 30 * 24 * 60 * 60) {
            @unlink($file);
        }
    }
}