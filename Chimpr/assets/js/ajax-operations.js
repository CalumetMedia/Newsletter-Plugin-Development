(function($) {
    /**
     * Existing code from your ajax-operations.js
     */

    // Save entire block structure (existing method)
    function saveBlocks(newsletterSlug, blocks, subjectLine, customHeader, customFooter, isAutoSave = false) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('action', 'newsletter_handle_blocks_form_submission');
            formData.append('security', window.saveBlocksNonce);
            formData.append('newsletter_slug', newsletterSlug);
            formData.append('blocks', JSON.stringify(blocks));
            formData.append('is_auto_save', isAutoSave ? '1' : '0');

            if (subjectLine) {
                formData.append('subject_line', subjectLine);
            }
            if (customHeader) {
                formData.append('custom_header', customHeader);
            }
            if (customFooter) {
                formData.append('custom_footer', customFooter);
            }

            $.ajax({
                url: window.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.data || 'Save failed'));
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    reject(new Error(errorThrown || textStatus));
                }
            });
        });
    }

    // Send Test Email
    function sendTestEmail(testEmail) {
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
    }

    // Create Mailchimp Campaign
    function createMailchimpCampaign(subject_line, campaign_name) {
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
    }

    // Create and Schedule Campaign
    function createAndScheduleCampaign(scheduleDateTime) {
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
    }

    // Send Campaign Immediately
    function sendNowCampaign() {
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
    }

    /**
     * Below is the newly added AJAX code for block-manager interactions:
     *   - saveBlockState
     *   - loadBlockPosts
     *   - initWysiwygEditor
     * 
     * This code references:
     *   - collectPostData(...) from utilities.js
     *   - isUpdateInProgress, setUpdateInProgress from state.js
     *   - initializeSortable(...) from block-sort.js
     *   - updatePreview(...) presumably from your preview or block-manager
     */

    // Save the current newsletter blocks to server
    function saveBlockState($block, isManual, callback) {
        console.log('Saving block state:', {
            blockIndex: $block.data('index'),
            blockType: $block.find('.block-type').val(),
            isManual: isManual,
            htmlContent: $block.find('.block-html').val(),
            wysiwygContent: $block.find('.wysiwyg-editor-content').val() ? true : false
        });

        var blocks = [];
        
        // Gather data from all blocks in the DOM
        $('#blocks-container .block-item').each(function() {
            const $currentBlock = $(this);

            let blockData = {
                type: $currentBlock.find('.block-type').val(),
                title: $currentBlock.find('.block-title-input').val(),
                show_title: $currentBlock.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $currentBlock.find('.block-template').val() || '0',
                category: $currentBlock.find('.block-category').val() || '',
                date_range: $currentBlock.find('.block-date-range').val() || '7',
                story_count: $currentBlock.find('.block-story-count').val() || 'disable',
                manual_override: (
                    $currentBlock.is($block)
                        ? isManual
                        : $currentBlock.find('.manual-override-toggle').prop('checked')
                ) ? 1 : 0,
                posts: collectPostData($currentBlock) // from utilities.js
            };

            blocks.push(blockData);
        });

        return $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_newsletter_blocks',
                security: newsletterData.nonceSaveBlocks,
                newsletter_slug: newsletterData.newsletterSlug,
                blocks: blocks
            },
            success: function(response) {
                if (response.success && typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    }

    // Load posts via AJAX for a single block
    function loadBlockPosts(block, categoryId, currentIndex, dateRange, storyCount, skipPreview = false) {
        console.log('loadBlockPosts called with:', {
            categoryId,
            currentIndex,
            dateRange,
            storyCount,
            skipPreview
        });

        if (!block || !categoryId) {
            console.error('Missing required parameters:', { block: !!block, categoryId });
            return Promise.resolve();
        }

        var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked') || false;
        var currentSelections = collectPostData(block);

        var savedSelections = {
            [currentIndex]: {
                posts: currentSelections,
                manual_override: manualOverride ? '1' : '0',
                story_count: storyCount
            }
        };

        var data = new FormData();
        data.append('action', 'load_block_posts');
        data.append('security', window.newsletterAjaxNonce || newsletterData.nonceLoadPosts);
        data.append('category_id', categoryId);
        data.append('block_index', currentIndex);
        data.append('date_range', dateRange);
        data.append('story_count', storyCount);
        data.append('newsletter_slug', newsletterData.newsletterSlug);
        data.append('saved_selections', JSON.stringify(savedSelections));
        data.append('manual_override', manualOverride ? 'true' : 'false');

        console.log('Sending AJAX request with data:', Object.fromEntries(data));

        return $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            method: 'POST',
            processData: false,
            contentType: false,
            data: data,
            beforeSend: function(xhr) {
                console.log('AJAX request starting...');
                block.find('.block-posts').addClass('loading');
            },
            success: function(response) {
                console.log('AJAX response received:', response);
                block.find('.block-posts').removeClass('loading');
                
                if (response.success && response.data) {
                    var $postsContainer = block.find('.block-posts');
                    try {
                        $postsContainer.empty();
                        var $temp = $('<div>').html(response.data);

                        // Restore checkboxes if manual override
                        if (manualOverride) {
                            $temp.find('input[type="checkbox"]').each(function() {
                                var $checkbox = $(this);
                                var postId = $checkbox.closest('li').data('post-id');
                                if (
                                    currentSelections[postId] && 
                                    currentSelections[postId].checked === '1'
                                ) {
                                    $checkbox.prop('checked', true);
                                    $checkbox.closest('li')
                                        .find('.post-order')
                                        .val(currentSelections[postId].order || '0');
                                } else {
                                    $checkbox.prop('checked', false);
                                }
                            });
                        }

                        $postsContainer.append($temp.children());
                        // Make post titles visible
                        $postsContainer.find('.post-title').css({
                            'display': 'inline-block',
                            'visibility': 'visible',
                            'opacity': '1'
                        });

                        // Re-initialize sorting
                        initializeSortable(block);

                        // Save updated state
                        return saveBlockState(block, manualOverride, function() {
                            if (!skipPreview && !isUpdateInProgress()) {
                                setUpdateInProgress(true);
                                setTimeout(() => {
                                    updatePreview('posts_loaded');
                                    setUpdateInProgress(false);
                                }, 250);
                            }
                        });
                    } catch (error) {
                        console.error('Error processing response:', error);
                        $postsContainer.html(response.data);
                        return Promise.reject(error);
                    }
                } else {
                    console.error('Invalid response:', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                block.find('.block-posts').removeClass('loading');
            }
        });
    }

    // Initialize or refresh the WYSIWYG editor for a given block
    function initWysiwygEditor(block) {
        const blockIndex = block.data('index');
        const editorId = 'wysiwyg-editor-' + blockIndex;
        
        let existingContent = '';
        if (tinymce.get(editorId)) {
            existingContent = tinymce.get(editorId).getContent();
            tinymce.execCommand('mceRemoveEditor', true, editorId);
        } else if ($('#' + editorId).length) {
            existingContent = $('#' + editorId).val();
        }

        // Load existing content from server if none is found
        if (!existingContent) {
            const loadUrl = window.ajaxurl;
            $.ajax({
                url: loadUrl,
                type: 'POST',
                async: false,
                data: {
                    action: 'load_block_content',
                    security: window.newsletterData.nonceLoadPosts,
                    block_index: blockIndex,
                    block_type: 'wysiwyg',
                    newsletter_slug: window.newsletterData.newsletterSlug
                },
                success: function(response) {
                    if (response.success && response.data) {
                        existingContent = response.data;
                    }
                }
            });
        }

        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize(editorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                    setup: function(editor) {
                        editor.on('init', function() {
                            if (existingContent) {
                                editor.setContent(existingContent);
                            }
                        });
                        editor.on('change keyup paste', function() {
                            editor.save();
                            // Possibly call debouncedAutoSave() or trackEditorChanges()
                        });
                        editor.on('submit', function() {
                            editor.save();
                        });
                    }
                },
                quicktags: true,
                mediaButtons: true
            });
        }

        return existingContent;
    }

    /**
     * Expose everything to the global scope
     */
    window.saveBlocks = saveBlocks;  // your existing method
    window.sendTestEmail = sendTestEmail;
    window.createMailchimpCampaign = createMailchimpCampaign;
    window.createAndScheduleCampaign = createAndScheduleCampaign;
    window.sendNowCampaign = sendNowCampaign;

    // New block-manager AJAX methods
    window.saveBlockState = saveBlockState;
    window.loadBlockPosts = loadBlockPosts;
    window.initWysiwygEditor = initWysiwygEditor;

})(jQuery);
