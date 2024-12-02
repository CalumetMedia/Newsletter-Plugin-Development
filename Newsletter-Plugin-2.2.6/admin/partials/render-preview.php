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
    // Extract the slug from multiple possible sources
    $slug = '';
    if (isset($_GET['slug'])) {
        $slug = sanitize_text_field($_GET['slug']);
    } elseif (isset($_GET['tab'])) {
        $slug = sanitize_text_field($_GET['tab']);
    } elseif (isset($_GET['page'])) {
        $page = sanitize_text_field($_GET['page']);
        if (strpos($page, 'newsletter-stories-') === 0) {
            $slug = str_replace('newsletter-stories-', '', $page);
        }
    }

    // Retrieve the list of valid slugs from the newsletter_list option
    $newsletter_list = get_option('newsletter_list', []);

    // Ensure newsletter_list is an array
    if (!is_array($newsletter_list)) {
        error_log("newsletter_list option is not an array. Re-initializing with 'default'.");
        $newsletter_list = ['default' => 'Default Newsletter'];
        update_option('newsletter_list', $newsletter_list);
    }

    // If no slug is provided or invalid, default to first available or 'default'
    if (empty($slug) || !array_key_exists($slug, $newsletter_list)) {
        if (!empty($newsletter_list)) {
            $slug = array_key_first($newsletter_list);
        } else {
            $slug = 'default';
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
    $blocks_option = "newsletter_blocks_$slug";
    $blocks = get_option($blocks_option, []);

    // Ensure blocks is an array and initialize if empty
    if (!is_array($blocks)) {
        error_log("Blocks for slug '$slug' are not an array. Initializing as empty array.");
        $blocks = [];
        update_option($blocks_option, $blocks);
    }

    return $blocks;
}

// Get the validated newsletter slug
$newsletter_slug = get_valid_newsletter_slug();

// Get the template ID, defaulting to 'default' if not set
$template_id = get_option("newsletter_template_id_$newsletter_slug", 'default');

// Get the blocks associated with the validated slug
$blocks = get_newsletter_blocks_by_slug($newsletter_slug);

// Only attempt to generate preview if we have a valid template
if (!empty($template_id)) {
    // Generate the preview using helper function
    $preview_html = newsletter_generate_preview_content($newsletter_slug, $template_id, $blocks);

    // Output the generated preview
    if (!empty($preview_html)) {
        echo $preview_html;
    } else {
        echo '<p class="error">' . esc_html__('Unable to generate preview content.', 'newsletter') . '</p>';
    }
} else {
    echo '<p class="error">' . esc_html__('No template selected for this newsletter.', 'newsletter') . '</p>';
}
?>