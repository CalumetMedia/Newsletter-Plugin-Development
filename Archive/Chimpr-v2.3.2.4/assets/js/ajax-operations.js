(function($) {

    // Save Blocks
    window.saveBlocks = function() {
        console.log('saveBlocks called');
        
        // Create blocks array
        var blocks = [];
        
        // Get all blocks
        $('#blocks-container .block-item').each(function(index) {
            var $block = $(this);
            var blockData = {
                type: $block.find('.block-type').val(),
                title: $block.find('.block-title-input').val(),
                show_title: $block.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $block.find('.block-template').val(),
                category: $block.find('.block-category').val(),
                date_range: $block.find('.block-date-range').val(),
                story_count: $block.find('.block-story-count').val(),
                manual_override: $block.find('input[name*="[manual_override]"]').prop('checked') ? 1 : 0,
                posts: {}
            };
            
            // Collect post data
            $block.find('.block-posts li').each(function() {
                var $post = $(this);
                var postId = $post.data('post-id');
                var $checkbox = $post.find('input[type="checkbox"][name*="[selected]"]');
                var $orderInput = $post.find('.post-order');
                
                blockData.posts[postId] = {
                    selected: $checkbox.prop('checked') ? 1 : 0,
                    order: $orderInput.val() || '9223372036854775807'
                };
            });

            // Add HTML content if it's an HTML block
            if (blockData.type === 'html') {
                blockData.html = $block.find('.html-block textarea').val();
            }
            
            // Add WYSIWYG content if it's a WYSIWYG block
            if (blockData.type === 'wysiwyg') {
                blockData.wysiwyg = $block.find('.wysiwyg-editor-content').val();
            }
            
            blocks[index] = blockData;
        });
        
        var ajaxData = {
            action: 'save_newsletter_blocks',
            security: newsletterData.nonceSaveBlocks,
            newsletter_slug: newsletterData.newsletterSlug,
            blocks: blocks
        };

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    window.updatePreview('after_save');
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                } else {
                    console.error('Save failed:', response);
                    var errorMessage = 'An error occurred while saving blocks.';
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.data.error) {
                            errorMessage = response.data.error;
                        }
                    }
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                var errorMessage = 'Error saving blocks';
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData.data) {
                        if (typeof responseData.data === 'string') {
                            errorMessage += ': ' + responseData.data;
                        } else if (responseData.data.message) {
                            errorMessage += ': ' + responseData.data.message;
                        } else {
                            errorMessage += ': ' + JSON.stringify(responseData.data);
                        }
                    } else if (typeof error === 'string') {
                        errorMessage += ': ' + error;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMessage += ': ' + xhr.responseText;
                    } else if (typeof error === 'string') {
                        errorMessage += ': ' + error;
                    }
                }
                alert(errorMessage);
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