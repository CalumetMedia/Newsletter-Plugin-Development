(function($) {

    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: currentIndex,
                date_range: dateRange,
                newsletter_slug: newsletterData.newsletterSlug
            },
            success: function(response) {
                if (response.success) {
                    block.find('.block-posts').html(response.data);
                    initializeSortable(block);
                    updatePreview();
                }
            }
        });
    };

    // Save Blocks
window.saveBlocks = function() {
    console.log('saveBlocks called');
    var $form = $('#blocks-form');
    
    // Create a FormData object to properly handle all form data
    var formData = new FormData($form[0]);
    formData.append('action', 'save_newsletter_blocks');
    formData.append('security', newsletterData.nonceSaveBlocks);
    formData.append('newsletter_slug', newsletterData.newsletterSlug);

    // Log the form data for debugging
    console.log('Form data being sent:', Object.fromEntries(formData));

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                updatePreview();
                alert('Newsletter blocks have been successfully saved.');
            } else {
                alert(response.data);
            }
        },
        error: function(xhr, status, error) {
            alert('Error saving blocks: ' + error);
        }
    });
};

    window.sendTestEmail = function(testEmail) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'send_test_email',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Test email sent successfully!');
                    $('#test-email').val('');
                } else {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Error sending test email: ' + response.data);
                }
            },
            error: function() {
                $('#email-input-step').hide();
                $('#success-step').show().find('p').text('Error connecting to server');
            }
        });
    };

    window.createMailchimpCampaign = function(subject_line, campaign_name) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'create_mailchimp_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                subject_line: subject_line,
                campaign_name: campaign_name
            },
            success: function(response) {
                if (response.success) {
                    alert('Campaign created successfully! Campaign ID: ' + response.data.campaign_id);
                } else {
                    alert('Error creating campaign: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error connecting to server: ' + error);
            }
        });
    };

    window.createAndScheduleCampaign = function(scheduleDateTime) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'create_and_schedule_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                schedule_datetime: scheduleDateTime
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + (response.data ? response.data : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while attempting to create and schedule the campaign: ' + error);
            }
        });
    };

    window.sendNowCampaign = function() {
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
            error: function(xhr, status, error) {
                alert("Error sending campaign: " + error);
            }
        });
    };

})(jQuery);