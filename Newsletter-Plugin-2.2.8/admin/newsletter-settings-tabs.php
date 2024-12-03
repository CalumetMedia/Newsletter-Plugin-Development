<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Ensure only authorized users can access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get all newsletters
$newsletter_list = get_option('newsletter_list', []);

// Determine active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'mailchimp';

// Define the tabs
$tabs = [
    'mailchimp' => __('Mailchimp Settings', 'newsletter'),
    'templates' => __('Templates', 'newsletter'),
    'add_newsletter' => __('Add Newsletter', 'newsletter'),
];

foreach ($newsletter_list as $id => $name) {
    $tabs[$id] = $name;
}

$tabs['delete_newsletter'] = __('Delete Newsletter', 'newsletter');

// Output the tabs
?>
<div class="wrap">
    <h1><?php esc_html_e('Newsletter Settings', 'newsletter'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <?php
        foreach ($tabs as $tab_key => $tab_name) {
            $tab_url = admin_url('admin.php?page=newsletter-settings&tab=' . $tab_key);
            $active = $active_tab === $tab_key ? 'nav-tab-active' : '';

            // Add styles for Add, Delete, and Templates tabs
            if ($tab_key === 'add_newsletter') {
                $tab_name = '<span style="background-color:green; color:white; padding:2px 5px; border-radius:3px;">&#43;</span> ' . $tab_name;
            } elseif ($tab_key === 'delete_newsletter') {
                $tab_name = '<span style="background-color:red; color:white; padding:2px 5px; border-radius:3px;">&#8722;</span> ' . $tab_name;
            } elseif ($tab_key === 'mailchimp') {
                $image_url = plugin_dir_url(dirname(__FILE__)) . 'assets/images/mailchimp-logo.webp';
                $tab_name = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Mailchimp Settings', 'newsletter') . '" style="height: 20px; vertical-align: middle; margin-right: 5px;">';
            } elseif ($tab_key === 'templates') {
                $tab_name = '<span style="background-color:black; color:white; padding:2px 5px; border-radius:3px;">&#9998;</span> ' . $tab_name;
            }

            echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . esc_attr($active) . '">' . wp_kses_post($tab_name) . '</a>';
        }
        ?>
    </h2>

    <?php
    // Include the content for the active tab
    switch ($active_tab) {
        case 'mailchimp':
            include plugin_dir_path(__FILE__) . 'mailchimp-settings.php';
            break;
        case 'templates':
            include plugin_dir_path(__FILE__) . 'templates.php';
            break;
        case 'add_newsletter':
            include plugin_dir_path(__FILE__) . 'add-newsletter.php';
            break;
        case 'delete_newsletter':
            include plugin_dir_path(__FILE__) . 'delete-newsletter.php';
            break;
        default:
            // If the tab key matches a newsletter ID, load the individual settings
            if (isset($newsletter_list[$active_tab])) {
                $newsletter_id = $active_tab;
                include plugin_dir_path(__FILE__) . 'individual-settings.php';
            } else {
                echo '<p>' . esc_html__('Invalid tab selected.', 'newsletter') . '</p>';
            }
            break;
    }
    ?>
</div>