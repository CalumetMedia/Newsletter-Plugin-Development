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
        let hasValidState = false;

        $('.block-item').each(function() {
            const blockIndex = $(this).data('index');
            state[blockIndex] = {};
            
            const storyCount = $(this).find('.block-story-count').val();
            const manualOverride = $(this).find('.manual-override-toggle').prop('checked');
            
            // Get all posts for this block
            const $posts = $(this).find('.block-posts li');
            $posts.each(function(index) {
                const postId = $(this).data('post-id');
                if (!postId) return;

                const $checkbox = $(this).find('input[type="checkbox"][name*="[checked]"]');
                const $orderInput = $(this).find('.post-order');
                
                // Post is selected if:
                // 1. Manual override is on and checkbox is checked, OR
                // 2. Manual override is off and index is less than story count (auto-selection)
                const isSelected = manualOverride ? 
                    $checkbox.prop('checked') : 
                    (storyCount !== 'disable' && index < parseInt(storyCount));
                
                state[blockIndex][postId] = {
                    checked: isSelected ? '1' : '',
                    order: $orderInput.val() || '0'
                };

                if (isSelected) hasValidState = true;
            });
            
            // Keep the block state even if empty to maintain block structure
            if (Object.keys(state[blockIndex]).length === 0) {
                state[blockIndex] = {};
            }
        });
        
        // Return state if we have any blocks, even without selections
        // This allows preview generation for empty/new blocks
        return Object.keys(state).length > 0 ? state : null;
    }

    // Simplified updatePreview function with debounce
    let previewTimeout = null;
    let lastUpdateSource = null;
    window.updatePreview = function(source) {
        if (!source) {
            console.warn('[Preview] Update called without source, using default');
            source = 'default';
        }
        
        console.log('[Preview] Update requested from:', source);
        lastUpdateSource = source;
        
        // Clear any existing timeout
        if (previewTimeout) {
            clearTimeout(previewTimeout);
            previewTimeout = null;
        }
        
        // If an update is in progress, wait a bit longer
        const delay = window.isPreviewUpdateInProgress() ? 500 : 250;
        
        // Set a new timeout
        previewTimeout = setTimeout(function() {
            previewTimeout = null;  // Clear the reference
            // Double check the flag when timeout executes
            if (!window.isPreviewUpdateInProgress()) {
                window.generatePreview(source);
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

    // Expose the preview generation function globally
    window.generatePreview = function(trigger) {
        if (!trigger) trigger = 'default';
        
        if (window.isPreviewUpdateInProgress()) {
            return Promise.resolve();
        }
        
        window.setPreviewUpdateInProgress(true);

        try {
            // Collect current state
            const postStates = collectPostStates();
            
            if (!postStates) {
                $('#preview-container').html('<p>No content available for preview. Please select a category and posts to preview.</p>');
                window.setPreviewUpdateInProgress(false);
                return Promise.resolve();
            }

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
                    posts: postStates[blockIndex] || {}
                };

                if (blockData.type === 'html') {
                    blockData.html = $(this).find('.html-block textarea').val();
                } else if (blockData.type === 'wysiwyg') {
                    blockData.wysiwyg = $(this).find('.wysiwyg-editor-content').val();
                }

                blocks[blockIndex] = blockData;
            });

            const data = {
                action: 'generate_preview',
                newsletter_slug: newsletterData.newsletterSlug,
                security: newsletterData.nonceGeneratePreview,
                saved_selections: JSON.stringify(postStates),
                blocks: JSON.stringify(blocks),
                custom_header: $('#custom_header').val() || '',
                custom_footer: $('#custom_footer').val() || '',
                subject_line: $('#subject_line').val() || '',
                campaign_name: $('#campaign_name').val() || '',
                start_date: $('#start_date').val() || '',
                end_date: $('#end_date').val() || ''
            };

            return $.ajax({
                url: newsletterData.ajaxUrl,
                method: 'POST',
                data: data,
                dataType: 'json'
            })
            .done(response => {
                if (!response.success) {
                    $('#preview-container').html('<p>Error generating preview</p>');
                    return;
                }
                
                if (!response.data || response.data.includes('No content available for preview')) {
                    $('#preview-container').html('<p>No content available for preview. Please ensure posts are selected and try again.</p>');
                    return;
                }
                
                try {
                    const cleanedData = response.data.replace(/\\r\\n/g, '\n').replace(/\\"/g, '"');
                    let $container = $('#preview-container');
                    if (!$container.length) {
                        $container = $('<div id="preview-container"></div>').appendTo('#preview-tab');
                    }
                    $container.empty().html(cleanedData);
                } catch (error) {
                    $('#preview-container').html('<p>Error displaying preview content</p>');
                }
            })
            .fail(() => {
                $('#preview-container').html('<p>Failed to generate preview</p>');
            })
            .always(() => {
                window.setPreviewUpdateInProgress(false);
            });

        } catch (error) {
            window.setPreviewUpdateInProgress(false);
            return Promise.reject(error);
        }
    };

})(jQuery);