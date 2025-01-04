(function($) {
    function formatLocalDateTime(dateObj) {
        var year = dateObj.getFullYear();
        var month = String(dateObj.getMonth() + 1).padStart(2, '0');
        var day = String(dateObj.getDate()).padStart(2, '0');
        var hours = String(dateObj.getHours()).padStart(2, '0');
        var mins = String(dateObj.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + ' ' + hours + ':' + mins + ':00';
    }

    function scheduleCampaignHandler() {
        if (!confirm("Are you sure you want to schedule the campaign?")) return;

        if (!newsletterData.nextScheduledTimestamp) {
            alert("No scheduled time available.");
            return;
        }

        var scheduledDate = new Date(parseInt(newsletterData.nextScheduledTimestamp, 10) * 1000);
        var localFormattedTime = formatLocalDateTime(scheduledDate);

        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'create_and_schedule_campaign', // Updated action name
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
                }
            },
            error: function(xhr, status, error) {
                alert("Ajax error scheduling campaign: " + error);
            }
        });
    }

    function sendNowHandler() {
        if (!confirm("Are you sure you want to SEND NOW to your Mailchimp list?")) return;

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'send_now_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug
            },
            success: function(response) {
                if (response.success) {
                    alert("Campaign has been sent successfully.");
                    location.reload();
                } else {
                    alert("Error sending campaign: " + (response.data ? response.data : 'Unknown error'));
                }
            },
            error: function(error) {
                alert("Ajax error sending campaign.");
            }
        });
    }

    $(document).ready(function() {
        $(document).off('click', '#schedule-campaign');
        $(document).off('click', '#send-now');
        $('#schedule-campaign').off('click');
        $('#send-now').off('click');

        $('#schedule-campaign').on('click', scheduleCampaignHandler);
        $('#send-now').on('click', sendNowHandler);
    });

})(jQuery);
