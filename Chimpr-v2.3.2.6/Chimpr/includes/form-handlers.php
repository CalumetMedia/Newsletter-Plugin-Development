<?php
if (!defined('ABSPATH')) exit;

function newsletter_stories_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocks_nonce'])) {
        if (!wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
            wp_die(__('Security check failed.', 'newsletter'));
        }

        $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';

        // Handle blocks saving
        if (isset($_POST['blocks'])) {
            $blocks = $_POST['blocks'];
            $sanitized_blocks = [];
            $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);

            foreach ($blocks as $block) {
                $sanitized_block = [
                    'type' => isset($block['type']) ? sanitize_text_field($block['type']) : '',
                    'title' => isset($block['title']) ? sanitize_text_field($block['title']) : '',
                    'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : '0',
                    'show_title' => isset($block['show_title']) ? (int)$block['show_title'] : 1,
                    'date_range' => isset($block['date_range']) ? sanitize_text_field($block['date_range']) : '',
                    'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : '',
                    'manual_override' => isset($block['manual_override']) ? (int)$block['manual_override'] : 0,
                    'category' => isset($block['category']) ? sanitize_text_field($block['category']) : '',
                    'posts' => isset($block['posts']) ? $block['posts'] : []
                ];

                // Get existing block for content preservation
                $existing_block = null;
                if (!empty($existing_blocks)) {
                    foreach ($existing_blocks as $existing_block_item) {
                        if ($existing_block_item['type'] === $block['type'] &&
                            $existing_block_item['title'] === $block['title']) {
                            $existing_block = $existing_block_item;
                            break;
                        }
                    }
                }

                if (isset($block['type']) && $block['type'] === 'wysiwyg') {
                    $processed_block = handle_wysiwyg_content($block, $existing_block);
                    $sanitized_block['wysiwyg'] = isset($processed_block['wysiwyg']) ? $processed_block['wysiwyg'] : '';
                }

                $sanitized_blocks[] = $sanitized_block;
            }

            // Save with verification
            update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
            $verify_save = get_option("newsletter_blocks_$newsletter_slug");
            $actually_saved = !empty($verify_save) && serialize($verify_save) === serialize($sanitized_blocks);

            if (!$actually_saved) {
                wp_die('Failed to save newsletter blocks. Please try again.');
            }
        }

        // If generate PDF clicked
        if (isset($_POST['generate_pdf'])) {
            require_once NEWSLETTER_PLUGIN_DIR . 'includes/pdf/class-newsletter-pdf-generator.php';
            $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
            $pdf_generator = new Newsletter_PDF_Generator($newsletter_slug);
            $result = $pdf_generator->generate($blocks);

            if (is_wp_error($result)) {
                wp_redirect(add_query_arg([
                    'page'    => 'newsletter-stories-' . $newsletter_slug,
                    'message' => 'pdf_error'
                ], admin_url('admin.php')));
                exit;
            } else {
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
