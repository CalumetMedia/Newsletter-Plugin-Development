<?php
if (!defined('ABSPATH')) exit;

function wr_register_pdf_settings() {
    register_setting('wr_pdf_settings_group', 'pdf_enabled', [
        'type' => 'boolean',
        'default' => true
    ]);

    register_setting('wr_pdf_settings_group', 'pdf_auto_generate', [
        'type' => 'boolean',
        'default' => false
    ]);

    register_setting('wr_pdf_settings_group', 'pdf_date_format', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'F j, Y'
    ]);

    add_settings_section(
        'wr_pdf_settings_section',
        'PDF Configuration',
        '__return_false',
        'wr-pdf-settings'
    );

    add_settings_field(
        'pdf_enabled',
        'Enable PDF Generation',
        'wr_pdf_enabled_callback',
        'wr-pdf-settings',
        'wr_pdf_settings_section'
    );

    add_settings_field(
        'pdf_auto_generate',
        'Auto-generate PDFs',
        'wr_pdf_auto_generate_callback',
        'wr-pdf-settings',
        'wr_pdf_settings_section'
    );

    add_settings_field(
        'pdf_date_format',
        'Date Format',
        'wr_pdf_date_format_callback',
        'wr-pdf-settings',
        'wr_pdf_settings_section'
    );
}
add_action('admin_init', 'wr_register_pdf_settings');

function wr_pdf_enabled_callback() {
    $enabled = get_option('pdf_enabled', true);
    echo '<input type="checkbox" name="pdf_enabled" value="1" ' . checked($enabled, true, false) . '>';
    echo '<p class="description">Enable PDF generation functionality</p>';
}

function wr_pdf_auto_generate_callback() {
    $auto_generate = get_option('pdf_auto_generate', false);
    echo '<input type="checkbox" name="pdf_auto_generate" value="1" ' . checked($auto_generate, true, false) . '>';
    echo '<p class="description">Automatically generate PDFs when sending newsletters</p>';
}

function wr_pdf_date_format_callback() {
    $option = get_option('pdf_date_format', 'F j, Y');
    echo '<input type="text" name="pdf_date_format" value="' . esc_attr($option) . '" class="regular-text">';
    echo '<p class="description">Enter a valid PHP date format string. Default is "F j, Y".</p>';
}

function wr_pdf_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wr_pdf_settings_group');
            do_settings_sections('wr-pdf-settings');
            submit_button('Save PDF Settings');
            ?>
        </form>
        
        <hr>
        
        <h2>PDF Generation Status</h2>
        <div class="pdf-status">
            <?php
            $upload_dir = wp_upload_dir();
            $secure_dir = $upload_dir['basedir'] . '/secure';
            
            if (!file_exists($secure_dir)) {
                echo '<p class="notice notice-error">PDF secure directory is missing. PDFs will be created when generating your first newsletter.</p>';
            } else {
                echo '<p class="notice notice-success">PDF system is ready.</p>';
            }
            ?>
        </div>
        
        <h2>Recent PDF Logs</h2>
        <?php
        global $pdf_logger;
        if ($pdf_logger) {
            $logs = $pdf_logger->get_logs(1);
            if (!empty($logs)) {
                echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 300px; overflow: auto;">';
                foreach ($logs as $date => $log) {
                    echo esc_html($log);
                }
                echo '</pre>';
            } else {
                echo '<p>No recent PDF logs found.</p>';
            }
        }
        ?>
    </div>
    <?php
}
