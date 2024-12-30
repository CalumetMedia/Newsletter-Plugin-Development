(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;
    let globalUpdateInProgress = false;

    // Track active AJAX requests
    let activeRequests = [];

    // Helper function to cleanup requests
    function cleanupRequests() {
        activeRequests.forEach(request => {
            if (request && request.abort) {
                request.abort();
            }
        });
        activeRequests = [];
    }

    // Save and update preview
    window.saveAndUpdatePreview = function(blockData, blockIndex) {
        console.log('[Preview] Saving block data:', blockData);
        
        // If there's already an update in progress, queue this save
        if (globalUpdateInProgress) {
            console.log('[Preview] Update in progress, queueing save');
            return new Promise((resolve) => {
                setTimeout(() => {
                    saveAndUpdatePreview(blockData, blockIndex).then(resolve);
                }, 100);
            });
        }

        globalUpdateInProgress = true;
        
        var blocks = [];
        blocks[blockIndex] = blockData;

        return new Promise((resolve, reject) => {
            const request = $.ajax({
                url: newsletterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_newsletter_blocks',
                    security: newsletterData.nonceSaveBlocks,
                    newsletter_slug: newsletterData.newsletterSlug,
                    blocks: blocks
                }
            });

            // Track this request
            activeRequests.push(request);

            request
                .done(response => {
                    console.log('[Preview] Save response:', response);
                    if (!response.success) {
                        console.error('[Preview] Save failed:', response);
                        resetPreviewState();
                        reject(response);
                        return;
                    }
                    
                    // Only trigger preview after a successful save
                    console.log('[Preview] Save successful, generating preview...');
                    generatePreview('after_save')
                        .then(() => resolve(response))
                        .catch(error => reject(error))
                        .finally(() => {
                            const index = activeRequests.indexOf(request);
                            if (index > -1) {
                                activeRequests.splice(index, 1);
                            }
                            resetPreviewState();
                        });
                })
                .fail((xhr, status, error) => {
                    console.error('[Preview] Save error:', { xhr, status, error });
                    resetPreviewState();
                    reject(error);
                });
        });
    };

    function collectPostStates() {
        const state = {};
        $('.block-item').each(function() {
            const blockIndex = $(this).data('index');
            state[blockIndex] = state[blockIndex] || {};
            
            $(this).find('.sortable-post-item').each(function() {
                const postId = $(this).data('post-id');
                const $checkbox = $(this).find('input[type="checkbox"][name*="[checked]"]');
                const $orderInput = $(this).find('.post-order');
                
                // Only store checked posts
                if ($checkbox.prop('checked')) {
                    state[blockIndex][postId] = {
                        checked: '1',
                        order: $orderInput.val() || '0'
                    };
                }
            });
        });
        return state;
    }

    function generatePreview(trigger) {
        if (globalUpdateInProgress) {
            console.log('[Preview] Update still in progress, skipping');
            return Promise.resolve();
        }

        console.log('[Preview] Starting preview generation');
        globalUpdateInProgress = true;

        try {
            // Collect current state
            const postStates = collectPostStates();
            
            // Log the state for debugging
            Object.entries(postStates).forEach(([blockIndex, posts]) => {
                Object.entries(posts).forEach(([postId, state]) => {
                    console.log('[Preview] Post state:', {
                        blockIndex,
                        postId,
                        isChecked: state.checked === '1',
                        order: state.order
                    });
                });
            });

            console.log('[Preview] Collected state:', postStates);

            // Get all blocks data
            const blocks = [];
            $('.block-item').each(function() {
                const blockIndex = $(this).data('index');
                const blockData = {
                    type: $(this).find('.block-type').val(),
                    title: $(this).find('.block-title-input').val(),
                    show_title: $(this).find('.show-title-toggle').prop('checked') ? 1 : 0,
                    template_id: $(this).find('.block-template').val(),
                    category: $(this).find('.block-category').val(),
                    date_range: $(this).find('.block-date-range').val(),
                    story_count: $(this).find('.block-story-count').val(),
                    manual_override: $(this).find('.manual-override-toggle').prop('checked') ? 1 : 0,
                    posts: {}
                };

                // Add post states
                if (postStates[blockIndex]) {
                    blockData.posts = postStates[blockIndex];
                }

                if (blockData.type === 'html') {
                    blockData.html = $(this).find('.html-block textarea').val();
                } else if (blockData.type === 'wysiwyg') {
                    // Get content from TinyMCE if available
                    const editorId = $(this).find('.wysiwyg-editor-content').attr('id');
                    if (window.tinyMCE && window.tinyMCE.get(editorId)) {
                        console.log('[Preview] Getting WYSIWYG content from editor:', editorId);
                        blockData.wysiwyg = window.tinyMCE.get(editorId).getContent();
                        console.log('[Preview] WYSIWYG content:', blockData.wysiwyg);
                    } else {
                        console.log('[Preview] Getting WYSIWYG content from textarea');
                        blockData.wysiwyg = $(this).find('.wysiwyg-editor-content').val();
                    }
                }

                blocks[blockIndex] = blockData;
            });

            var formData = new FormData();
            formData.append('action', 'generate_preview');
            formData.append('newsletter_slug', newsletterData.newsletterSlug);
            formData.append('security', newsletterData.nonceGeneratePreview);
            formData.append('saved_selections', JSON.stringify(postStates));
            formData.append('blocks', JSON.stringify(blocks));

            // Create and store the promise
            previewUpdatePromise = new Promise((resolve, reject) => {
                $.ajax({
                    url: newsletterData.ajaxUrl,
                    method: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(response) {
                        console.log('[Preview] Server response:', response);
                        if (!response.success) {
                            reject(new Error(response.data || 'Preview generation failed'));
                            return;
                        }
                        $('#preview-content').html(response.data);
                        console.log('[Preview] Preview updated successfully');
                        resolve(response);
                    },
                    error: function(error) {
                        console.error('[Preview] Error updating preview:', error);
                        reject(error);
                    },
                    complete: function() {
                        // Remove this request from tracking
                        const index = activeRequests.indexOf(previewUpdatePromise);
                        if (index > -1) {
                            activeRequests.splice(index, 1);
                        }
                        resetPreviewState();
                    }
                });
            });

            // Track this request
            activeRequests.push(previewUpdatePromise);

            return previewUpdatePromise;

        } catch (error) {
            console.error('[Preview] Error in preview generation:', error);
            resetPreviewState();
            return Promise.reject(error);
        }
    }

    // Simplified updatePreview function with debounce
    let previewTimeout = null;
    let lastUpdateSource = null;
    window.updatePreview = function(source) {
        // Skip undefined source if we've already had a real update
        if (!source && lastUpdateSource) {
            console.log('[Preview] Skipping undefined source update after', lastUpdateSource);
            return;
        }
        
        console.log('[Preview] Update requested from:', source);
        lastUpdateSource = source || 'initial';
        
        // Clear any existing timeout
        if (previewTimeout) {
            clearTimeout(previewTimeout);
            previewTimeout = null;
        }
        
        // If an update is in progress, wait a bit longer
        const delay = globalUpdateInProgress ? 500 : 250;
        
        // Set a new timeout
        previewTimeout = setTimeout(function() {
            previewTimeout = null;  // Clear the reference
            // Double check the flag when timeout executes
            if (!globalUpdateInProgress) {
                generatePreview();
            } else {
                console.log('[Preview] Update still in progress, skipping');
            }
        }, delay);
    };

    // Cleanup on page unload
    $(window).on('unload', function() {
        resetPreviewState();
    });

    // Helper function to reset state
    function resetPreviewState() {
        if (previewTimeout) {
            clearTimeout(previewTimeout);
            previewTimeout = null;
        }
        cleanupRequests();
        globalUpdateInProgress = false;
        previewUpdatePromise = null;
        lastUpdateSource = null;
    }

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
            var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            var $orderInput = $post.find('.post-order');
            
            posts[postId] = {
                checked: $checkbox.prop('checked') ? '1' : '',
                order: $orderInput.val() || '9223372036854775807'
            };
        });
        return posts;
    };

    // Export global update flag
    window.isPreviewUpdateInProgress = function() {
        return globalUpdateInProgress;
    };

    // Expose a function to set the global update flag
    window.setPreviewUpdateInProgress = function(value) {
        globalUpdateInProgress = value;
    };

})(jQuery);