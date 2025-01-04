<?php
// newsletter-stories.php

if (!defined('ABSPATH')) {
    exit;
}

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Newsletter stories page loaded");

// Include helper functions and form handlers early
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/form-handlers.php';

// Hook form submission handler
add_action('admin_post_newsletter_stories_form_submission', 'newsletter_stories_handle_form_submission');

// Display admin notices
function newsletter_display_admin_notices() {
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        $notices = [
            'blocks_saved'         => ['success', __('Blocks have been saved successfully.', 'newsletter')],
            'invalid_slug'         => ['error', __('Invalid newsletter slug.', 'newsletter')],
            'newsletter_not_found' => ['error', __('Newsletter not found.', 'newsletter')],
            'pdf_generated'        => ['success', __('PDF generated successfully!', 'newsletter')],
            'pdf_error'            => ['error', __('PDF generation failed.', 'newsletter')],
        ];

        if (isset($notices[$message])) {
            [$type, $text] = $notices[$message];
            echo "<div class='notice notice-{$type} is-dismissible'><p>{$text}";
            if ($message === 'pdf_generated' && isset($_GET['pdf_url'])) {
                $pdf_url = esc_url($_GET['pdf_url']);
                echo ' <a href="' . $pdf_url . '" target="_blank">' . __('View PDF', 'newsletter') . '</a>';
            }
            echo "</p></div>";
        }
    }
}
add_action('admin_notices', 'newsletter_display_admin_notices');

// Retrieve the list of newsletters
$newsletter_list = get_option('newsletter_list', []);

// If empty, add default
if (empty($newsletter_list)) {
    $newsletter_list['default'] = __('Default Newsletter', 'newsletter');
    update_option('newsletter_list', $newsletter_list);
}

// Ensure default template exists
$default_template = '';
$template_path    = NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';

if (file_exists($template_path)) {
    ob_start();
    include $template_path;
    $default_template = ob_get_clean();
    update_option('newsletter_default_template', $default_template);
} else {
    $default_template = '';
}

// Determine newsletter slug
$newsletter_slug = '';
if (isset($_GET['newsletter_slug'])) {
    $newsletter_slug = sanitize_text_field($_GET['newsletter_slug']);
} elseif (isset($_GET['page'])) {
    $page_slug = sanitize_text_field($_GET['page']);
    $prefix    = 'newsletter-stories-';
    if (strpos($page_slug, $prefix) === 0) {
        $newsletter_slug = str_replace($prefix, '', $page_slug);
    }
}

// Default to 'default' if not set
if (empty($newsletter_slug)) {
    $newsletter_slug = 'default';
}

// Validate slug
$newsletter_name = isset($newsletter_list[$newsletter_slug]) ? $newsletter_list[$newsletter_slug] : '';
if (empty($newsletter_name)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Newsletter not found.', 'newsletter') . '</p></div>';
    return;
}

// Retrieve assigned categories and blocks
$assigned_categories = get_option("newsletter_categories_$newsletter_slug", []);
$blocks              = get_option("newsletter_blocks_$newsletter_slug", []);
$all_categories      = get_categories(['include' => $assigned_categories, 'hide_empty' => false]);

// Fetch templates
$available_templates = get_option('newsletter_templates', []);
if (empty($available_templates)) {
    $available_templates['default'] = [
        'name'    => __('Default Template', 'newsletter'),
        'content' => $default_template,
    ];
}

// Determine next scheduled time
$send_days = get_option("newsletter_send_days_$newsletter_slug", []);
$send_time = get_option("newsletter_send_time_$newsletter_slug", '');
$next_scheduled_text = '';
$send_date_display = wp_date('F j, Y');
$next_scheduled_timestamp = null;

