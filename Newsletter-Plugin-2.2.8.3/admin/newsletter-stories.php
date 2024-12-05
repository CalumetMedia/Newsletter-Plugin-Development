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
    // update_option("newsletter_enable_custom_header_$newsletter_slug", isset($_POST['enable_custom_header']) ? 1 : 0));
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
    <h1><?php echo esc_html(sprintf(__('Newsletter Generator for %s', 'newsletter'), $newsletter_name)); ?></h1>
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

                <!-- Flex Container for Campaign Settings and Button Group -->
                <div class="settings-and-buttons">
                    <!-- Campaign Settings & Custom Code -->
                    <div class="campaign-settings">
                        <h2 class="nav-tab-wrapper">
                            <a href="#campaign-settings" class="nav-tab nav-tab-active" data-tab="campaign-settings"><?php esc_html_e('Campaign Settings', 'newsletter'); ?></a>
                            <a href="#header-html" class="nav-tab" data-tab="header-html"><?php esc_html_e('Header HTML', 'newsletter'); ?></a>
                            <a href="#footer-html" class="nav-tab" data-tab="footer-html"><?php esc_html_e('Footer HTML', 'newsletter'); ?></a>
                        </h2>

                        <!-- Campaign Settings Tab -->
                        <div id="campaign-settings" class="tab-content active">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="campaign_name"><?php esc_html_e('Campaign Name', 'newsletter'); ?></label></th>
                                    <td>
                                        <input type="text" id="campaign_name" name="campaign_name" class="regular-text" value="<?php echo esc_attr(get_option("newsletter_campaign_name_$newsletter_slug", '')); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="subject_line"><?php esc_html_e('Subject Line', 'newsletter'); ?></label></th>
                                    <td>
                                        <input type="text" id="subject_line" name="subject_line" class="regular-text" value="<?php echo esc_attr(get_option("newsletter_subject_line_$newsletter_slug", '')); ?>">
                                    </td>
                                </tr>
                                <?php
                                // Get newsletter schedule settings
                                $is_ad_hoc = get_option("newsletter_is_ad_hoc_$newsletter_slug", 0);
                                if (!$is_ad_hoc) {
                                    $send_days = get_option("newsletter_send_days_$newsletter_slug", []);
                                    $send_time = get_option("newsletter_send_time_$newsletter_slug", '');

                                    if (!empty($send_days) && !empty($send_time)) {
                                        $next_date = null;
                                        $current_time = current_time('timestamp');
                                        $today = strtolower(date('l', $current_time));
                                        $current_hour = date('H:i', $current_time);

                                        // Convert send_time to 24hr format for comparison
                                        $schedule_time = date('H:i', strtotime($send_time));

                                        // Find next scheduled day
                                        $checked_days = 0;
                                        $day_index = array_search($today, array_map('strtolower', $send_days));

                                        while ($checked_days < 7 && $next_date === null) {
                                            if ($day_index === false || $day_index >= count($send_days)) {
                                                $day_index = 0;
                                            }

                                            $check_day = strtolower($send_days[$day_index]);
                                            $days_to_add = 0;

                                            if ($check_day === $today) {
                                                if ($current_hour < $schedule_time) {
                                                    $next_date = strtotime("today " . $schedule_time);
                                                }
                                            } else {
                                                $days_to_add = 1;
                                                while (strtolower(date('l', strtotime("+$days_to_add days"))) !== $check_day) {
                                                    $days_to_add++;
                                                }
                                                $next_date = strtotime("+$days_to_add days " . $schedule_time);
                                            }

                                            $day_index++;
                                            $checked_days++;
                                        }

                                        if ($next_date) {
                                            echo '<tr>';
                                            echo '<th scope="row">' . esc_html__('Next Scheduled', 'newsletter') . '</th>';
                                            echo '<td><strong>' . date('l, F j, Y \a\t g:i A', $next_date) . '</strong></td>';
                                            echo '</tr>';
                                        }
                                    }
                                }
                                ?>

                                <!-- Add after the Next Scheduled row -->
                                <tr>
                                    <th scope="row"><?php esc_html_e('Manual Schedule', 'newsletter'); ?></th>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="date" id="manual_schedule_date" name="manual_schedule_date" style="width: 150px;" value="<?php echo esc_attr(get_option("newsletter_manual_schedule_date_$newsletter_slug", '')); ?>">
                                            <input type="time" id="manual_schedule_time" name="manual_schedule_time" style="width: 120px;" value="<?php echo esc_attr(get_option("newsletter_manual_schedule_time_$newsletter_slug", '')); ?>">
                                            <label>
                                                <input type="checkbox" id="use_manual_schedule" name="use_manual_schedule" <?php checked(get_option("newsletter_use_manual_schedule_$newsletter_slug", false)); ?>>
                                                <?php esc_html_e('Override default schedule', 'newsletter'); ?>
                                            </label>
                                        </div>
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
                    </div>

                    <!-- Button Group -->
                    <div class="button-group" style="flex: 1 1 30%; max-width: 30%; box-sizing: border-box; display: flex; flex-direction: column; align-items: flex-start; justify-content: center;">
                        <div class="buttons" style="display: flex; flex-direction: column; gap: 15px;">
                            <button type="submit" class="button button-primary button-large" id="save-blocks" style="padding: 10px 20px; font-size: 18px;">
                                <strong><?php esc_html_e('SAVE', 'newsletter'); ?></strong>
                            </button>
                            <?php if (get_option('mailchimp_api_key')) : ?>
                                <button type="button" class="button button-secondary button-large" id="send-to-mailchimp" style="padding: 10px 20px; font-size: 18px; background:#FFE01B; align-items: center; justify-content: center;">
                                    <img src="<?php echo esc_url(NEWSLETTER_PLUGIN_URL . 'assets/images/mailchimp-logo.webp'); ?>" alt="<?php esc_attr_e('Mailchimp', 'newsletter'); ?>" style="height: 45px; display: flex; align-items: center; justify-content: center; margin-right: 5px;">
                                </button>
                            <?php endif; ?>
                            <button type="button" class="button button-large" id="reset-blocks" style="padding: 10px 20px; font-size: 18px; background-color: #dc3545; color: white; border-color: #dc3545;">
                                <strong><?php esc_html_e('RESET', 'newsletter'); ?></strong>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- End of Settings and Buttons Flex Container -->

                <!-- Date Range Selection -->
                <div class="settings-box">
                    <h2><?php esc_html_e('Date Range', 'newsletter'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('End Date:', 'newsletter'); ?></label></th>
                            <td style="display: flex; align-items: center; gap: 15px;">
                                <button type="button" id="today_button" class="button" style="min-width: 80px;">
                                    <?php esc_html_e('TODAY', 'newsletter'); ?>
                                </button>
                                <input type="date" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? esc_attr($_POST['end_date']) : date('Y-m-d'); ?>" style="width: 150px;" />
                                <span style="margin-right: -10px;"><?php esc_html_e('Start Date:', 'newsletter'); ?></span>
                                <select id="date_range" name="date_range" style="width: 150px;">
                                    <option value="1"><?php esc_html_e('1 Day Prior', 'newsletter'); ?></option>
                                    <option value="3"><?php esc_html_e('3 Days Prior', 'newsletter'); ?></option>
                                    <option value="5"><?php esc_html_e('5 Days Prior', 'newsletter'); ?></option>
                                    <option value="7" selected><?php esc_html_e('7 Days Prior', 'newsletter'); ?></option>
                                    <option value="14"><?php esc_html_e('14 Days Prior', 'newsletter'); ?></option>
                                    <option value="30"><?php esc_html_e('30 Days Prior', 'newsletter'); ?></option>
                                    <option value="90"><?php esc_html_e('90 Days Prior', 'newsletter'); ?></option>
                                </select>
                                <span id="start_date_display" style="color: #666;">[<span class="date"></span>]</span>
                                <input type="hidden" id="start_date" name="start_date">
                            </td>
                        </tr>
                    </table>
                </div>

                <script>
                jQuery(document).ready(function($) {
                    function updateDates() {
                        var days = $('#date_range').val();
                        var endDate = new Date($('#end_date').val());
                        var startDate = new Date(endDate);
                        startDate.setDate(endDate.getDate() - parseInt(days));
                        var formattedStart = startDate.toISOString().split('T')[0];
                        $('#start_date').val(formattedStart);
                        $('#start_date_display .date').text(formattedStart);
                    }

                    if (!$('#end_date').val()) {
                        $('#end_date').val(new Date().toISOString().split('T')[0]);
                    }

                    $('#today_button').on('click', function() {
                        $('#end_date').val(new Date().toISOString().split('T')[0]);
                        updateDates();
                        // Trigger post reload
                        $('.block-category').each(function() {
                            var block = $(this).closest('.block-item');
                            var categoryId = $(this).val();
                            var blockIndex = block.data('index');
                            if (categoryId) {
                                loadBlockPosts(block, categoryId, blockIndex);
                            }
                        });
                    });

                    updateDates();

                    $('#date_range, #end_date').on('change', function() {
                        updateDates();
                        $('.block-category').each(function() {
                            var block = $(this).closest('.block-item');
                            var categoryId = $(this).val();
                            var blockIndex = block.data('index');
                            if (categoryId) {
                                loadBlockPosts(block, categoryId, blockIndex);
                            }
                        });
                    });
                });
                </script>

                <script>
                jQuery(document).ready(function($) {
                    $('#reset-blocks').on('click', function() {
                        if (confirm('Are you sure you want to reset all selections?')) {
                            // Reset all checkboxes
                            $('.block-posts input[type="checkbox"]').prop('checked', false);

                            // Reset end date to today
                            $('#end_date').val(new Date().toISOString().split('T')[0]);

                            // Reset date range to default
                            $('#date_range').val('7');

                            // Save newsletter with reset state
                            var formData = $('#blocks-form').serializeArray();
                            formData.push({ name: 'action', value: 'save_newsletter_blocks' });
                            formData.push({ name: 'security', value: newsletterData.nonceSaveBlocks });
                            formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });

                            $.ajax({
                                url: newsletterData.ajaxUrl,
                                method: 'POST',
                                data: formData,
                                success: function(response) {
                                    if (response.success) {
                                        // Refresh the page after saving
                                        window.location.reload();
                                    }
                                }
                            });
                        }
                    });
                });
                </script>

                <br>

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
                    <button type="button" class="button button-large" id="add-block">
                        <strong><?php esc_html_e('ADD BLOCK', 'newsletter'); ?></strong>
                    </button>
                </div>
                <!-- End of Blocks Management -->
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

<!-- JavaScript for Tab Functionality -->
<script>
    jQuery(document).ready(function($) {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var targetTab = $(this).data('tab');

            // Remove active class from all tabs
            $('.nav-tab').removeClass('nav-tab-active');

            // Add active class to the clicked tab
            $(this).addClass('nav-tab-active');

            // Hide all tab contents
            $('.tab-content').removeClass('active');

            // Show the targeted tab content
            $('#' + targetTab).addClass('active');
        });

        // Trigger click on the active tab to show it by default
        $('.nav-tab.nav-tab-active').trigger('click');
    });
</script>
