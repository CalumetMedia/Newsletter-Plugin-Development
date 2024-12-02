<?php
// Prevent unauthorized access
if (!defined('ABSPATH')) exit;

// Ensure the user has the required capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'newsletter'));
}

// Retrieve newsletter settings
$newsletter_list = get_option('newsletter_list', []);
$newsletter_name = isset($newsletter_list[$newsletter_id]) ? $newsletter_list[$newsletter_id] : '';
$categories = get_option("newsletter_categories_$newsletter_id", []);
$template_id = get_option("newsletter_template_id_$newsletter_id", 'default');

// Retrieve all available templates
$templates = get_option('newsletter_templates', []);

// Handle template selection save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    // Verify nonce for security
    if (!isset($_POST['newsletter_nonce']) || !wp_verify_nonce($_POST['newsletter_nonce'], 'save_template_action')) {
        echo '<div class="error"><p>' . __('Security check failed. Please try again.', 'newsletter') . '</p></div>';
    } else {
        // Sanitize the selected template ID
        $selected_template_id = isset($_POST['selected_template_id']) ? sanitize_text_field($_POST['selected_template_id']) : 'default';

        // Update the newsletter's template ID
        update_option("newsletter_template_id_$newsletter_id", $selected_template_id);
        $template_id = $selected_template_id;
        echo '<div class="updated"><p>' . esc_html__('Settings saved successfully.', 'newsletter') . '</p></div>';
    }
}

// Retrieve the selected template
$selected_template = isset($templates[$template_id]) ? $templates[$template_id] : null;

// Calculate default start and end dates
$end_date_default = date('Y-m-d'); // Today
$start_date_default = date('Y-m-d', strtotime('-14 days')); // 14 days ago

// Display header with newsletter name
echo '<div class="wrap">';
echo '<h1>' . sprintf(esc_html__('%s Stories', 'newsletter'), esc_html($newsletter_name)) . '</h1>';
?>
<div class="flex-container">
    <!-- Left Column (Settings) -->
    <div class="left-column">
        <!-- Filter Stories by Date -->
        <form method="post" id="date-range-form">
            <h2><?php esc_html_e('Filter Stories by Date', 'newsletter'); ?></h2>

            <label for="start_date"><?php esc_html_e('Start Date:', 'newsletter'); ?></label>
            <input type="text" id="start_date" name="start_date" value="<?php echo esc_attr($start_date_default); ?>" />

            <label for="end_date"><?php esc_html_e('End Date:', 'newsletter'); ?></label>
            <input type="text" id="end_date" name="end_date" value="<?php echo esc_attr($end_date_default); ?>" />
        </form>

        <!-- Stories List -->
        <div id="stories-container">
            <!-- Stories will load here dynamically based on the selected date range -->
        </div>

        <!-- Settings Boxes Container -->
        <div class="settings-boxes">
            <!-- Newsletter Settings Box -->
            <div class="settings-box">
                <h2><?php esc_html_e('Newsletter Settings', 'newsletter'); ?></h2>
                <p><?php esc_html_e('All posts within the selected date range and categories will be displayed.', 'newsletter'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=newsletter-settings&tab=settings&newsletter_id=' . $newsletter_id); ?>" class="button button-secondary"><?php esc_html_e('Edit', 'newsletter'); ?></a>
            </div>

            <!-- Template Settings Box -->
            <div class="settings-box">
                <h2><?php esc_html_e('Template Settings', 'newsletter'); ?></h2>
                <form method="post" style="margin-bottom: 0;">
                    <?php wp_nonce_field('save_template_action', 'newsletter_nonce'); ?>
                    <label for="selected_template_id"><?php esc_html_e('Select Template:', 'newsletter'); ?></label>
                    <select name="selected_template_id" id="selected_template_id">
                        <option value=""><?php esc_html_e('Default Template', 'newsletter'); ?></option>
                        <?php
                        if (!empty($templates)) {
                            foreach ($templates as $index => $template) {
                                // Ensure 'name' key exists
                                $template_name = isset($template['name']) ? $template['name'] : __('Untitled Template', 'newsletter');
                                $selected = ($template_id == $index) ? 'selected' : '';
                                echo '<option value="' . esc_attr($index) . '" ' . esc_attr($selected) . '>' . esc_html($template_name) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>' . esc_html__('No templates available', 'newsletter') . '</option>';
                        }
                        ?>
                    </select>
                    <div style="margin-top: 10px;">
                        <input type="submit" name="save_template" class="button button-primary" value="<?php esc_attr_e('Save', 'newsletter'); ?>"> 
                        <a href="<?php echo admin_url('admin.php?page=newsletter-settings&tab=templates'); ?>" class="button button-secondary"><?php esc_html_e('Edit Templates', 'newsletter'); ?></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coming Soon and Send Email Buttons -->
        <div style="margin-top: 20px;">
            <p class="coming-soon"><?php esc_html_e('Coming soon!', 'newsletter'); ?></p>
            <button class="button button-primary button-large"><?php esc_html_e('Send Email', 'newsletter'); ?></button>
            <button class="button button-secondary button-large"><?php esc_html_e('Send Test Email', 'newsletter'); ?></button>
        </div>
    </div>

    <!-- Right Column (Preview) -->
    <div class="right-column">
        <div id="story-preview">
            <h2><?php esc_html_e('Story Preview', 'newsletter'); ?></h2>
            <div id="preview-content">
                <p><?php esc_html_e('Select stories to see a preview here.', 'newsletter'); ?></p>
            </div>
            <img id="phone-overlay" src="<?php echo plugin_dir_url(__FILE__) . 'images/phone-screen.png'; ?>" alt="<?php esc_attr_e('Phone Screen', 'newsletter'); ?>">
        </div>
    </div>
</div>

<?php
// Generate the nonce
$ajax_nonce = wp_create_nonce('newsletter_nonce');

// Pass variables to JavaScript
?>
<script>
    var ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
    var ajaxNonce = "<?php echo $ajax_nonce; ?>";
    var newsletterId = "<?php echo esc_js($newsletter_id); ?>";
</script>
