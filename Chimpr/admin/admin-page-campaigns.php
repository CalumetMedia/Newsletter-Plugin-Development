<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function np_render_campaigns_page() {
    $mailchimp_api = new Newsletter_Mailchimp_API();
    
    // Pagination settings
    $items_per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Handle Filtering
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

    // Handle Sorting
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
    $valid_order_values = array('desc', 'asc');
    if (!in_array($order, $valid_order_values, true)) {
        $order = 'desc';
    }

    // Get campaigns based on filter
    if ($filter_status) {
        // If filtering, just get that status
        $response = $mailchimp_api->get_campaigns($offset, $items_per_page, $filter_status);
        $active_campaigns = is_wp_error($response) ? [] : $response['campaigns'];
        $sent_campaigns = ($filter_status === 'sent') ? $active_campaigns : [];
        $active_campaigns = ($filter_status !== 'sent') ? $active_campaigns : [];
        
        $total_active = ($filter_status !== 'sent') ? $response['total_items'] : 0;
        $total_sent = ($filter_status === 'sent') ? $response['total_items'] : 0;
    } else {
        // If no filter, get both active and sent
        $active_response = $mailchimp_api->get_campaigns($offset, $items_per_page, 'save,schedule,paused');
        $sent_response = $mailchimp_api->get_campaigns($offset, $items_per_page, 'sent');
        
        $active_campaigns = is_wp_error($active_response) ? [] : $active_response['campaigns'];
        $sent_campaigns = is_wp_error($sent_response) ? [] : $sent_response['campaigns'];
        
        $total_active = is_wp_error($active_response) ? 0 : $active_response['total_items'];
        $total_sent = is_wp_error($sent_response) ? 0 : $sent_response['total_items'];
    }

    $total_active_pages = ceil($total_active / $items_per_page);
    $total_sent_pages = ceil($total_sent / $items_per_page);

    // Sort function remains the same
    $sort_func = function($a, $b) use ($orderby, $order) {
        if (!$orderby) {
            $valA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
            $valB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
        } else {
            switch ($orderby) {
                case 'title':
                    $valA = isset($a['settings']['title']) ? $a['settings']['title'] : '';
                    $valB = isset($b['settings']['title']) ? $b['settings']['title'] : '';
                    break;
                case 'subject':
                    $valA = isset($a['settings']['subject_line']) ? $a['settings']['subject_line'] : '';
                    $valB = isset($b['settings']['subject_line']) ? $b['settings']['subject_line'] : '';
                    break;
                case 'created':
                    $valA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                    $valB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                    break;
                case 'scheduled':
                    $valA = isset($a['send_time']) ? strtotime($a['send_time']) : 0;
                    $valB = isset($b['send_time']) ? strtotime($b['send_time']) : 0;
                    break;
                default:
                    $valA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                    $valB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                    break;
            }
        }

        if (is_numeric($valA) && is_numeric($valB)) {
            $result = $valA - $valB;
        } else {
            $result = strcmp($valA, $valB);
        }

        return ($order === 'asc') ? $result : -$result;
    };

    if (!empty($active_campaigns)) {
        usort($active_campaigns, $sort_func);
    }
    if (!empty($sent_campaigns)) {
        usort($sent_campaigns, $sort_func);
    }

    function get_status_label($status) {
        switch ($status) {
            case 'save':
                return '<span style="background:yellow;color:black;padding:2px 4px;border-radius:3px;">Draft</span>';
            case 'schedule':
                return '<span style="background:green;color:white;padding:2px 4px;border-radius:3px;">Scheduled</span>';
            case 'paused':
                return '<span style="background:orange;color:white;padding:2px 4px;border-radius:3px;">Paused</span>';
            default:
                return esc_html(ucfirst($status));
        }
    }

    function format_wp_time($time_str) {
        if (empty($time_str) || $time_str === '-001-11-30T00:00:00+00:00') {
            return '-';
        }
        
        try {
            $dt = new DateTime($time_str, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(get_option('timezone_string')));
            return $dt->format('F j, Y') . '<br>' . $dt->format('g:i a');
        } catch (Exception $e) {
            return '-';
        }
    }

    function get_segment_name_or_id($mailchimp_api, $campaign) {
        if (!empty($campaign['recipients']['segment_opts']['saved_segment_id'])) {
            $segment_id = (int) $campaign['recipients']['segment_opts']['saved_segment_id'];
            $list_id = isset($campaign['recipients']['list_id']) ? $campaign['recipients']['list_id'] : '';
            if (!empty($list_id)) {
                $segment_name = $mailchimp_api->get_segment_name($list_id, $segment_id);
                if ($segment_name) {
                    return $segment_name;
                }
                return 'Segment ID: ' . $segment_id;
            }
            return 'Segment ID: ' . $segment_id;
        }
        return 'No Segment';
    }

    function render_pagination($total_pages, $current_page, $table_type = 'draft', $total_items = 0) {
        if ($total_pages <= 1) return;
        
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total_items, 'newsletter'), number_format_i18n($total_items)) . '</span>';
        echo '<span class="pagination-links">';
        
        $base_args = [
            'page' => isset($_REQUEST['page']) ? $_REQUEST['page'] : '',
            'filter_status' => isset($_GET['filter_status']) ? $_GET['filter_status'] : '',
            'orderby' => isset($_GET['orderby']) ? $_GET['orderby'] : '',
            'order' => isset($_GET['order']) ? $_GET['order'] : '',
            'table' => $table_type
        ];
        
        // First page link
        if ($current_page > 1) {
            echo '<a class="first-page button" href="' . esc_url(add_query_arg(array_merge($base_args, ['paged' => 1]))) . '">&laquo;</a>';
            echo '<a class="prev-page button" href="' . esc_url(add_query_arg(array_merge($base_args, ['paged' => max(1, $current_page - 1)]))) . '">&lsaquo;</a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        }

        echo '<span class="paging-input">';
        echo sprintf(
            '<span class="current-page">%s</span>
            <span class="tablenav-paging-text"> of 
            <span class="total-pages">%s</span></span>',
            $current_page,
            $total_pages
        );
        echo '</span>';

        // Next & last page links
        if ($current_page < $total_pages) {
            echo '<a class="next-page button" href="' . esc_url(add_query_arg(array_merge($base_args, ['paged' => min($total_pages, $current_page + 1)]))) . '">&rsaquo;</a>';
            echo '<a class="last-page button" href="' . esc_url(add_query_arg(array_merge($base_args, ['paged' => $total_pages]))) . '">&raquo;</a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        }
        
        echo '</span></div></div>';
    }

    function sort_link($column_key, $label) {
        $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
        $current_order = isset($_GET['order']) ? $_GET['order'] : 'asc';
        $new_order = ($current_orderby === $column_key && $current_order === 'asc') ? 'desc' : 'asc';

        $base_args = [
            'page' => isset($_REQUEST['page']) ? $_REQUEST['page'] : '',
            'filter_status' => isset($_GET['filter_status']) ? $_GET['filter_status'] : '',
            'paged' => 1,
            'orderby' => $column_key,
            'order' => $new_order
        ];

        $arrow = '';
        if ($current_orderby === $column_key) {
            $arrow = $current_order === 'asc' ? ' ↑' : ' ↓';
        }

        $url = esc_url(add_query_arg($base_args, admin_url('admin.php')));
        return '<a href="' . $url . '">' . esc_html($label) . $arrow . '</a>';
    }

    echo '<div class="wrap">';
    echo '<h1>Campaign Dashboard</h1>';
    echo '<form method="get" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '">';
    echo '<label for="filter_status"><strong>Filter by Status:</strong></label> ';
    echo '<select name="filter_status" id="filter_status" style="margin-left:5px;">';
    echo '<option value="">All Statuses</option>';
    $statuses = ['save' => 'Draft', 'schedule' => 'Scheduled', 'paused' => 'Paused', 'sent' => 'Sent'];
    foreach ($statuses as $val => $display) {
        echo '<option value="' . esc_attr($val) . '" ' . selected($filter_status, $val, false) . '>' . esc_html($display) . '</option>';
    }
    echo '</select> ';
    echo '<input type="submit" class="button" value="Filter">';
    echo '</form>';

    // Draft, Scheduled & Paused Table
    if (!empty($active_campaigns)) {
        echo '<h2>Draft, Scheduled & Paused Newsletters</h2>';
        echo '<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width:100px;">Status</th>';
        echo '<th>' . sort_link('title', 'Title') . '</th>';
        echo '<th>' . sort_link('subject', 'Subject') . '</th>';
        echo '<th>' . sort_link('created', 'Created At') . '</th>';
        echo '<th>' . sort_link('scheduled', 'Scheduled Time') . '</th>';
        echo '<th>Segment Tags</th>';
        echo '<th style="width:160px;">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($active_campaigns as $c) {
            $status_label = get_status_label($c['status']);
            $create_time_str = format_wp_time($c['create_time']);

            $scheduled_time_str = '-';
            if (!empty($c['settings']['send_time'])) {
                $scheduled_time_str = format_wp_time($c['settings']['send_time']);
            } elseif (!empty($c['send_time'])) {
                $scheduled_time_str = format_wp_time($c['send_time']);
            }

            $title = !empty($c['settings']['title']) ? $c['settings']['title'] : '';
            $subject = !empty($c['settings']['subject_line']) ? $c['settings']['subject_line'] : '';
            $segment_info = get_segment_name_or_id($mailchimp_api, $c);

            $datacenter = $mailchimp_api->get_datacenter();
            $edit_url = 'https://' . $datacenter . '.admin.mailchimp.com/campaigns/edit?id=' . urlencode($c['web_id']);

            if ($c['status'] === 'schedule') {
                $pause_and_edit_url = add_query_arg([
                    'page' => isset($_REQUEST['page']) ? $_REQUEST['page'] : '',
                    'action' => 'pause_and_edit',
                    'campaign_id' => $c['id'],
                    'web_id' => $c['web_id']
                ], admin_url('admin.php'));

                $edit_button = sprintf(
                    '<a class="button button-primary" href="#" onclick="if(confirm(\'This campaign is scheduled. Editing requires pausing first. Continue?\')){window.location.href=\'%s\';}return false;">Edit</a>',
                    esc_url($pause_and_edit_url)
                );
            } else {
                $edit_button = sprintf(
                    '<a class="button button-primary" href="%s" target="_blank">Edit</a>',
                    esc_url($edit_url)
                );
            }

            $delete_url = add_query_arg([
                'page' => isset($_REQUEST['page']) ? $_REQUEST['page'] : '',
                'action' => 'delete_campaign',
                'campaign_id' => $c['id']
            ], admin_url('admin.php'));

            echo '<tr>';
            echo '<td>' . $status_label . '</td>';
            echo '<td>' . esc_html($title) . '</td>';
            echo '<td>' . esc_html($subject) . '</td>';
            echo '<td>' . $create_time_str . '</td>';
            echo '<td>' . $scheduled_time_str . '</td>';
            echo '<td>' . esc_html($segment_info) . '</td>';
            echo '<td>';
            echo $edit_button . ' ';
            echo '<a class="button button-secondary" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        render_pagination($total_active_pages, $current_page, 'draft', $total_active);
    }

    // Sent Table
    if (!empty($sent_campaigns)) {
    echo '<h2>Sent Newsletters</h2>';
    echo '<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;table-layout:fixed;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width:110px;white-space:nowrap;text-align:center;">' . sort_link('scheduled', 'Send Time') . '</th>';
    echo '<th style="width:150px;white-space:nowrap;text-align:center;">' . sort_link('title', 'Title') . '</th>';
    echo '<th style="width:100px;white-space:nowrap;text-align:center;">' . sort_link('subject', 'Subject') . '</th>';
    echo '<th style="width:100px;white-space:nowrap;text-align:center;">Segment</th>';
    echo '<th style="width:50px;white-space:nowrap;text-align:center;">Sent</th>';
    echo '<th style="width:50px;white-space:nowrap;text-align:center;">Opens</th>';
    echo '<th style="width:50px;white-space:nowrap;text-align:center;">Open %</th>';
    echo '<th style="width:50px;white-space:nowrap;text-align:center;">Clicks</th>';
    echo '<th style="width:50px;white-space:nowrap;text-align:center;">Click %</th>';
    echo '<th style="width:120px;white-space:nowrap;text-align:center;">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($sent_campaigns as $c) {
        $send_time_str = '-';
        if (!empty($c['send_time'])) {
            $send_time_str = format_wp_time($c['send_time']);
        }

        $title = !empty($c['settings']['title']) ? $c['settings']['title'] : '';
        $subject = !empty($c['settings']['subject_line']) ? $c['settings']['subject_line'] : '';
        $segment_info = get_segment_name_or_id($mailchimp_api, $c);

        $archive_url = !empty($c['archive_url']) ? $c['archive_url'] : '';
        $emails_sent = !empty($c['emails_sent']) ? $c['emails_sent'] : 0;

        $opens = 0;
        $open_rate = 'N/A';
        $clicks = 0;
        $click_rate = 'N/A';

        if (!empty($c['report_summary'])) {
            $unique_opens = isset($c['report_summary']['unique_opens']) ? $c['report_summary']['unique_opens'] : 0;
            $open_rate_val = isset($c['report_summary']['open_rate']) ? round($c['report_summary']['open_rate'] * 100) . '%' : 'N/A';
            $clicks_val = isset($c['report_summary']['clicks']) ? $c['report_summary']['clicks'] : 0;
            $click_rate_val = isset($c['report_summary']['click_rate']) ? round($c['report_summary']['click_rate'] * 100) . '%' : 'N/A';

            $opens = $unique_opens;
            $open_rate = $open_rate_val;
            $clicks = $clicks_val;
            $click_rate = $click_rate_val;
        }

        $datacenter = $mailchimp_api->get_datacenter();
        $report_url = 'https://' . $datacenter . '.admin.mailchimp.com/reports/summary?id=' . urlencode($c['web_id']);

        echo '<tr style="text-align:center;">';
        echo '<td>' . $send_time_str . '</td>';
        echo '<td>' . esc_html($title) . '</td>';
        echo '<td>' . esc_html($subject) . '</td>';
        echo '<td>' . esc_html($segment_info) . '</td>';
        echo '<td>' . intval($emails_sent) . '</td>';
        echo '<td>' . intval($opens) . '</td>';
        echo '<td>' . esc_html($open_rate) . '</td>';
        echo '<td>' . intval($clicks) . '</td>';
        echo '<td>' . esc_html($click_rate) . '</td>';
        echo '<td>';
        if ($archive_url) {
            echo '<a class="button button-secondary" href="' . esc_url($archive_url) . '" target="_blank" style="margin-right:5px;">Archive</a>';
        }
        echo '<a class="button button-secondary" href="' . esc_url($report_url) . '" target="_blank">Reports</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    render_pagination($total_sent_pages, $current_page, 'sent', $total_sent);
}

    if (empty($active_campaigns) && empty($sent_campaigns)) {
        echo '<p>No campaigns found.</p>';
    }

    echo '</div>'; // Close .wrap
}
