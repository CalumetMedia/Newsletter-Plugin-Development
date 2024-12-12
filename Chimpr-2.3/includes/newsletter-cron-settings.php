<?php
if (!defined('ABSPATH')) exit;

class Newsletter_Cron_Settings {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        register_setting('newsletter_cron_settings_group', 'newsletter_use_wp_cron', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));

        register_setting('newsletter_cron_settings_group', 'newsletter_use_pageview_cron', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));

        register_setting('newsletter_cron_settings_group', 'newsletter_pageview_threshold', array(
            'type' => 'integer',
            'default' => 100,
            'sanitize_callback' => 'absint',
        ));

        add_settings_section(
            'newsletter_cron_main_section',
            'Newsletter Cron Configuration',
            function() {
                echo '<p>Configure how and when automated newsletter sends are scheduled.</p>';
            },
            'newsletter-cron-settings'
        );

        add_settings_field(
            'newsletter_use_wp_cron',
            'Use WP-Cron Scheduling',
            array($this, 'render_wp_cron_field'),
            'newsletter-cron-settings',
            'newsletter_cron_main_section',
            array(
                'label_for' => 'newsletter_use_wp_cron',
                'option_name' => 'newsletter_use_wp_cron',
                'description' => 'If enabled, a cron event will run every hour. The plugin will check if any newsletters are scheduled in the next 75 minutes. If not, it will schedule a new newsletter if due. This minimizes server load compared to more frequent checks.'
            )
        );

        add_settings_field(
            'newsletter_use_pageview_cron',
            'Use Pageview Triggered Cron',
            array($this, 'render_pageview_cron_field'),
            'newsletter-cron-settings',
            'newsletter_cron_main_section',
            array(
                'label_for' => 'newsletter_use_pageview_cron',
                'option_name' => 'newsletter_use_pageview_cron',
                'description' => 'If enabled, the newsletter scheduling will be triggered after a certain number of pageviews. Note: This can still affect performance, and is less recommended than a server-side cron.'
            )
        );

        add_settings_field(
            'newsletter_pageview_threshold',
            'Pageview Threshold',
            array($this, 'render_number_field'),
            'newsletter-cron-settings',
            'newsletter_cron_main_section',
            array(
                'label_for' => 'newsletter_pageview_threshold',
                'option_name' => 'newsletter_pageview_threshold',
                'description' => 'Number of pageviews required to trigger a newsletter scheduling check (if pageview cron is enabled).'
            )
        );

        // Additional section for server-side cron setup instructions
        add_settings_section(
            'newsletter_cron_server_section',
            'Server-Side Cron Setup (Recommended)',
            function() {
                echo '<p>For better performance, consider using a server-side cron job once per hour instead of WP-Cron or Pageview Triggered Cron. The automation checks 75 minutes ahead, ensuring no duplicate scheduling and catching all future newsletters.</p>';
            },
            'newsletter-cron-settings'
        );

        add_settings_field(
            'newsletter_cron_server_info',
            'How to Setup Server-Side Cron',
            array($this, 'render_server_info_field'),
            'newsletter-cron-settings',
            'newsletter_cron_server_section'
        );
    }

    public function render_wp_cron_field($args) {
        $option = get_option($args['option_name'], false);
        ?>
        <input type="checkbox" id="<?php echo esc_attr($args['option_name']); ?>" name="<?php echo esc_attr($args['option_name']); ?>" value="1" <?php checked($option, 1); ?> />
        <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php
    }

    public function render_pageview_cron_field($args) {
        $option = get_option($args['option_name'], false);
        ?>
        <input type="checkbox" id="<?php echo esc_attr($args['option_name']); ?>" name="<?php echo esc_attr($args['option_name']); ?>" value="1" <?php checked($option, 1); ?> />
        <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php
    }

    public function render_number_field($args) {
        $option = get_option($args['option_name'], 100);
        ?>
        <input type="number" id="<?php echo esc_attr($args['option_name']); ?>" name="<?php echo esc_attr($args['option_name']); ?>" value="<?php echo esc_attr($option); ?>" />
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    public function render_server_info_field() {
        $site_url = site_url();
        $wp_path = ABSPATH;
        ?>
        <p><strong>Recommended Frequency:</strong> Run the cron once every hour. The plugin checks 75 minutes ahead to ensure no overlaps.</p>

        <h3>cPanel Setup</h3>
        <ol>
            <li>Log into cPanel and go to <strong>Cron Jobs</strong>.</li>
            <li>Set the cron job to run every hour (e.g., Minute: <code>0</code>, Hour: <code>*</code>). This ensures the plugin checks once per hour.</li>
            <li>In the Command field, you can use WP-CLI (if available):<br />
                <code>/usr/bin/wp --path=<?php echo esc_html(rtrim($wp_path, '/')); ?> cron event run newsletter_automated_send</code>
            </li>
            <li>If WP-CLI is not available, use cURL:<br />
                <code>0 * * * * curl -s <?php echo esc_url($site_url); ?>/wp-cron.php?doing_wp_cron &gt; /dev/null 2&gt;&1</code>
            </li>
            <li>Save the cron job.</li>
        </ol>

        <h3>SiteGround Setup (via Site Tools)</h3>
        <ol>
            <li>Log into Site Tools and go to <strong>Dev</strong> > <strong>Cron Jobs</strong>.</li>
            <li>Set the cron job to run every hour by selecting the appropriate schedule.</li>
            <li>Enter the WP-CLI or cURL command as shown above.</li>
            <li>Save the cron job.</li>
        </ol>

        <p><strong>Note:</strong> Ensure you do not enable WP-Cron or Pageview Triggered Cron if you are using a server-side cron. The external cron job now handles the schedule, checking every hour and looking ahead 75 minutes to avoid duplicates and capture all newsletters.</p>
        <?php
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Newsletter Cron Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('newsletter_cron_settings_group');
                do_settings_sections('newsletter-cron-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new Newsletter_Cron_Settings();