if (!empty($send_days) && !empty($send_time)) {
    $tz = wp_timezone();
    $now_local = new DateTime('now', $tz);
    $today_day = strtolower($now_local->format('l'));

    $send_today = DateTime::createFromFormat('Y-m-d H:i', $now_local->format('Y-m-d') . ' ' . $send_time, $tz);

    if (in_array($today_day, array_map('strtolower', $send_days)) && $send_today > $now_local) {
        $next_scheduled_timestamp = $send_today->getTimestamp();
    } else {
        for ($i = 1; $i <= 7; $i++) {
            $candidate = clone $now_local;
            $candidate->modify('+' . $i . ' days');
            $candidate_day = strtolower($candidate->format('l'));

            if (in_array($candidate_day, array_map('strtolower', $send_days))) {
                $candidate_send_time = DateTime::createFromFormat('Y-m-d H:i', $candidate->format('Y-m-d') . ' ' . $send_time, $tz);
                $next_scheduled_timestamp = $candidate_send_time->getTimestamp();
                break;
            }
        }
    }

    if ($next_scheduled_timestamp) {
        $edit_link = esc_url(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_slug));
        $next_scheduled_local = wp_date('l, F j, Y \a\t g:i A', $next_scheduled_timestamp);
        $next_scheduled_text = '<tr><th scope="row">' . esc_html__('Next Scheduled', 'newsletter') . '</th><td><strong>' . esc_html($next_scheduled_local) . '</strong> <a href="' . $edit_link . '">Edit</a></td></tr>';
        $send_date_display = wp_date('F j, Y', $next_scheduled_timestamp);
    }
}

// Enqueue admin JS
wp_enqueue_script('newsletter-admin-js', NEWSLETTER_PLUGIN_URL . 'assets/js/newsletter-admin.js', ['jquery', 'jquery-ui-datepicker', 'jquery-ui-accordion', 'jquery-ui-sortable'], '1.0', true);

// Prepare categories for JS
$categories_data = [];
if (!empty($all_categories)) {
    foreach ($all_categories as $category) {
        $categories_data[] = [
            'term_id' => $category->term_id,
            'name'    => $category->name,
        ];
    }
}

// Prepare templates for JS
$templates_data = [];
if (!empty($available_templates)) {
    foreach ($available_templates as $template_id => $template) {
        $templates_data[] = [
            'id'   => $template_id,
            'name' => $template['name'],
        ];
    }
}

wp_localize_script('newsletter-admin-js', 'newsletterData', [
    'ajaxUrl'               => admin_url('admin-ajax.php'),
    'nonceLoadPosts'        => wp_create_nonce('load_block_posts_nonce'),
    'nonceGeneratePreview'  => wp_create_nonce('generate_preview_nonce'),
    'nonceSaveBlocks'       => wp_create_nonce('save_blocks_action'),
    'newsletterSlug'        => $newsletter_slug,
    'blockLabel'            => __('Block', 'newsletter'),
    'blockTitleLabel'       => __('Block Title:', 'newsletter'),
    'blockTypeLabel'        => __('Block Type:', 'newsletter'),
    'contentLabel'          => __('Content', 'newsletter'),
    'advertisingLabel'      => __('Advertising', 'newsletter'),
    'selectCategoryLabel'   => __('Select Category:', 'newsletter'),
    'selectCategoryOption'  => __('-- Select Category --', 'newsletter'),
    'selectCategoryPrompt'  => __('Please select a category to display posts.', 'newsletter'),
    'advertisingHtmlLabel'  => __('Advertising HTML:', 'newsletter'),
    'removeBlockLabel'      => __('Remove Block', 'newsletter'),
    'categories'            => $categories_data,
    'availableTemplates'    => $templates_data,
    'templateLabel'         => __('Template:', 'newsletter'),
    'nonceMailchimp'        => wp_create_nonce('mailchimp_campaign_nonce'),
    'nextScheduledTimestamp'=> $next_scheduled_timestamp ? $next_scheduled_timestamp : ''
]);

