(function($) {

window.loadBlockPosts = function(block, categoryId, currentIndex) {
    var dates = updateBlockDates(block);

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            newsletter_slug: newsletterData.newsletterSlug,
            start_date: dates.startDate,
            end_date: dates.endDate
        },
        success: function(response) {
            if (response.success) {
                block.find('.block-posts').html(response.data);
                initializeBlockUI(block);
            }
        }
    });
};

// Save Blocks
window.saveBlocks = function() {
    var formData = $('#blocks-form').serializeArray();
    formData.push({ name: 'action', value: 'save_newsletter_blocks' });
    formData.push({ name: 'security', value: newsletterData.nonceSaveBlocks });
    formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
    formData.push({
        name: 'use_manual_schedule',
        value: $('#use_manual_schedule').is(':checked') ? '1' : '0'
    });
    formData.push({
        name: 'manual_schedule_date',
        value: $('#manual_schedule_date').val()
    });
    formData.push({
        name: 'manual_schedule_time',
        value: $('#manual_schedule_time').val()
    });
    formData.push({
        name: 'custom_header',
        value: $('#custom_header').val()
    });
    formData.push({
        name: 'custom_footer',
        value: $('#custom_footer').val()
    });

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                updatePreview();
                alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
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
