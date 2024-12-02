<?php
// newsletter-stories.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Include helper functions if not already included
if (!function_exists('get_newsletter_posts')) {
    include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';
}

/**
 * Display Admin Notices Based on Query Parameters
 */
function newsletter_display_admin_notices() {
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'blocks_saved':
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Blocks have been saved successfully.', 'newsletter') . '</p></div>';
                break;
            case 'invalid_slug':
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Invalid newsletter slug.', 'newsletter') . '</p></div>';
                break;
            case 'newsletter_not_found':
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Newsletter not found.', 'newsletter') . '</p></div>';
                break;
            default:
                break;
        }
    }
}
add_action('admin_notices', 'newsletter_display_admin_notices');

// Retrieve the list of newsletters
$newsletter_list = get_option('newsletter_list', []);

// If the newsletter list is empty, add a default newsletter
if (empty($newsletter_list)) {
    $newsletter_list['default'] = 'Default Newsletter';
    update_option('newsletter_list', $newsletter_list);
}

// Ensure the default template exists
$default_template = get_option('newsletter_default_template');
if (empty($default_template)) {
    $default_template = '<div class="post">
        <h3>{title}</h3>
        <img src="{thumbnail_url}" alt="{title}">
        <p>{content}</p>
    </div>';
    update_option('newsletter_default_template', $default_template);
}

// Retrieve the current newsletter slug from URL parameters
$newsletter_slug = '';
if (isset($_GET['newsletter_slug'])) {
    $newsletter_slug = sanitize_text_field($_GET['newsletter_slug']);
} elseif (isset($_GET['page'])) {
    // Attempt to extract the slug from the 'page' parameter if possible
    $page_slug = sanitize_text_field($_GET['page']);
    $prefix = 'newsletter-stories-';
    if (strpos($page_slug, $prefix) === 0) {
        $newsletter_slug = str_replace($prefix, '', $page_slug);
    }
}

// If no slug is provided, default to 'default'
if (empty($newsletter_slug)) {
    $newsletter_slug = 'default';
}

// Validate the newsletter slug
$newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : '';

if (empty($newsletter_name)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Newsletter not found.', 'newsletter') . '</p></div>';
    return;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocks_nonce'])) {
    newsletter_handle_blocks_form_submission($newsletter_slug);
}

// Retrieve assigned categories and blocks based on the slug
$assigned_categories = get_option("newsletter_categories_$newsletter_slug", []);
$blocks              = get_option("newsletter_blocks_$newsletter_slug", []);
$all_categories      = get_categories(['include' => $assigned_categories, 'hide_empty' => false]);

?>
<div class="wrap">
    <h1><?php echo esc_html(sprintf(__('Manage Stories for %s', 'newsletter'), $newsletter_name)); ?></h1>

    <div class="flex-container">
        <div class="left-column">
            <!-- Left Column Content -->
            <form method="post" id="blocks-form">
                <?php
                // Output nonce for form security
                wp_nonce_field('save_blocks_action', 'blocks_nonce');
                ?>

                <!-- Date Range Selection -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Date Range', 'newsletter'); ?></h2>
                    <label for="start_date"><?php esc_html_e('Start Date:', 'newsletter'); ?></label>
                    <input type="text" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? esc_attr($_POST['start_date']) : ''; ?>" />

                    <label for="end_date"><?php esc_html_e('End Date:', 'newsletter'); ?></label>
                    <input type="text" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? esc_attr($_POST['end_date']) : ''; ?>" />
                </div>

                <!-- Blocks Management -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Blocks', 'newsletter'); ?></h2>
                    <div id="blocks-container">
                        <?php
                        if (!empty($blocks)) {
                            foreach ($blocks as $index => $block) {
                                include NEWSLETTER_PLUGIN_DIR . 'admin/partials/block-item.php';
                            }
                        } else {
                            // Display a default block if none exist
                            $index = 0;
                            $block = [
                                'type'     => 'content',
                                'category' => '',
                                'title'    => '',
                                'posts'    => [],
                                'html'     => '',
                            ];
                            include NEWSLETTER_PLUGIN_DIR . 'admin/partials/block-item.php';
                        }
                        ?>
                    </div>

                    <button type="button" class="button" id="add-block"><?php esc_html_e('Add Block', 'newsletter'); ?></button>
                    <button type="submit" class="button button-primary" id="save-blocks"><?php esc_html_e('Save', 'newsletter'); ?></button>
                </div>
            </form>
        </div> <!-- End of Left Column -->

        <div class="right-column">
            <!-- Newsletter Preview -->
            <div class="settings-box">
                <h2><?php esc_html_e('Newsletter Preview', 'newsletter'); ?></h2>
                <div id="story-preview">
                    <div id="preview-content">
                        <?php
                        // Include the rendering partial for the preview
                        include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php';
                        ?>
                    </div>
                    <img id="phone-overlay" src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/images/iphone.png'); ?>" alt="<?php esc_attr_e('Phone Overlay', 'newsletter'); ?>">
                </div>
            </div>
        </div> <!-- End of Right Column -->
    </div> <!-- End of Flex Container -->
</div> <!-- End of Wrap -->
