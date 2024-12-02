<?php
// admin/partials/render-preview.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Retrieve and validate the newsletter slug.
 *
 * @return string Validated newsletter slug.
 */
function get_valid_newsletter_slug() {
    // Extract the slug from URL parameters
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';

    // If no slug is provided, you can choose to default to a specific newsletter or handle it accordingly
    if (empty($slug)) {
        error_log("No newsletter slug provided. Using 'default' as the fallback slug.");
        $slug = 'default';
    }

    // Retrieve the list of valid slugs from the newsletter_list option
    $newsletter_list = get_option('newsletter_list', []);

    // Ensure newsletter_list is an associative array
    if (!is_array($newsletter_list)) {
        error_log("newsletter_list option is not an array. Re-initializing with 'default'.");
        $newsletter_list = ['default' => 'Default Newsletter'];
        update_option('newsletter_list', $newsletter_list);
    }

    // Check if the extracted slug exists as a key in the newsletter_list
    if (!array_key_exists($slug, $newsletter_list)) {
        error_log("Invalid newsletter slug provided: '{$slug}'. Defaulting to 'default'.");
        $slug = 'default';

        // Optionally, you can check if 'default' exists, and if not, initialize it
        if (!array_key_exists('default', $newsletter_list)) {
            $newsletter_list['default'] = __('Default Newsletter', 'newsletter');
            update_option('newsletter_list', $newsletter_list);
            error_log("Added 'default' to newsletter_list.");
        }
    }

    return $slug;
}

/**
 * Retrieve the blocks for the given newsletter slug.
 *
 * @param string $slug The newsletter slug.
 * @return array The blocks associated with the newsletter.
 */
function get_newsletter_blocks_by_slug($slug) {
    // Retrieve blocks from the option. Adjust the option name if different.
    $blocks_option = 'newsletter_blocks_' . $slug;
    $blocks = get_option($blocks_option, []);

    // Ensure blocks is an array
    if (!is_array($blocks)) {
        error_log("Blocks for slug '{$slug}' are not an array. Initializing as empty array.");
        $blocks = [];
    }

    return $blocks;
}

// Get the validated newsletter slug
$newsletter_slug = get_valid_newsletter_slug();

// Get the blocks associated with the validated slug
$blocks = get_newsletter_blocks_by_slug($newsletter_slug);

// Generate the preview using the default template
$initial_preview = newsletter_generate_preview_content($newsletter_slug, 'default', $blocks);

// Output the generated preview
echo $initial_preview;
?>
