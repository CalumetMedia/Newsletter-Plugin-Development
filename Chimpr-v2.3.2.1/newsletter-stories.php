<?php if ($next_scheduled_timestamp): ?>
    <button type="button" 
            class="button button-large action-button schedule-button" 
            id="schedule-campaign" 
            data-timestamp="<?php echo esc_attr((int)$next_scheduled_timestamp); ?>"
            data-formatted-time="<?php echo esc_attr(wp_date('F j, Y g:i a', $next_scheduled_timestamp)); ?>">
        <span class="dashicons dashicons-calendar" style="vertical-align: middle; margin-right:5px;"></span>
        <strong><?php esc_html_e('SCHEDULE', 'newsletter'); ?></strong>
    </button>
<?php endif; ?> 