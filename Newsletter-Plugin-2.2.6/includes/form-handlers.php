<?php
if (!defined('ABSPATH')) exit;

/**
 * Handle Form Submission for Blocks
 *
 * @param string $newsletter_slug The slug of the newsletter being edited.
 */
function newsletter_handle_blocks_form_submission($newsletter_slug) {
    // Verify nonce for security
    if (!isset($_POST['blocks_nonce']) || !wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
        return;
    }

    // Process the blocks data
    $blocks = isset($_POST['blocks']) ? $_POST['blocks'] : [];

    // Sanitize and save the blocks data
    $sanitized_blocks = [];
    foreach ($blocks as $block) {
        $sanitized_block = [
            'type'     => sanitize_text_field($block['type']), // 'content' or 'advertising'
            'category' => isset($block['category']) ? intval($block['category']) : null,
            'title'    => sanitize_text_field($block['title']),
            'posts'    => isset($block['posts']) ? array_map('intval', $block['posts']) : [],
            'html'     => isset($block['html']) ? wp_kses_post($block['html']) : '',
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

    echo '<div class="updated"><p>' . esc_html__('Blocks saved successfully.', 'newsletter') . '</p></div>';
}
?>
