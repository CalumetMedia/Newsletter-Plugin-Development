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
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Define the tabs
$tabs = [
    'dashboard'      => __('Dashboard', 'newsletter'),
    'add_newsletter' => __('Add Newsletter', 'newsletter'),
];

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

            // Add styles for Add and Delete tabs
            if ($tab_key === 'add_newsletter') {
                $tab_name = '<span style="background-color:green; color:white; padding:2px 5px; border-radius:3px;">&#43;</span> ' . $tab_name;
            } elseif ($tab_key === 'delete_newsletter') {
                $tab_name = '<span style="background-color:red; color:white; padding:2px 5px; border-radius:3px;">&#8722;</span> ' . $tab_name;
            } elseif ($tab_key === 'dashboard') {
                $tab_name = '<span style="background-color:#007cba; color:white; padding:2px 5px; border-radius:3px;">&#128200;</span> ' . $tab_name;
            }

            echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . esc_attr($active) . '">' . wp_kses_post($tab_name) . '</a>';
        }
        ?>
    </h2>

    <?php
    // Include the content for the active tab
    switch ($active_tab) {
        case 'dashboard':
            // Include dashboard code here
            // We use similar code from the previous dashboard you requested
            $wp_timezone = get_option('timezone_string') ? get_option('timezone_string') : 'UTC';
            date_default_timezone_set($wp_timezone);

            if (empty($newsletter_list)) {
                echo '<p>' . esc_html__('No newsletters found.', 'newsletter') . '</p>';
            } else {
                ?>
                <style>
                .newsletter-dashboard-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                .newsletter-dashboard-table th,
                .newsletter-dashboard-table td {
                    padding: 10px;
                    border-bottom: 1px solid #eee;
                }
                .newsletter-dashboard-table th {
                    background: #f9f9f9;
                    text-align: left;
                }
                .newsletter-status {
                    display: inline-block;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .newsletter-status.schedule {
                    background: #28a745;
                    color: #fff;
                }
                .newsletter-status.adhoc {
                    background: #6c757d;
                    color: #fff;
                }
                .newsletter-status.not-scheduled {
                    background: #ffc107;
                    color: #000;
                }
                .newsletter-name {
                    font-weight: 600;
                    font-size: 14px;
                }
                </style>
                <?php

                echo '<table class="widefat striped newsletter-dashboard-table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>' . esc_html__('Newsletter Name', 'newsletter') . '</th>';
                echo '<th>' . esc_html__('Scheduling Type', 'newsletter') . '</th>';
                echo '<th>' . esc_html__('Scheduled Days & Time', 'newsletter') . '</th>';
                echo '<th>' . esc_html__('Next Scheduled Send', 'newsletter') . '</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                foreach ($newsletter_list as $newsletter_slug => $newsletter_name) {
                    $is_ad_hoc = get_option("newsletter_is_ad_hoc_{$newsletter_slug}", 0);
                    $send_days = get_option("newsletter_send_days_{$newsletter_slug}", []);
                    $send_time = get_option("newsletter_send_time_{$newsletter_slug}", '');

                    // Determine scheduling status
                    if ($is_ad_hoc) {
                        $schedule_status = '<span class="newsletter-status adhoc">'.esc_html__('Ad Hoc', 'newsletter').'</span>';
                        $scheduled_pattern = esc_html__('No automatic send times.', 'newsletter');
                        $next_scheduled = esc_html__('Ad hoc only.', 'newsletter');
                    } else {
                        if (!empty($send_days) && !empty($send_time)) {
                            $schedule_status = '<span class="newsletter-status schedule">'.esc_html__('Scheduled', 'newsletter').'</span>';

                            // Format send_days nicely
                            $days_map = [
                                'monday'    => __('Monday', 'newsletter'),
                                'tuesday'   => __('Tuesday', 'newsletter'),
                                'wednesday' => __('Wednesday', 'newsletter'),
                                'thursday'  => __('Thursday', 'newsletter'),
                                'friday'    => __('Friday', 'newsletter'),
                                'saturday'  => __('Saturday', 'newsletter'),
                                'sunday'    => __('Sunday', 'newsletter'),
                            ];
                            $day_names = array_map(function($d) use ($days_map) {
                                return isset($days_map[$d]) ? $days_map[$d] : ucfirst($d);
                            }, $send_days);

                            $scheduled_pattern = sprintf(
                                __('Every %s at %s', 'newsletter'),
                                implode(', ', $day_names),
                                date('g:i a', strtotime($send_time))
                            );

                            // Determine next scheduled date
                            $now = new DateTime('now', new DateTimeZone($wp_timezone));
                            $next_send = null;

                            // Find the next upcoming day/time
                            for ($i=0; $i<14; $i++) {
                                $check = clone $now;
                                $check->modify("+{$i} day");
                                $day_str = strtolower($check->format('l'));
                                if (in_array($day_str, $send_days, true)) {
                                    $time_parts = explode(':', $send_time);
                                    if (count($time_parts) === 2) {
                                        $check->setTime((int)$time_parts[0], (int)$time_parts[1], 0);
                                        if ($check > $now) {
                                            $next_send = $check;
                                            break;
                                        }
                                    }
                                }
                            }

                            $next_scheduled = $next_send ? $next_send->format('F j, Y g:i a') : esc_html__('Not scheduled soon.', 'newsletter');
                        } else {
                            $schedule_status = '<span class="newsletter-status not-scheduled">'.esc_html__('Not Scheduled', 'newsletter').'</span>';
                            $scheduled_pattern = esc_html__('No days/time selected.', 'newsletter');
                            $next_scheduled = esc_html__('Not scheduled.', 'newsletter');
                        }
                    }

                    echo '<tr>';
                    echo '<td class="newsletter-name">' . esc_html($newsletter_name) . '</td>';
                    echo '<td>' . $schedule_status . '</td>';
                    echo '<td>' . esc_html($scheduled_pattern) . '</td>';
                    echo '<td>' . esc_html($next_scheduled) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            }
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
