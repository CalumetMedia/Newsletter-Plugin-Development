(function($) {
    window.setupScheduleHandler = function() {
        $('#schedule-campaign').off('click').on('click', function() {
            var confirmSchedule = confirm("Are you sure you want to schedule the campaign?");
            if (!confirmSchedule) {
                return;
            }

            // Get current time and round up to next 15-minute interval
            var now = new Date();
            var minutes = now.getMinutes();
            var roundedMinutes = Math.ceil((minutes + 10) / 15) * 15;
            now.setMinutes(roundedMinutes);
            now.setSeconds(0);

            // Format as local time string: YYYY-MM-DD HH:MM:SS
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, '0');
            var day = String(now.getDate()).padStart(2, '0');
            var hours = String(now.getHours()).padStart(2, '0');
            var mins = String(now.getMinutes()).padStart(2, '0');
            var localFormattedTime = year + '-' + month + '-' + day + ' ' + hours + ':' + mins + ':00';

            console.log('Scheduling for local time:', now.toString());
            console.log('Local formatted time being sent:', localFormattedTime);

            $.ajax({
                url: newsletterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'schedule_mailchimp_campaign',
                    security: newsletterData.nonceMailchimp,
                    newsletter_slug: newsletterData.newsletterSlug,
                    schedule_datetime: localFormattedTime,
                    debug_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    debug_offset: -(new Date().getTimezoneOffset())
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || "Campaign scheduled successfully.");
                        location.reload();
                    } else {
                        alert("Error scheduling campaign: " + (response.data ? response.data : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Schedule error:', {xhr: xhr, status: status, error: error});
                    alert("Ajax error scheduling campaign: " + error);
                }
            });
        });
    };

    $(document).ready(function() {
        setupScheduleHandler();
    });

})(jQuery);
