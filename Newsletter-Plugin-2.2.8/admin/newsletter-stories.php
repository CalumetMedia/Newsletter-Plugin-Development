<?php
// newsletter-stories.php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Include helper functions
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';

// Include form handlers
include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';

/**
 * Display Admin Notices Based on Query Parameters
 */
function newsletter_display_admin_notices()
{
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        $notices = [
            'blocks_saved'         => ['success', __('Blocks have been saved successfully.', 'newsletter')],
            'invalid_slug'         => ['error', __('Invalid newsletter slug.', 'newsletter')],
            'newsletter_not_found' => ['error', __('Newsletter not found.', 'newsletter')],
        ];

        if (isset($notices[$message])) {
            [$type, $text] = $notices[$message];
            echo "<div class='notice notice-{$type} is-dismissible'><p>{$text}</p></div>";
        }
    }
}
add_action('admin_notices', 'newsletter_display_admin_notices');

// Retrieve the list of newsletters
$newsletter_list = get_option('newsletter_list', []);

// If the newsletter list is empty, add a default newsletter
if (empty($newsletter_list)) {
    $newsletter_list['default'] = __('Default Newsletter', 'newsletter');
    update_option('newsletter_list', $newsletter_list);
}

// Ensure the default template exists
$default_template = '';
$template_path    = NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';

// Check if the template file exists
if (file_exists($template_path)) {
    ob_start();
    include $template_path;
    $default_template = ob_get_clean();
    update_option('newsletter_default_template', $default_template);
} else {
    // Handle the error or set a fallback template
    $default_template = '';
}

// Retrieve the current newsletter slug from URL parameters
$newsletter_slug = '';
if (isset($_GET['newsletter_slug'])) {
    $newsletter_slug = sanitize_text_field($_GET['newsletter_slug']);
} elseif (isset($_GET['page'])) {
    // Attempt to extract the slug from the 'page' parameter if possible
    $page_slug = sanitize_text_field($_GET['page']);
    $prefix    = 'newsletter-stories-';
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
    if (!wp_verify_nonce($_POST['blocks_nonce'], 'save_blocks_action')) {
        wp_die(__('Security check failed.', 'newsletter'));
    }
    // Handle the blocks form submission
    newsletter_handle_blocks_form_submission_non_ajax($newsletter_slug);

    // Save custom header and footer
    // Uncomment and adjust the following lines if you wish to handle custom header/footer
    // update_option("newsletter_custom_header_$newsletter_slug", wp_kses_post($_POST['custom_header']));
    // update_option("newsletter_custom_footer_$newsletter_slug", wp_kses_post($_POST['custom_footer']));
    // update_option("newsletter_enable_custom_header_$newsletter_slug", isset($_POST['enable_custom_header']) ? 1 : 0);
    // update_option("newsletter_enable_custom_footer_$newsletter_slug", isset($_POST['enable_custom_footer']) ? 1 : 0));


    // Redirect to avoid form resubmission
    wp_redirect(add_query_arg(['page' => 'newsletter-stories', 'newsletter_slug' => $newsletter_slug, 'message' => 'blocks_saved'], admin_url('admin.php')));
    exit;
}

// Retrieve assigned categories and blocks based on the slug
$assigned_categories = get_option("newsletter_categories_$newsletter_slug", []);
$blocks              = get_option("newsletter_blocks_$newsletter_slug", []);
$all_categories      = get_categories(['include' => $assigned_categories, 'hide_empty' => false]);

// Fetch available templates
$available_templates = get_option('newsletter_templates', []);
if (empty($available_templates)) {
    // If no templates are defined, set up a default template
    $available_templates['default'] = [
        'name'    => __('Default Template', 'newsletter'),
        'content' => $default_template,
    ];
}

// Enqueue admin JS
wp_enqueue_script('newsletter-admin-js', NEWSLETTER_PLUGIN_URL . 'admin/js/newsletter-admin.js', ['jquery', 'jquery-ui-datepicker', 'jquery-ui-accordion', 'jquery-ui-sortable'], '1.0', true);

// Prepare categories data for JavaScript
$categories_data = [];
if (!empty($all_categories)) {
    foreach ($all_categories as $category) {
        $categories_data[] = [
            'term_id' => $category->term_id,
            'name'    => $category->name,
        ];
    }
}

// Prepare templates data for JavaScript
$templates_data = [];
if (!empty($available_templates)) {
    foreach ($available_templates as $template_id => $template) {
        $templates_data[] = [
            'id'   => $template_id,
            'name' => $template['name'],
        ];
    }
}

