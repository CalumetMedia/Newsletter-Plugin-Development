<?php
// admin/admin-page.php

if (!defined('ABSPATH')) exit; // Prevent direct access

// Get the newsletter_id from the URL
$newsletter_id = isset($_GET['newsletter_id']) ? intval($_GET['newsletter_id']) : 0;

// Validate the newsletter_id
if ($newsletter_id <= 0) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Invalid newsletter ID.', 'newsletter') . '</p></div>';
    return;
}

// Retrieve the newsletter post
$newsletter = get_post($newsletter_id);
if (!$newsletter || $newsletter->post_type !== 'newsletter') { // Replace 'newsletter' with your CPT slug
    echo '<div class="notice notice-error"><p>' . esc_html__('Newsletter not found.', 'newsletter') . '</p></div>';
    return;
}

// Retrieve existing blocks
$blocks = get_option("newsletter_blocks_$newsletter_id", []);

// Retrieve selected template
$template_id = get_option("newsletter_template_$newsletter_id", 0);
?>
<div class="wrap">
    <h1><?php echo esc_html($newsletter->post_title); ?></h1>

    <!-- Tabs Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#stories" class="nav-tab nav-tab-active"><?php esc_html_e('Stories', 'newsletter'); ?></a>
        <!-- Add other tabs if necessary -->
    </h2>

    <!-- Template Selection -->
    <div class="template-selection">
        <label for="template-selection"><?php esc_html_e('Select Template:', 'newsletter'); ?></label>
        <select id="template-selection" name="template_id">
            <option value=""><?php esc_html_e('-- Select Template --', 'newsletter'); ?></option>
            <?php
            $templates = get_posts(['post_type' => 'newsletter_template', 'numberposts' => -1]);
            foreach ($templates as $template) {
                $selected = selected($template_id, $template->ID, false);
                echo '<option value="' . esc_attr($template->ID) . '" ' . $selected . '>' . esc_html($template->post_title) . '</option>';
            }
            ?>
        </select>
    </div>

    <!-- Blocks Container -->
    <form id="blocks-form">
        <!-- Hidden field for newsletter_id -->
        <input type="hidden" name="newsletter_id" value="<?php echo esc_attr($newsletter_id); ?>" />

        <div id="blocks-container">
            <?php
            if (!empty($blocks)) {
                foreach ($blocks as $index => $block) {
                    include NEWSLETTER_PLUGIN_DIR . 'templates/block-item.php';
                }
            }
            ?>
        </div>
        <button type="button" class="button button-primary" id="add-block"><?php esc_html_e('Add Block', 'newsletter'); ?></button>
    </form>

    <!-- Save Button -->
    <button type="button" class="button button-primary" id="save-blocks"><?php esc_html_e('Save', 'newsletter'); ?></button>

    <!-- Preview Section -->
    <div id="story-preview">
        <h2><?php esc_html_e('Preview', 'newsletter'); ?></h2>
        <div id="preview-content">
            <!-- Preview content will be loaded here via AJAX -->
        </div>
        <img id="phone-overlay" src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'images/phone-overlay.png'); ?>" alt="<?php esc_attr_e('Phone Overlay', 'newsletter'); ?>">
    </div>
</div>
