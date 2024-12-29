(function($) {

    // Save Blocks
    window.saveBlocks = function() {
        console.log('saveBlocks called');
        
        // Ensure all TinyMCE editors save their content to textareas
        if (typeof tinyMCE !== 'undefined') {
            for (var i = 0; i < tinyMCE.editors.length; i++) {
                tinyMCE.editors[i].save();
            }
        }
        
        // Collect all blocks data
        var blocks = [];
        $('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var blockType = $block.find('.block-type').val();
            
            // Get base block data
            var blockData = {
                type: blockType,
                title: $block.find('.block-title-input').val(),
                show_title: $block.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $block.find('.block-template').val(),
                category: $block.find('.block-category').val(),
                date_range: $block.find('.block-date-range').val(),
                story_count: $block.find('.block-story-count').val(),
                manual_override: $block.find('input[name*="[manual_override]"]').prop('checked') ? 1 : 0,
                posts: {}
            };
            
            // Handle different block types
            if (blockType === 'wysiwyg') {
                var editorId = 'wysiwyg-editor-' + blockIndex;
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                    blockData.wysiwyg = tinyMCE.get(editorId).getContent();
                } else {
                    blockData.wysiwyg = $('#' + editorId).val();
                }
            } else if (blockType === 'html') {
                blockData.html = $block.find('.html-block textarea').val();
            } else if (blockType === 'content') {
                // Get posts data
                $block.find('.block-posts li').each(function() {
                    var $post = $(this);
                    var postId = $post.data('post-id');
                    var $checkbox = $post.find('input[type="checkbox"][name*="[selected]"]');
                    var $orderInput = $post.find('.post-order');
                    
                    if (postId) {
                        blockData.posts[postId] = {
                            selected: $checkbox.prop('checked') ? 1 : 0,
                            order: $orderInput.val() || '9223372036854775807'
                        };
                    }
                });
            }
            
            // Save to blocks array
            blocks[blockIndex] = blockData;
        });

        console.log('Blocks data being sent:', blocks);

        // Get the form's nonce
        var nonce = $('#blocks-form input[name="security"]').val();
        
        // Prepare the data
        var data = {
            action: 'save_newsletter_blocks',
            security: nonce,
            newsletter_slug: newsletterData.newsletterSlug,
            blocks: blocks,
            subject_line: $('#subject_line').val(),
            custom_header: $('#custom_header').val(),
            custom_footer: $('#custom_footer').val()
        };

        console.log('Full request data:', data);

        // Save via AJAX
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                if (response.success) {
                    updatePreview();
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                } else {
                    console.error('Save failed:', response);
                    alert(response.data || 'Error saving blocks. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusText: xhr.statusText,
                    readyState: xhr.readyState
                });
                
                // Try to parse response text if available
                try {
                    if (xhr.responseText) {
                        console.log('Response text:', xhr.responseText);
                        var jsonResponse = JSON.parse(xhr.responseText);
                        console.log('Parsed response:', jsonResponse);
                    }
                } catch(e) {
                    console.log('Could not parse response text:', e);
                }
                
                alert('Error saving blocks. Check browser console for details.');
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