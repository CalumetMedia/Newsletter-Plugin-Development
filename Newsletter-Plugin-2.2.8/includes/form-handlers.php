<?php
if (!defined('ABSPATH')) exit;

/**
 * Handle Non-AJAX Form Submission for Blocks
 *
 * @param string $newsletter_slug The slug of the newsletter being edited.
 */
function newsletter_handle_blocks_form_submission_non_ajax($newsletter_slug) {
    // Debugging: Log function call
    error_log("Handler function called for slug: $newsletter_slug");

    // Verify nonce for security
    if (!isset($_POST['blocks_nonce']) || !wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
        error_log("Nonce verification failed for slug: $newsletter_slug");
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
        return;
    }

    // Process the blocks data
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    // Sanitize and save the blocks data
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
        $assigned_categories = array_map('intval', $_POST['assigned_categories']);
        update_option("newsletter_categories_$newsletter_slug", $assigned_categories);
    }

    // Update the template selection if it's part of the same form
    if (isset($_POST['selected_template_id'])) {
        $selected_template_id = sanitize_text_field($_POST['selected_template_id']);
        update_option("newsletter_template_id_$newsletter_slug", $selected_template_id);
    }

    // **New Code: Save Subject Line and Campaign Name**
    if (isset($_POST['subject_line'])) {
        $subject_line = sanitize_text_field($_POST['subject_line']);
        update_option("newsletter_subject_line_$newsletter_slug", $subject_line);
        error_log("Subject Line Saved: $subject_line");
    } else {
        error_log("Subject Line not set in POST data.");
    }

    if (isset($_POST['campaign_name'])) {
        $campaign_name = sanitize_text_field($_POST['campaign_name']);
        update_option("newsletter_campaign_name_$newsletter_slug", $campaign_name);
        error_log("Campaign Name Saved: $campaign_name");
    } else {
        error_log("Campaign Name not set in POST data.");
    }

    // Save custom header/footer HTML
    if (isset($_POST['custom_header'])) {
        update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post($_POST['custom_header']));
    }
    if (isset($_POST['custom_footer'])) {
        update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post($_POST['custom_footer']));
    }

    // **Optional: Add a success message indicating all fields have been saved**
    // The success message is handled via the redirect in newsletter-stories.php
}
?>
