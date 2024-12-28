<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Ensure only authorized users can access
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get all newsletters
$newsletter_list = get_option('newsletter_list', []);

// If coming from add newsletter, force refresh the list
if (isset($_POST['add_newsletter_submit'])) {
    $newsletter_list = get_option('newsletter_list', [], true); // Force refresh
}

// Determine active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Define the tabs
$tabs = [
    'dashboard'      => __('Dashboard', 'newsletter'),
    'add_newsletter' => __('Add Newsletter', 'newsletter'),
];

// Add each newsletter as a tab
foreach ($newsletter_list as $id => $name) {
    $tabs[$id] = $name;
}

$tabs['delete_newsletter'] = __('Delete Newsletter', 'newsletter');
?>
<div class="wrap">
    <h1><?php esc_html_e('Newsletter Settings', 'newsletter'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <?php
        foreach ($tabs as $tab_key => $tab_name) {
            $tab_url = admin_url('admin.php?page=newsletter-settings&tab=' . $tab_key);
            $active = $active_tab === $tab_key ? 'nav-tab-active' : '';
            
            // Style handling for special tabs
            $special_class = '';
            $tab_text = $tab_name;
            
            if ($tab_key === 'add_newsletter') {
                $special_class = 'add-tab';
                $tab_text = '<span style="background-color:green; color:white; padding:2px 5px; border-radius:3px;">&#43;</span> ' . $tab_name;
            } elseif ($tab_key === 'delete_newsletter') {
                $special_class = 'delete-tab';
                $tab_text = '<span style="background-color:red; color:white; padding:2px 5px; border-radius:3px;">&#8722;</span> ' . $tab_name;
            } elseif ($tab_key === 'dashboard') {
                $special_class = 'dashboard-tab';
                $tab_text = '<span style="background-color:#007cba; color:white; padding:2px 5px; border-radius:3px;">&#128200;</span> ' . $tab_name;
            }

            echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . esc_attr($active) . ' ' . esc_attr($special_class) . '">' . wp_kses_post($tab_text) . '</a>';
        }
        ?>
    </h2>

    <?php
    // Include the content for the active tab
    switch ($active_tab) {
        case 'dashboard':
            // Move the old dashboard logic into a separate partial:
            include plugin_dir_path(__FILE__) . 'partials/dashboard.php';
            break;

        case 'add_newsletter':
            include plugin_dir_path(__FILE__) . 'add-newsletter.php';
            break;

        case 'delete_newsletter':
            include plugin_dir_path(__FILE__) . 'delete-newsletter.php';
            break;

        default:
            // If the tab matches a newsletter ID:
            if (isset($newsletter_list[$active_tab])) {
                $newsletter_slug = $active_tab; // Pass the slug directly
                include plugin_dir_path(__FILE__) . 'individual-settings.php';
            } else {
                echo '<p>' . esc_html__('Invalid tab selected.', 'newsletter') . '</p>';
            }
            break;
    }
    ?>
</div>
