<?php
if (!defined('ABSPATH')) exit;

class Newsletter_Cron_Settings {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        // Removed newsletter_use_wp_cron references entirely

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
                echo '<p>Configure how and when automated newsletter sends are checked.</p>';
            },
            'newsletter-cron-settings'
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
                'description' => 'If enabled, scheduling checks are triggered after a certain number of pageviews.'
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
                'description' => 'Number of pageviews required to trigger a scheduling check (if pageview cron is enabled).'
            )
        );

        add_settings_section(
            'newsletter_cron_server_section',
            'Server-Side Cron Setup (Recommended)',
            function() {
                echo '<p>Use a server-side cron job for better performance. The plugin checks ahead to prevent duplicates.</p>';
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
        <p><strong>Recommended Frequency:</strong> Run the cron once every hour.</p>
        <p>Example WP-CLI command:</p>
        <code>/usr/bin/wp --path=<?php echo esc_html(rtrim($wp_path, '/')); ?> cron event run newsletter_automated_send</code>

        <p>Or using cURL:</p>
        <code>curl -s <?php echo esc_url($site_url); ?>/wp-cron.php?doing_wp_cron &gt; /dev/null 2&gt;&1</code>
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
