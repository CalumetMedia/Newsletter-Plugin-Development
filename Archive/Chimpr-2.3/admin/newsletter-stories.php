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
]);

// Determine next scheduled send time (if any)
$send_days = get_option("newsletter_send_days_$newsletter_slug", []);
$send_time = get_option("newsletter_send_time_$newsletter_slug", '');
$next_scheduled_text = '';
if (!empty($send_days) && !empty($send_time)) {
    $current_time = current_time('timestamp');
    $today = strtolower(date('l', $current_time));
    $current_hour = date('H:i', $current_time);
    $schedule_time = date('H:i', strtotime($send_time));

    $next_date = null;
    for ($i = 0; $i < 7; $i++) {
        $check_timestamp = strtotime("+$i days", $current_time);
        $check_day = strtolower(date('l', $check_timestamp));

        if (in_array($check_day, array_map('strtolower', $send_days))) {
            if ($i === 0 && $current_hour >= $schedule_time) {
                continue; 
            }
            $next_date = strtotime(date('Y-m-d', $check_timestamp) . ' ' . $schedule_time);
            break;
        }
    }

    if ($next_date) {
        $edit_link = esc_url(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_slug));
        $next_scheduled_text = '<tr><th scope="row">' . esc_html__('Next Scheduled', 'newsletter') . '</th><td><strong>' . date('l, F j, Y \a\t g:i A', $next_date) . '</strong> <a href="' . $edit_link . '">Edit</a></td></tr>';
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html(sprintf(__('Newsletter Generator for %s', 'newsletter'), $newsletter_name)); ?></h1>
    <div class="flex-container">
        <div class="left-column">
            <!-- Left Column Content -->
            <form method="post" id="blocks-form">
                <?php wp_nonce_field('save_blocks_action', 'blocks_nonce'); ?>
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
                                        <?php
                                        // Determine a send date to display, if available:
                                        $send_date_display = !empty($next_date) ? date('F j, Y', $next_date) : date('F j, Y');
                                        ?>
                                        <span><?php echo esc_html($newsletter_name . ' - ' . $send_date_display); ?></span>
                                        <span class="tooltip-icon" title="<?php esc_attr_e('This campaign name is automatically generated upon sending or scheduling.', 'newsletter'); ?>">
                                            <span class="dashicons dashicons-info"></span>
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="subject_line"><?php esc_html_e('Subject Line', 'newsletter'); ?></label></th>
                                    <td><input type="text" id="subject_line" name="subject_line" class="regular-text" value="<?php echo esc_attr(get_option("newsletter_subject_line_$newsletter_slug", '')); ?>"></td>
                                </tr>
                                <?php
                                // Display next scheduled time if available
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="feather feather-save">
                                    <path d="M19 21H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2
                                    2-2h11l5 5v11c0 1.1-.9 2-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                <strong>SAVE</strong>
                            </button>

                            <button type="button" class="button button-large action-button" id="reset-blocks">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="feather feather-power">
                                    <path d="M18.36 6.64a9 9 0 1 1-12.73
                                    0"/>
                                    <line x1="12" y1="2" x2="12" y2="12"/>
                                </svg>
                                <strong>RESET</strong>
                            </button>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=newsletter-settings&tab=' . $newsletter_slug)); ?>"
                               class="button button-large action-button" id="settings-button"
                               style="background-color: #28a745; color: #fff;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="feather feather-settings">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33
                                    1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65
                                    1.65 0 0 0-1.82-.33 1.65
                                    1.65 0 0 0-1 1.51V21a2 2 0 0
                                    1-2 2h-0a2 2 0 0 1-2-2v-.09a1.65
                                    1.65 0 0 0-1-1.51 1.65
                                    1.65 0 0 0-1.82.33l-.06.06a2 2 0 0
                                    1-2.83-2.83l.06-.06a1.65
                                    1.65 0 0 0 .33-1.82 1.65
                                    1.65 0 0 0-1.51-1H3a2 2 0 0
                                    1-2-2v0a2 2 0 0 1 2-2h.09a1.65
                                    1.65 0 0 0 1.51-1 1.65
                                    1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0
                                    1 2.83-2.83l.06.06a1.65
                                    1.65 0 0 0 1.82.33h0a1.65
                                    1.65 0 0 0 1-1.51V3a2 2 0 0
                                    1 2-2h0a2 2 0 0 1 2 2v.09a1.65
                                    1.65 0 0 0 1 1.51 1.65
                                    1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0
                                    1 2.83 2.83l-.06.06a1.65
                                    1.65 0 0 0-.33 1.82v0a1.65
                                    1.65 0 0 0 1.51 1H21a2 2 0 0
                                    1 2 2v0a2 2 0 0 1-2 2h-.09a1.65
                                    1.65 0 0 0-1.51 1z"/>
                                </svg>
                                <strong>SETTINGS</strong>
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                                        <path d="m22 5-10 7L2 5"/>
                                    </svg>
                                    <strong>SEND TEST</strong>
                                </button>

                                <button type="button" class="button button-large action-button" id="send-to-mailchimp">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                                        <path d="M21 3v5h-5"/>
                                    </svg>
                                    <strong>SEND DRAFT</strong>
                                </button>

                                <button type="button" class="button button-large action-button schedule-button" id="schedule-campaign">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <strong>SCHEDULE</strong>
                                </button>

                                <button type="button" class="button button-large action-button schedule-button" id="send-now">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m5 12 7-7 7 7"/>
                                        <path d="M12 19V5"/>
                                    </svg>
                                    <strong>SEND NOW</strong>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Email Dialog -->
                <div id="test-email-dialog" class="dialog-overlay">
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
                                <button type="button" class="button button-primary" id="close-success">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <br>

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
                        <?php include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Tab Functionality -->
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

<!-- Send Now Button JavaScript -->
<script>
jQuery(document).ready(function($) {
    var sendNowButtonClickedOnce = false;
    $('#send-now').on('click', function() {
        console.log('SEND NOW clicked.');
        if (!sendNowButtonClickedOnce) {
            console.log('Showing first confirmation prompt.');
            var firstConfirm = confirm("Are you sure you want to SEND NOW to your Mailchimp list?");
            if (firstConfirm) {
                console.log('First confirmation passed. Prompting user to click again.');
                sendNowButtonClickedOnce = true;
                alert("Click SEND NOW again to confirm sending.");
                $('#send-now').addClass('send-now-confirmed');
            } else {
                console.log('User canceled on first confirmation.');
            }
        } else {
            console.log('Second confirmation attempt.');
            var secondConfirm = confirm("Double Checking: Are you absolutely sure you want to SEND NOW?");
            if (secondConfirm) {
                console.log('Second confirmation passed. Sending AJAX request now.');
                $.ajax({
                    url: newsletterData.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'send_now_campaign',
                        security: newsletterData.nonceMailchimp,
                        newsletter_slug: newsletterData.newsletterSlug
                    },
                    success: function(response) {
                        console.log('AJAX success callback fired.', response);
                        if (response.success) {
                            alert("Campaign has been sent successfully.");
                            location.reload();
                        } else {
                            alert("Error sending campaign: " + (response.data ? response.data : 'Unknown error'));
                        }
                    },
                    error: function(error) {
                        console.error('AJAX error callback fired.', error);
                        alert("Ajax error sending campaign.");
                    }
                });
            } else {
                console.log('User canceled on second confirmation.');
                sendNowButtonClickedOnce = false;
                $('#send-now').removeClass('send-now-confirmed');
            }
        }
    });
});
</script>