?>
<div class="wrap">
    <h1><?php echo esc_html(sprintf(__('Newsletter Generator for %s', 'newsletter'), $newsletter_name)); ?></h1>
    <div class="flex-container">
        <div class="left-column">
            <!-- Use admin-post.php for form submission -->
            <form method="post" id="blocks-form" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_blocks_action', 'blocks_nonce'); ?>
                <input type="hidden" name="action" value="newsletter_stories_form_submission">
                <input type="hidden" name="newsletter_slug" value="<?php echo esc_attr($newsletter_slug); ?>">

                <div class="settings-and-buttons">
                    <div class="campaign-settings">
                        <h2 class="nav-tab-wrapper">
                            <a href="#campaign-settings" class="nav-tab nav-tab-active" data-tab="campaign-settings"><?php esc_html_e('Campaign Settings', 'newsletter'); ?></a>
                            <a href="#tag-targeting" class="nav-tab" data-tab="tag-targeting"><?php esc_html_e('Tag Targeting', 'newsletter'); ?></a>
                            <a href="#header-html" class="nav-tab" data-tab="header-html"><?php esc_html_e('Header HTML', 'newsletter'); ?></a>
                            <a href="#footer-html" class="nav-tab" data-tab="footer-html"><?php esc_html_e('Footer HTML', 'newsletter'); ?></a>
                        </h2>

                        <!-- Campaign Settings Tab -->
                        <div id="campaign-settings" class="tab-content active">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Campaign Name', 'newsletter'); ?></th>
                                    <td>
                                        <span><?php echo esc_html($newsletter_name . ' - ' . $send_date_display); ?></span>
                                        <span class="tooltip-icon" title="<?php esc_attr_e('This campaign name is automatically generated based on your set days and times.', 'newsletter'); ?>">
                                            <span class="dashicons dashicons-info"></span>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="subject_line"><?php esc_html_e('Subject Line', 'newsletter'); ?></label></th>
                                    <td><input type="text" id="subject_line" name="subject_line" class="regular-text" value="<?php echo esc_attr(get_option("newsletter_subject_line_$newsletter_slug", '')); ?>"></td>
                                </tr>
                                <?php
                                if (!empty($next_scheduled_text)) {
                                    echo $next_scheduled_text;
                                }
                                ?>
                            </table>
                        </div>

                        <!-- Tag Targeting Tab Content -->
                        <div id="tag-targeting" class="tab-content">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Target Tags', 'newsletter'); ?></th>
                                    <td>
                                        <?php
                                        $mailchimp = new Newsletter_Mailchimp_API();
                                        $list_id = get_option('mailchimp_list_id');
                                        $tags_response = $mailchimp->get_list_tags($list_id);
                                        $selected_tags = get_option("newsletter_target_tags_$newsletter_slug", []);

                                        if (!is_wp_error($tags_response) && isset($tags_response['tags'])) {
                                            echo '<select name="target_tags[]" multiple>';
                                            foreach ($tags_response['tags'] as $tag) {
                                                printf(
                                                    '<option value="%s" %s>%s</option>',
                                                    esc_attr($tag['id']),
                                                    selected(in_array($tag['id'], $selected_tags), true, false),
                                                    esc_html($tag['name'])
                                                );
                                            }
                                            echo '</select>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Header HTML Tab -->
                        <div id="header-html" class="tab-content">
                            <table class="form-table">
                                <tr>
                                    <td colspan="2">
                                        <textarea id="custom_header" name="custom_header" rows="10" class="large-text"><?php echo esc_textarea(get_option("newsletter_custom_header_$newsletter_slug", '')); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Footer HTML Tab -->
                        <div id="footer-html" class="tab-content">
                            <table class="form-table">
                                <tr>
                                    <td colspan="2">
                                        <textarea id="custom_footer" name="custom_footer" rows="10" class="large-text"><?php echo esc_textarea(get_option("newsletter_custom_footer_$newsletter_slug", '')); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Local Buttons -->
                        <div class="local-buttons">
                            <button type="submit" class="button button-primary button-large action-button" id="save-blocks">
                                <strong><?php esc_html_e('SAVE', 'newsletter'); ?></strong>
                            </button>

                            <button type="button" class="button button-large action-button" id="reset-blocks">
                                <strong><?php esc_html_e('RESET', 'newsletter'); ?></strong>
                            </button>

                            <!-- Generate PDF Button -->
                            <button type="submit" class="button button-large action-button" name="generate_pdf" value="1" id="generate-pdf" style="background-color: #0073aa; color: #fff;" onclick="return confirm('<?php esc_attr_e('Generate PDF now?', 'newsletter'); ?>');">
                                <span class="dashicons dashicons-pdf" style="vertical-align: middle; margin-right:5px;"></span>
                                <strong><?php esc_html_e('GENERATE PDF', 'newsletter'); ?></strong>
                            </button>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_slug)); ?>"
                               class="button button-large action-button" id="settings-button"
                               style="background-color: #28a745; color: #fff;">
                                <strong><?php esc_html_e('SETTINGS', 'newsletter'); ?></strong>
                            </a>
                        </div>
                    </div>

                    <div class="mailchimp-block">
                        <div class="logo-container">
                            <img src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/images/mailchimp-logo.webp'); ?>" alt="Mailchimp Logo" class="mailchimp-logo" />
                        </div>

                        <div class="button-group">
                            <div class="buttons">
                                <button type="button" class="button button-large action-button" id="send-test-email">
                                    <span class="dashicons dashicons-email" style="vertical-align: middle; margin-right:5px;"></span>
                                    <strong><?php esc_html_e('SEND TEST', 'newsletter'); ?></strong>
                                </button>

                                <button type="button" class="button button-large action-button" id="send-to-mailchimp">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle; margin-right:5px;"></span>
                                    <strong><?php esc_html_e('SEND DRAFT', 'newsletter'); ?></strong>
                                </button>

