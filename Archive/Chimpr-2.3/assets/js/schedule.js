Fixed schedule.js with 15-minute intervals

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
            
            // Format datetime for API with proper timezone offset
            var tzoffset = now.getTimezoneOffset() * 60000;
            var localISOTime = (new Date(now - tzoffset)).toISOString().slice(0, -8);

            $.ajax({
                url: newsletterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'schedule_mailchimp_campaign',
                    security: newsletterData.nonceMailchimp,
                    newsletter_slug: newsletterData.newsletterSlug,
                    schedule_datetime: localISOTime
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
        });
    };

    
    window.setupSendNowHandler = function() {
        var sendNowButtonClickedOnce = false;
        $('#send-now').off('click').on('click', function() {
            if (!sendNowButtonClickedOnce) {
                var firstConfirm = confirm("Are you sure you want to SEND NOW to your Mailchimp list?");
                if (firstConfirm) {
                    sendNowButtonClickedOnce = true;
                    alert("Click SEND NOW again to confirm sending.");
                    $('#send-now').addClass('send-now-confirmed');
                }
            } else {
                var secondConfirm = confirm("Double Checking: Are you absolutely sure you want to SEND NOW?");
                if (secondConfirm) {
                    sendNowCampaign();
                } else {
                    sendNowButtonClickedOnce = false;
                    $('#send-now').removeClass('send-now-confirmed');
                }
            }
        });
    };

    $(document).ready(function() {
        setupSendNowHandler();
        setupScheduleHandler();
    });

})(jQuery);