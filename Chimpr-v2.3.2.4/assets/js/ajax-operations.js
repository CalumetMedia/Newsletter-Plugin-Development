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

            // Log block data being collected
            console.log('Block ' + index + ' data:', {
                type: blockData.type,
                title: blockData.title,
                show_title: blockData.show_title,
                template_id: blockData.template_id,
                category: blockData.category,
                date_range: blockData.date_range,
                story_count: blockData.story_count,
                manual_override: blockData.manual_override
            });

            // Collect post data
            $block.find('.block-posts li').each(function() {
                var $post = $(this);
                var postId = $post.data('post-id');
                var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
                var $orderInput = $post.find('.post-order');
                var isChecked = $checkbox.prop('checked');
                
                // Log post data being collected
                console.log('Post ' + postId + ' data:', {
                    checked: isChecked,
                    order: $orderInput.val()
                });
                
                // Only store checked posts
                if (isChecked) {
                    blockData.posts[postId] = {
                        checked: '1',
                        order: $orderInput.val() || '0'
                    };
                }
            });

            // Add HTML content if it's an HTML block
            if (blockData.type === 'html') {
                blockData.html = $block.find('.html-block textarea').val();
                console.log('HTML content:', blockData.html);
            }
            
            // Add WYSIWYG content if it's a WYSIWYG block
            if (blockData.type === 'wysiwyg') {
                blockData.wysiwyg = $block.find('.wysiwyg-editor-content').val();
                console.log('WYSIWYG content:', blockData.wysiwyg);
            }

            blocks[index] = blockData;
        });

        console.log('Final blocks data:', blocks);

        var ajaxData = {
            action: 'save_newsletter_blocks',
            security: newsletterData.nonceSaveBlocks,
            newsletter_slug: newsletterData.newsletterSlug,
            blocks: blocks
        };

        console.log('AJAX request data:', ajaxData);

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    updatePreview();
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                } else {
                    console.error('Save failed. Response:', response);
                    var errorMessage = 'An error occurred while saving blocks.';
                    if (response.data) {
                        console.error('Error data:', response.data);
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                            if (response.data.debug_info) {
                                console.error('Debug info:', response.data.debug_info);
                                errorMessage += '\n\nDebug info:\n' + JSON.stringify(response.data.debug_info, null, 2);
                            }
                        } else if (response.data.error) {
                            errorMessage = response.data.error;
                        }
                    }
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                var errorMessage = 'Error saving blocks';
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData.data) {
                        if (typeof responseData.data === 'string') {
                            errorMessage += ': ' + responseData.data;
                        } else if (responseData.data.message) {
                            errorMessage += ': ' + responseData.data.message;
                            if (responseData.data.debug_info) {
                                console.error('Debug info:', responseData.data.debug_info);
                                errorMessage += '\n\nDebug info:\n' + JSON.stringify(responseData.data.debug_info, null, 2);
                            }
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