<?php if ($next_scheduled_timestamp): ?>
    <button type="button" 
            class="button button-large action-button schedule-button" 
            id="schedule-campaign" 
            data-timestamp="<?php echo esc_attr((int)$next_scheduled_timestamp); ?>"
            data-formatted-time="<?php echo esc_attr(wp_date('F j, Y g:i a', $next_scheduled_timestamp)); ?>">
        <span class="dashicons dashicons-calendar" style="vertical-align: middle; margin-right:5px;"></span>
        <strong><?php esc_html_e('SCHEDULE', 'newsletter'); ?></strong>
    </button>
<?php endif; ?> 
                                <button type="button" class="button button-large action-button schedule-button" id="send-now">
                                    <span class="dashicons dashicons-megaphone" style="vertical-align: middle; margin-right:5px;"></span>
                                    <strong><?php esc_html_e('SEND NOW', 'newsletter'); ?></strong>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Email Dialog -->
                <div id="test-email-dialog" class="dialog-overlay" style="display:none;">
                    <div class="dialog-content">
                        <div class="dialog-step" id="email-input-step">
                            <h3><?php esc_html_e('Send Test Email', 'newsletter'); ?></h3>
                            <p>
                                <label for="test-email"><?php esc_html_e('Email Address:', 'newsletter'); ?></label>
                                <input type="email" id="test-email" class="regular-text">
                            </p>
                            <div class="dialog-buttons">
                                <button type="button" class="button" id="cancel-test"><?php esc_html_e('Cancel', 'newsletter'); ?></button>
                                <button type="button" class="button button-primary" id="send-test"><?php esc_html_e('Send', 'newsletter'); ?></button>
                            </div>
                        </div>

                        <div class="dialog-step" id="success-step" style="display: none;">
                            <h3><?php esc_html_e('Success!', 'newsletter'); ?></h3>
                            <p><?php esc_html_e('Test email has been sent successfully.', 'newsletter'); ?></p>
                            <div class="dialog-buttons">
                                <button type="button" class="button button-primary" id="close-success"><?php esc_html_e('Close', 'newsletter'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Test Email Dialog -->

                <!-- Blocks Management -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Blocks', 'newsletter'); ?></h2>
                    <div id="blocks-container">
                        <?php
                        if (!empty($blocks)) {
                            foreach ($blocks as $index => $block) {
                                include NEWSLETTER_PLUGIN_DIR . 'admin/partials/block-item.php';
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button button-large" id="add-block">
                        <strong><?php esc_html_e('ADD BLOCK', 'newsletter'); ?></strong>
                    </button>
                </div>
            </form>
        </div>

        <div class="right-column">
            <!-- Newsletter Preview -->
            <div class="settings-box">
                <h2><?php esc_html_e('Newsletter Preview', 'newsletter'); ?></h2>
                <div id="story-preview">
                    <div id="preview-content">
                        <?php
                        // Render the preview
                        include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Confirm Send Now -->
<script>
jQuery(document).ready(function($) {
    var sendNowButtonClickedOnce = false;
    $('#send-now').on('click', function() {
        if (!sendNowButtonClickedOnce) {
            var firstConfirm = confirm("Are you sure you want to SEND NOW to your Mailchimp list?");
            if (firstConfirm) {
                sendNowButtonClickedOnce = true;
                alert("Click SEND NOW again to confirm sending.");
                $('#send-now').addClass('send-now-confirmed');
            }
        } else {
            var secondConfirm = confirm("Double Checking: Are you absolutely sure you want to SEND NOW?");
            if (secondConfirm) {
                $.ajax({
                    url: newsletterData.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'send_now_campaign',
                        security: newsletterData.nonceMailchimp,
                        newsletter_slug: newsletterData.newsletterSlug
                    },
                    success: function(response) {
                        if (response.success) {
                            alert("Campaign has been sent successfully.");
                            location.reload();
                        } else {
                            alert("Error sending campaign: " + (response.data ? response.data : 'Unknown error'));
                        }
                    },
                    error: function(error) {
                        alert("Ajax error sending campaign.");
                    }
                });
            } else {
                sendNowButtonClickedOnce = false;
                $('#send-now').removeClass('send-now-confirmed');
            }
        }
    });
});
</script>

</div>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');
    });

    $('.nav-tab.nav-tab-active').trigger('click');
});
</script>
