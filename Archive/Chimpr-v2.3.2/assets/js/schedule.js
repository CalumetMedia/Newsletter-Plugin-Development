(function($) {
    function formatLocalDateTime(dateObj) {
        var year = dateObj.getFullYear();
        var month = String(dateObj.getMonth() + 1).padStart(2, '0');
        var day = String(dateObj.getDate()).padStart(2, '0');
        var hours = String(dateObj.getHours()).padStart(2, '0');
        var mins = String(dateObj.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + ' ' + hours + ':' + mins + ':00';
    }

    function scheduleCampaignHandler(e) {
        e.preventDefault();
        
        // Remove any existing click handlers first
        $('#schedule-campaign').off('click');
        
        if (!confirm("Are you sure you want to schedule the campaign?")) {
            return;
        }

        if (!newsletterData.nextScheduledTimestamp) {
            alert("No scheduled time available.");
            return;
        }

        var scheduledDate = new Date(parseInt(newsletterData.nextScheduledTimestamp, 10) * 1000);
        var localFormattedTime = formatLocalDateTime(scheduledDate);

        // Show loading state
        var $button = $(this);
        $button.prop('disabled', true);
        $button.css('opacity', '0.7');

        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'create_and_schedule_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                schedule_datetime: localFormattedTime
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || "Campaign scheduled successfully.");
                    location.reload();
                } else {
                    alert("Error scheduling campaign: " + (response.data ? response.data : 'Unknown error'));
                    $button.prop('disabled', false);
                    $button.css('opacity', '1');
                }
            },
            error: function(xhr, status, error) {
                alert("Ajax error scheduling campaign: " + error);
                $button.prop('disabled', false);
                $button.css('opacity', '1');
            }
        });
    }

    // Initialize scheduling functionality
    function initializeScheduling() {
        // Remove any existing handlers first
        $('#schedule-campaign').off('click');
        
        // Add the new handler
        $('#schedule-campaign').on('click', scheduleCampaignHandler);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initializeScheduling();
    });

})(jQuery);