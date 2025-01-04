<?php
if (!defined('ABSPATH')) exit;

function newsletter_stories_handle_form_submission() {
    error_log("Form handler called");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocks_nonce'])) {
        error_log("POST method confirmed");
        error_log("POST data in handler: " . print_r($_POST, true));

        if (!wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
            error_log("Nonce verification failed");
            wp_die(__('Security check failed.', 'newsletter'));
        }

        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
        error_log("Newsletter slug: $newsletter_slug");

        // Handle blocks saving
        if (isset($_POST['blocks'])) {
            $blocks = wp_unslash($_POST['blocks']);
            foreach ($blocks as $key => $block) {
                if (isset($block['type']) && $block['type'] === 'wysiwyg') {
                    $allowed_html = wp_kses_allowed_html('post');
                    $allowed_html['div'] = array('class' => true, 'style' => true);
                    $allowed_html['span'] = array('class' => true, 'style' => true);
                    $blocks[$key]['wysiwyg'] = wp_kses($block['wysiwyg'], $allowed_html);
                }
            }
            update_option("newsletter_blocks_$newsletter_slug", $blocks);
        }

        // If generate PDF clicked
        if (isset($_POST['generate_pdf'])) {
            error_log("Generate PDF triggered");
            require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-generator.php';

            $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
            error_log("Blocks retrieved: " . print_r($blocks, true));

            $pdf_generator = new Newsletter_PDF_Generator($newsletter_slug);
            error_log("PDF Generator instantiated");

            $result = $pdf_generator->generate($blocks);
            error_log("PDF Generation result: " . print_r($result, true));

            if (is_wp_error($result)) {
                error_log("PDF Generation error: " . $result->get_error_message());
                wp_redirect(add_query_arg([
                    'page'    => 'newsletter-stories-' . $newsletter_slug,
                    'message' => 'pdf_error'
                ], admin_url('admin.php')));
                exit;
            } else {
                error_log("PDF Generated successfully at: $result");
                $pdf_url = str_replace(ABSPATH, site_url('/'), $result);
                wp_redirect(add_query_arg([
                    'page'    => 'newsletter-stories-' . $newsletter_slug,
                    'message' => 'pdf_generated',
                    'pdf_url' => urlencode($pdf_url)
                ], admin_url('admin.php')));
                exit;
            }
        }

        // Redirect with blocks saved message if no PDF generation
        wp_redirect(add_query_arg([
            'page'    => 'newsletter-stories-' . $newsletter_slug,
            'message' => 'blocks_saved'
        ], admin_url('admin.php')));
        exit;
    }
}