// Localize script with data
wp_localize_script('newsletter-admin-js', 'newsletterData', array(
    'ajaxUrl'               => admin_url('admin-ajax.php'),
    'nonceLoadPosts'       => wp_create_nonce('load_block_posts_nonce'),
    'nonceGeneratePreview' => wp_create_nonce('generate_preview_nonce'),
    'nonceSaveBlocks'      => wp_create_nonce('save_blocks_action'),
    'newsletterSlug'       => $newsletter_slug,
    'blockLabel'           => __('Block', 'newsletter'),
    'blockTitleLabel'      => __('Block Title:', 'newsletter'),
    'blockTypeLabel'       => __('Block Type:', 'newsletter'),
    'contentLabel'         => __('Content', 'newsletter'),
    'advertisingLabel'     => __('Advertising', 'newsletter'),
    'selectCategoryLabel'  => __('Select Category:', 'newsletter'),
    'selectCategoryOption' => __('-- Select Category --', 'newsletter'),
    'selectCategoryPrompt' => __('Please select a category to display posts.', 'newsletter'),
    'advertisingHtmlLabel' => __('Advertising HTML:', 'newsletter'),
    'removeBlockLabel'     => __('Remove Block', 'newsletter'),
    'categories'           => $categories_data,
    'availableTemplates'   => $templates_data, // Pass templates as array of objects
    'templateLabel'        => __('Template:', 'newsletter'),
    'nonceMailchimp'       => wp_create_nonce('mailchimp_campaign_nonce'),
));

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
                <!-- Hidden Field for Newsletter Slug -->
                <input type="hidden" name="newsletter_slug" value="<?php echo esc_attr($newsletter_slug); ?>">

                <!-- Date Range Selection -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Date Range', 'newsletter'); ?></h2>
                    <label for="start_date"><?php esc_html_e('Start Date:', 'newsletter'); ?></label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? esc_attr($_POST['start_date']) : ''; ?>" />

                    <label for="end_date"><?php esc_html_e('End Date:', 'newsletter'); ?></label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? esc_attr($_POST['end_date']) : ''; ?>" />
                </div>

                <!-- Button Group: Moved between Date Range and Blocks -->
                <div class="button-group" style="display: flex; justify-content: flex-start; gap: 15px; margin: 20px 0;">
                    <button type="button" class="button button-large" id="add-block" style="padding: 10px 20px; font-size: 16px;">
                        <?php esc_html_e('Add Block', 'newsletter'); ?>
                    </button>
                    <button type="submit" class="button button-primary button-large" id="save-blocks" style="padding: 10px 20px; font-size: 16px;">
                        <?php esc_html_e('Save', 'newsletter'); ?>
                    </button>
                    <?php if (get_option('mailchimp_api_key')) : ?>
                        <button type="button" class="button button-secondary button-large" id="send-to-mailchimp" style="padding: 10px 20px; font-size: 16px;">
                            <img src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/images/mailchimp-logo.webp'); ?>"
                                 alt="<?php esc_attr_e('Mailchimp', 'newsletter'); ?>"
                                 style="height: 15px; vertical-align: middle; margin-right: 5px;">
                            <?php esc_html_e('Send to Mailchimp', 'newsletter'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- **New Section: Subject Line and Campaign Name** -->
<div class="settings-box" style="margin-bottom: 20px;">
    <h2><?php esc_html_e('Mailchimp Settings', 'newsletter'); ?></h2>
    <p>
        <label for="subject_line"><?php esc_html_e('Subject Line:', 'newsletter'); ?></label><br>
        <input type="text" id="subject_line" name="subject_line" value="<?php echo esc_attr(get_option("newsletter_subject_line_$newsletter_slug", '')); ?>" style="width:100%; padding: 8px; font-size: 14px;" />
    </p>
    <p>
        <label for="campaign_name"><?php esc_html_e('Campaign Name:', 'newsletter'); ?></label><br>
        <input type="text" id="campaign_name" name="campaign_name" value="<?php echo esc_attr(get_option("newsletter_campaign_name_$newsletter_slug", '')); ?>" style="width:100%; padding: 8px; font-size: 14px;" />
    </p>
</div>
                <!-- End of New Section -->

                <!-- Blocks Management -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Blocks', 'newsletter'); ?></h2>
                    <div id="blocks-container">
                        <?php
                        if (!empty($blocks)) {
                            foreach ($blocks as $index => $block) {
                                // Pass available templates to the block item
                                include NEWSLETTER_PLUGIN_DIR . 'admin/partials/block-item.php';
                            }
                        } else {
                            // Display a default block if none exist
                            $index = 0;
                            $block = [
                                'type'        => 'content',
                                'category'    => '',
                                'title'       => '',
                                'posts'       => [],
                                'html'        => '',
                                'template_id' => 'default',
                            ];
                            include NEWSLETTER_PLUGIN_DIR . 'admin/partials/block-item.php';
                        }
                        ?>
                    </div>
                </div>

                <!-- Custom Header/Footer HTML -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Custom Header/Footer HTML', 'newsletter'); ?></h2>
                    
                    <div class="custom-html-section">
                        <label for="custom_header"><?php esc_html_e('Custom Header HTML:', 'newsletter'); ?></label>
                        <textarea id="custom_header" name="custom_header" rows="5" style="width:100%;"><?php echo esc_textarea(get_option("newsletter_custom_header_$newsletter_slug", '')); ?></textarea>
                        
                        <label for="custom_footer" style="margin-top: 15px; display: block;"><?php esc_html_e('Custom Footer HTML:', 'newsletter'); ?></label>
                        <textarea id="custom_footer" name="custom_footer" rows="5" style="width:100%;"><?php echo esc_textarea(get_option("newsletter_custom_footer_$newsletter_slug", '')); ?></textarea>
                    </div>
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
                </div>
            </div>
        </div> <!-- End of Right Column -->
    </div> <!-- End of Flex Container -->
</div> <!-- End of Wrap -->
