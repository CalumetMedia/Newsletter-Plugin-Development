(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;
    let globalUpdateInProgress = false;

    // Save and update preview
    window.saveAndUpdatePreview = function(blockData, blockIndex) {
        console.log('[Preview] Saving block data:', blockData);
        
        var blocks = [];
        blocks[blockIndex] = blockData;

        globalUpdateInProgress = true;
        
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
                console.log('[Preview] Save response:', response);
                if (response.success) {
                    // Wait a moment for the save to be processed
                    setTimeout(() => {
                        if (typeof window.generatePreview === 'function') {
                            console.log('[Preview] Calling generatePreview after save...');
                            window.generatePreview().then(() => {
                                console.log('[Preview] Preview generated successfully');
                                globalUpdateInProgress = false;
                            }).catch(error => {
                                console.error('[Preview] Error generating preview:', error);
                                globalUpdateInProgress = false;
                            });
                        } else {
                            console.error('[Preview] generatePreview function not found');
                            globalUpdateInProgress = false;
                        }
                    }, 100); // Small delay to ensure save is processed
                } else {
                    console.error('[Preview] Save failed:', response);
                    globalUpdateInProgress = false;
                }
            },
            error: function(xhr, status, error) {
                console.error('[Preview] Save error:', { xhr, status, error });
                globalUpdateInProgress = false;
            }
        });
    };

    window.generatePreview = function() {
        // If there's already a preview update in progress, wait for it
        if (previewUpdatePromise) {
            return previewUpdatePromise;
        }

        console.log('[Preview] Starting preview generation');

        // Store the current state of all checkboxes and their order
        var savedState = {};
        $('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var categoryId = $block.find('.block-category').val();
            var storyCount = $block.find('.block-story-count').val();
            var manualOverride = $block.find('input[name*="[manual_override]"]').prop('checked');
            
            savedState[blockIndex] = {
                storyCount: storyCount,
                category: categoryId,
                manual_override: manualOverride ? 1 : 0,
                selections: {}
            };
            
            $block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
                var $checkbox = $(this);
                var postId = $checkbox.closest('li').data('post-id');
                var $orderInput = $checkbox.closest('li').find('.post-order');
                var isChecked = $checkbox.is(':checked');
                var order = $orderInput.length ? $orderInput.val() : '0';
                
                console.log('[Preview] Post state:', { blockIndex, postId, isChecked, order });
                
                savedState[blockIndex].selections[postId] = {
                    checked: isChecked,
                    order: order
                };
            });
        });

        console.log('[Preview] Collected state:', savedState);

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });
        formData.push({ name: 'saved_selections', value: JSON.stringify(savedState) });

        // Create and store the promise
        previewUpdatePromise = $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: formData
        }).done(function(response) {
            console.log('[Preview] Server response:', response);
            if (response.success) {
                $('#preview-content').html(response.data);
                
                // Restore state after preview loads
                $('.block-item').each(function() {
                    var $block = $(this);
                    var blockIndex = $block.data('index');
                    var state = savedState[blockIndex];
                    
                    if (state) {
                        $block.find('.block-story-count').val(state.storyCount);
                        Object.keys(state.selections).forEach(function(postId) {
                            var selection = state.selections[postId];
                            var $li = $block.find('li[data-post-id="' + postId + '"]');
                            if ($li.length) {
                                var $checkbox = $li.find('input[type="checkbox"][name*="[selected]"]');
                                var $orderInput = $li.find('.post-order');
                                
                                if ($checkbox.length) {
                                    console.log('[Preview] Restoring checkbox state:', { postId, checked: selection.checked });
                                    $checkbox.prop('checked', selection.checked);
                                }
                                if ($orderInput.length) {
                                    $orderInput.val(selection.order);
                                }
                            }
                        });
                    }
                });
            } else {
                console.error('[Preview] Error in preview response:', response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('[Preview] Error updating preview:', textStatus, errorThrown);
        }).always(function() {
            previewUpdatePromise = null;
        });

        return previewUpdatePromise;
    };

    // Debounced updatePreview function
    window.updatePreview = function(source) {
        console.log('[Preview] Update requested from:', source);
        
        // If there's already an update in progress, queue this one
        if (globalUpdateInProgress) {
            console.log('[Preview] Update in progress, queueing update');
            if (window.updatePreviewTimeout) {
                clearTimeout(window.updatePreviewTimeout);
            }
            
            window.updatePreviewTimeout = setTimeout(function() {
                console.log('[Preview] Processing queued update');
                generatePreview();
            }, 500);
            return;
        }
        
        // Clear any existing timeout
        if (window.updatePreviewTimeout) {
            clearTimeout(window.updatePreviewTimeout);
            window.updatePreviewTimeout = null;
        }
        
        // Set the flag before starting the update
        globalUpdateInProgress = true;
        
        // Generate the preview
        generatePreview().then(function() {
            globalUpdateInProgress = false;
            
            // If there's a queued update, process it
            if (window.updatePreviewTimeout) {
                console.log('[Preview] Processing queued update after completion');
                clearTimeout(window.updatePreviewTimeout);
                window.updatePreviewTimeout = setTimeout(function() {
                    updatePreview('queued_update');
                }, 100);
            }
        }).catch(function(error) {
            console.error('[Preview] Error in preview update:', error);
            globalUpdateInProgress = false;
        });
    };

    // Helper function to collect block data
    window.collectBlockData = function($block) {
        return {
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
    };

    // Helper function to collect post data
    window.collectPostData = function($block) {
        var posts = {};
        $block.find('.block-posts li').each(function() {
            var $post = $(this);
            var postId = $post.data('post-id');
            var $checkbox = $post.find('input[type="checkbox"][name*="[selected]"]');
            var $orderInput = $post.find('.post-order');
            
            posts[postId] = {
                selected: $checkbox.prop('checked') ? 1 : 0,
                order: $orderInput.val() || '9223372036854775807'
            };
        });
        return posts;
    };

    // Export global update flag
    window.isPreviewUpdateInProgress = function() {
        return globalUpdateInProgress;
    };

})(jQuery);