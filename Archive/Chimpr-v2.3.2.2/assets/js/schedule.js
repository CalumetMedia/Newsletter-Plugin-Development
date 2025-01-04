(function($) {
    function scheduleCampaignHandler(e) {
        e.preventDefault();
        
        const $button = $(this);
        const timestamp = parseInt($button.attr('data-timestamp'), 10);
        const formattedTime = $button.attr('data-formatted-time');
        
        // Add more detailed console logging
        console.log('Schedule button clicked', {
            rawTimestamp: $button.attr('data-timestamp'),
            parsedTimestamp: timestamp,
            formattedTime: formattedTime,
            buttonData: $button.data(),
            buttonAttributes: $button.attr()
        });
        
        if (!timestamp || isNaN(timestamp)) {
            console.error('Invalid timestamp:', $button.attr('data-timestamp'));
            alert("Error: No valid scheduled time available");
            return;
        }

        // Add confirmation
        if (!confirm(`Are you sure you want to schedule this campaign for ${formattedTime}?`)) {
            return;
        }

        $button.prop('disabled', true).css('opacity', '0.5');

        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'schedule_mailchimp_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                timestamp: timestamp.toString()
            },
            success: function(response) {
                console.log('Schedule Response:', response);
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert("Error scheduling campaign: " + (response.data || 'Unknown error'));
                    $button.prop('disabled', false).css('opacity', '1');
                }
            },
            error: function(xhr, status, error) {
                console.error('Schedule AJAX Error:', {xhr, status, error});
                alert("Error scheduling campaign. Please check the console for details.");
                $button.prop('disabled', false).css('opacity', '1');
            }
        });
    }

    $(document).ready(function() {
        console.log('Schedule.js loaded');
        const $scheduleButton = $('#schedule-campaign');
        console.log('Schedule button found:', $scheduleButton.length > 0);
        console.log('Schedule button data:', {
            timestamp: $scheduleButton.attr('data-timestamp'),
            formattedTime: $scheduleButton.attr('data-formatted-time')
        });
        
        $('#schedule-campaign').on('click', scheduleCampaignHandler);
    });

})(jQuery);