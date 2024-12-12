<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$newsletter_list = get_option('newsletter_list', []);
$wp_timezone = get_option('timezone_string') ? get_option('timezone_string') : 'UTC';
date_default_timezone_set($wp_timezone);

?>
<div class="wrap">
    <h1><?php esc_html_e('Newsletter Dashboard', 'newsletter'); ?></h1>
    <p><?php esc_html_e('Below is a list of all newsletters and their scheduling details.', 'newsletter'); ?></p>

    <style>
        .newsletter-dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .newsletter-dashboard-table th,
        .newsletter-dashboard-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .newsletter-dashboard-table th {
            background: #f9f9f9;
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
        .newsletter-table-header {
            margin-top: 30px;
            font-size: 1.3em;
            margin-bottom: 10px;
        }
        .newsletter-schedule-info {
            font-size: 14px;
            color: #666;
        }
        .newsletter-name {
            font-weight: 600;
            font-size: 14px;
        }
        .newsletter-actions a.button {
            margin-right: 5px;
        }
    </style>

    <?php if (empty($newsletter_list)): ?>
        <p><?php esc_html_e('No newsletters found.', 'newsletter'); ?></p>
    <?php else: ?>
        <table class="widefat striped newsletter-dashboard-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Newsletter Name', 'newsletter'); ?></th>
                    <th><?php esc_html_e('Scheduling Type', 'newsletter'); ?></th>
                    <th><?php esc_html_e('Scheduled Days & Time', 'newsletter'); ?></th>
                    <th><?php esc_html_e('Next Scheduled Send', 'newsletter'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($newsletter_list as $newsletter_slug => $newsletter_name):
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

                            // Find the next closest day/time in the future
                            // We'll cycle through the next 14 days to find a match
                            for ($i=0; $i<14; $i++) {
                                $check = clone $now;
                                $check->modify("+{$i} day");
                                $day_str = strtolower($check->format('l')); // 'monday', 'tuesday', etc.
                                if (in_array($day_str, $send_days)) {
                                    // Check time
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

                            if ($next_send) {
                                $next_scheduled = $next_send->format('F j, Y g:i a');
                            } else {
                                $next_scheduled = esc_html__('Not scheduled soon.', 'newsletter');
                            }

                        } else {
                            $schedule_status = '<span class="newsletter-status not-scheduled">'.esc_html__('Not Scheduled', 'newsletter').'</span>';
                            $scheduled_pattern = esc_html__('No days/time selected.', 'newsletter');
                            $next_scheduled = esc_html__('Not scheduled.', 'newsletter');
                        }
                    }
                    ?>
                    <tr>
                        <td class="newsletter-name">
                            <?php echo esc_html($newsletter_name); ?>
                        </td>
                        <td>
                            <?php echo $schedule_status; ?>
                        </td>
                        <td>
                            <?php echo esc_html($scheduled_pattern); ?>
                        </td>
                        <td>
                            <?php echo esc_html($next_scheduled); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="description"><?php esc_html_e('Manage each newsletter\'s settings, schedule and content via their respective pages.', 'newsletter'); ?></p>
    <?php endif; ?>
</div>
