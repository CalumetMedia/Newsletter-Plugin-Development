<?php
if (!defined('ABSPATH')) exit;

/**
 * Handle Non-AJAX Form Submission for Blocks
 *
 * @param string $newsletter_slug The slug of the newsletter being edited.
 */
function newsletter_handle_blocks_form_submission_non_ajax($newsletter_slug) {
    // Verify nonce for security
    if (!isset($_POST['blocks_nonce']) || !wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
        return;
    }

    if (isset($_POST['target_tags']) && is_array($_POST['target_tags'])) {
        $target_tags = array_map('sanitize_text_field', $_POST['target_tags']); 
        update_option("newsletter_target_tags_$newsletter_slug", $target_tags);
    }

    // Process the blocks data
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];
    $sanitized_blocks = [];
    foreach ($blocks as $block) {
        $sanitized_block = [
            'type'        => sanitize_text_field($block['type']), // 'content' or 'advertising'
            'category'    => isset($block['category']) ? intval($block['category']) : null,
            'title'       => sanitize_text_field($block['title']),
            'posts'       => isset($block['posts']) ? array_map('intval', $block['posts']) : [],
            'html'        => isset($block['html']) ? wp_kses_post($block['html']) : '',
            'template_id' => isset($block['template_id']) ? sanitize_text_field($block['template_id']) : 'default',
        ];
        $sanitized_blocks[] = $sanitized_block;
    }
    update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

    // Update assigned categories if provided
    if (isset($_POST['assigned_categories']) && is_array($_POST['assigned_categories'])) {
        update_option("newsletter_categories_$newsletter_slug", array_map('intval', $_POST['assigned_categories']));
    }

    // Update the template selection if it's part of the same form
    if (isset($_POST['selected_template_id'])) {
        update_option("newsletter_template_id_$newsletter_slug", sanitize_text_field($_POST['selected_template_id']));
    }

    // Save Subject Line and Campaign Name
    if (isset($_POST['subject_line'])) {
        update_option("newsletter_subject_line_$newsletter_slug", sanitize_text_field($_POST['subject_line']));
    }

    if (isset($_POST['campaign_name'])) {
        update_option("newsletter_campaign_name_$newsletter_slug", sanitize_text_field($_POST['campaign_name']));
    }

    // Save custom header/footer HTML
    if (isset($_POST['custom_header'])) {
        update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post($_POST['custom_header']));
    }
    if (isset($_POST['custom_footer'])) {
        update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post($_POST['custom_footer']));
    }

    // Optional: Add a success message or redirect handled elsewhere
}
