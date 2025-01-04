<?php
// Check if accessed directly
if (!defined('ABSPATH')) exit;

function cnp_settings_page() {
    // Retrieve existing newsletter IDs
    $newsletter_ids = get_option('newsletter_ids', []);

    // Handle form submission for adding, editing, or deleting newsletters
    if (isset($_POST['save_settings'])) {
        // Mailchimp settings update
        update_option('mailchimp_api_key', sanitize_text_field($_POST['mailchimp_api_key']));
        update_option('mailchimp_list_id', sanitize_text_field($_POST['mailchimp_list_id']));

        // Adding a new newsletter
        if (!empty($_POST['new_newsletter_name'])) {
            $new_id = uniqid('newsletter_');
            $newsletter_ids[] = $new_id;
            update_option("newsletter_{$new_id}_name", sanitize_text_field($_POST['new_newsletter_name']));
            update_option("newsletter_{$new_id}_segment_id", sanitize_text_field($_POST['new_newsletter_segment_id']));
            update_option("newsletter_{$new_id}_reply_to", sanitize_email($_POST['new_newsletter_reply_to']));
            update_option("newsletter_{$new_id}_is_ad_hoc", !empty($_POST['new_newsletter_is_ad_hoc']) ? 1 : 0);
            update_option("newsletter_{$new_id}_send_days", $_POST['new_newsletter_send_days'] ?? []);
            update_option("newsletter_{$new_id}_send_time", sanitize_text_field($_POST['new_newsletter_send_time']));
            update_option("newsletter_{$new_id}_categories", array_map('intval', $_POST['new_newsletter_categories'] ?? []));
            update_option('newsletter_ids', $newsletter_ids);
            echo '<div class="updated"><p>New newsletter added successfully.</p></div>';
        }

        // Updates to existing newsletters
        foreach ($newsletter_ids as $newsletter_id) {
            if (isset($_POST['newsletters'][$newsletter_id])) {
                $settings = $_POST['newsletters'][$newsletter_id];
                update_option("newsletter_{$newsletter_id}_name", sanitize_text_field($settings['name']));
                update_option("newsletter_{$newsletter_id}_segment_id", sanitize_text_field($settings['segment_id']));
                update_option("newsletter_{$newsletter_id}_reply_to", sanitize_email($settings['reply_to']));
                update_option("newsletter_{$newsletter_id}_is_ad_hoc", !empty($settings['is_ad_hoc']) ? 1 : 0);
                update_option("newsletter_{$newsletter_id}_send_days", $settings['send_days'] ?? []);
                update_option("newsletter_{$newsletter_id}_send_time", sanitize_text_field($settings['send_time']));
                update_option("newsletter_{$newsletter_id}_categories", array_map('intval', $settings['categories'] ?? []));
            }
        }

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Handle deletion of newsletters
    if (isset($_POST['delete_newsletter']) && check_admin_referer('delete_newsletter_action', 'delete_newsletter_nonce')) {
        $newsletter_id_to_delete = sanitize_text_field($_POST['delete_newsletter']);
        $newsletter_ids = get_option('newsletter_ids', []);
        if (($key = array_search($newsletter_id_to_delete, $newsletter_ids)) !== false) {
            unset($newsletter_ids[$key]);
            delete_option("newsletter_{$newsletter_id_to_delete}_name");
            delete_option("newsletter_{$newsletter_id_to_delete}_segment_id");
            delete_option("newsletter_{$newsletter_id_to_delete}_reply_to");
            delete_option("newsletter_{$newsletter_id_to_delete}_is_ad_hoc");
            delete_option("newsletter_{$newsletter_id_to_delete}_send_days");
            delete_option("newsletter_{$newsletter_id_to_delete}_send_time");
            delete_option("newsletter_{$newsletter_id_to_delete}_categories");
            update_option('newsletter_ids', array_values($newsletter_ids));
            echo '<div class="updated"><p>Newsletter deleted successfully.</p></div>';
        }
    }

    // Retrieve Mailchimp settings
    $mailchimp_api_key = get_option('mailchimp_api_key', '');
    $mailchimp_list_id = get_option('mailchimp_list_id', '');

    // Display tab navigation
    echo '<div class="wrap"><h1>Newsletter Automator Settings</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    
    // Mailchimp tab
    echo '<a href="#mailchimp-tab" class="nav-tab nav-tab-active" onclick="showTab(event, \'mailchimp-tab\')">Mailchimp</a>';

    // + ADD tab for adding a new newsletter
    echo '<a href="#add-newsletter-tab" class="nav-tab add-tab" onclick="showTab(event, \'add-newsletter-tab\')">+ ADD</a>';

    // Tabs for existing newsletters
    foreach ($newsletter_ids as $newsletter_id) {
        $newsletter_name = get_option("newsletter_{$newsletter_id}_name", 'Unnamed Newsletter');
        echo '<a href="#' . esc_attr($newsletter_id) . '-tab" class="nav-tab" onclick="showTab(event, \'' . esc_attr($newsletter_id) . '-tab\')">' . esc_html($newsletter_name) . '</a>';
    }

    // Delete tab
    echo '<a href="#delete-newsletter-tab" class="nav-tab delete-tab" onclick="showTab(event, \'delete-newsletter-tab\')">- DELETE</a>';
    echo '</h2>';

    // Begin form for adding/editing newsletters
    echo '<form method="POST" id="settings-form">';
    
    // Mailchimp settings tab
    echo '<div id="mailchimp-tab" class="tab-content" style="display: block;">';
    echo '<h3>Mailchimp Settings</h3>';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Mailchimp API Key</th><td><input type="text" name="mailchimp_api_key" value="' . esc_attr($mailchimp_api_key) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Mailchimp List ID</th><td><input type="text" name="mailchimp_list_id" value="' . esc_attr($mailchimp_list_id) . '" class="regular-text"></td></tr>';
    echo '</table>';
    echo '</div>';

    // + ADD tab content for adding a new newsletter
    echo '<div id="add-newsletter-tab" class="tab-content" style="display: none;">';
    echo '<h3>Add New Newsletter</h3>';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Newsletter Name</th><td><input type="text" name="new_newsletter_name" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Segment ID</th><td><input type="text" name="new_newsletter_segment_id" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Reply-to Email</th><td><input type="email" name="new_newsletter_reply_to" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Ad Hoc</th><td><input type="checkbox" name="new_newsletter_is_ad_hoc" onclick="toggleScheduleFields(this, \'new_newsletter\')"> Enable Ad Hoc</td></tr>';
    echo '<tr id="send-days-row-new_newsletter"><th scope="row">Send Days</th><td>';
    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
        echo '<label><input type="checkbox" name="new_newsletter_send_days[]" value="' . esc_attr($day) . '"> ' . $day . '</label><br>';
    }
    echo '</td></tr>';
    echo '<tr id="send-time-row-new_newsletter"><th scope="row">Send Time</th><td><input type="time" name="new_newsletter_send_time" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Category Filter</th><td>';
    $categories = get_categories();
    foreach ($categories as $category) {
        echo '<label><input type="checkbox" name="new_newsletter_categories[]" value="' . esc_attr($category->term_id) . '"> ' . esc_html($category->name) . '</label><br>';
    }
    echo '</td></tr>';
    echo '</table>';
    echo '</div>';

    // Existing newsletter tabs for editing
    foreach ($newsletter_ids as $newsletter_id) {
        $newsletter_name = get_option("newsletter_{$newsletter_id}_name", 'Unnamed Newsletter');
        echo '<div id="' . esc_attr($newsletter_id) . '-tab" class="tab-content" style="display: none;">';
        echo '<h3>Edit ' . esc_html($newsletter_name) . '</h3>';
        echo '<table class="form-table">';
        echo '<tr><th scope="row">Newsletter Name</th><td><input type="text" name="newsletters[' . esc_attr($newsletter_id) . '][name]" value="' . esc_attr($newsletter_name) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Segment ID</th><td><input type="text" name="newsletters[' . esc_attr($newsletter_id) . '][segment_id]" value="' . esc_attr(get_option("newsletter_{$newsletter_id}_segment_id")) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Reply-to Email</th><td><input type="email" name="newsletters[' . esc_attr($newsletter_id) . '][reply_to]" value="' . esc_attr(get_option("newsletter_{$newsletter_id}_reply_to")) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Ad Hoc</th><td><input type="checkbox" name="newsletters[' . esc_attr($newsletter_id) . '][is_ad_hoc]" ' . checked(1, get_option("newsletter_{$newsletter_id}_is_ad_hoc"), false) . ' onclick="toggleScheduleFields(this, \'' . esc_attr($newsletter_id) . '\')"> Enable Ad Hoc</td></tr>';
        echo '<tr id="send-days-row-' . esc_attr($newsletter_id) . '"><th scope="row">Send Days</th><td>';
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $checked = in_array($day, get_option("newsletter_{$newsletter_id}_send_days", [])) ? 'checked' : '';
            echo '<label><input type="checkbox" name="newsletters[' . esc_attr($newsletter_id) . '][send_days][]" value="' . esc_attr($day) . '" ' . $checked . '> ' . $day . '</label><br>';
        }
        echo '</td></tr>';
        echo '<tr id="send-time-row-' . esc_attr($newsletter_id) . '"><th scope="row">Send Time</th><td><input type="time" name="newsletters[' . esc_attr($newsletter_id) . '][send_time]" value="' . esc_attr(get_option("newsletter_{$newsletter_id}_send_time")) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Category Filter</th><td>';
        $categories = get_categories();
        $selected_categories = get_option("newsletter_{$newsletter_id}_categories", []);
        foreach ($categories as $category) {
            $selected = in_array($category->term_id, $selected_categories) ? 'checked' : '';
            echo '<label><input type="checkbox" name="newsletters[' . esc_attr($newsletter_id) . '][categories][]" value="' . esc_attr($category->term_id) . '" ' . $selected . '> ' . esc_html($category->name) . '</label><br>';
        }
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';
    }

    // Delete tab with list of newsletters and delete buttons
    echo '<div id="delete-newsletter-tab" class="tab-content" style="display: none;">';
    echo '<h3>Delete Newsletters</h3>';
    wp_nonce_field('delete_newsletter_action', 'delete_newsletter_nonce');
    echo '<table class="form-table"><tbody>';
    foreach ($newsletter_ids as $newsletter_id) {
        $newsletter_name = get_option("newsletter_{$newsletter_id}_name", 'Unnamed Newsletter');
        echo '<tr><td>' . esc_html($newsletter_name) . '</td>';
        echo '<td><button type="submit" name="delete_newsletter" value="' . esc_attr($newsletter_id) . '" class="button button-danger" onclick="return confirm(\'Are you sure you want to delete this newsletter?\');">Delete</button></td></tr>';
    }
    echo '</tbody></table>';
    echo '</div>';

    // Save Settings button
    echo '<p class="submit">';
    echo '<input type="submit" name="save_settings" class="button-primary" value="Save Settings">';
    echo '</p>';

    echo '</form></div>'; // Close form and wrap div

    // JavaScript for Tab Navigation, Ad Hoc Toggle, and Persistent Active Tab
    echo '<script>
        function showTab(event, tabId) {
            event.preventDefault();
            document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
            document.querySelectorAll(".nav-tab").forEach(tab => tab.classList.remove("nav-tab-active"));
            document.getElementById(tabId).style.display = "block";
            event.currentTarget.classList.add("nav-tab-active");

            // Store active tab in local storage to persist after form submission
            localStorage.setItem("activeTab", tabId);
        }

        function toggleScheduleFields(checkbox, newsletterId) {
            const isChecked = checkbox.checked;
            document.getElementById("send-days-row-" + newsletterId).style.display = isChecked ? "none" : "table-row";
            document.getElementById("send-time-row-" + newsletterId).style.display = isChecked ? "none" : "table-row";
        }

        // Check local storage for active tab and set it on page load
        document.addEventListener("DOMContentLoaded", function() {
            const activeTab = localStorage.getItem("activeTab");
            if (activeTab) {
                document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
                document.querySelectorAll(".nav-tab").forEach(tab => tab.classList.remove("nav-tab-active"));
                document.getElementById(activeTab).style.display = "block";
                document.querySelector("[href=\'#" + activeTab + "\']").classList.add("nav-tab-active");
            }
        });
    </script>';

    // Styling for tabs
    echo '<style>
        .nav-tab.add-tab {
            background-color: #28a745; /* Flat green for add */
            color: #fff;
        }
        .nav-tab.delete-tab {
            background-color: #dc3545; /* Flat red for delete */
            color: #fff;
        }
        .nav-tab {
            color: #000;
        }
        .nav-tab-active {
            color: #007cba;
        }
    </style>';
}
