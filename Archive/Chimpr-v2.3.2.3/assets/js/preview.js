(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;
    let globalUpdateInProgress = false;

    // Save and update preview
    window.saveAndUpdatePreview = function(blockData, blockIndex) {
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
                if (response.success) {
                    setTimeout(() => {
                        if (typeof window.generatePreview === 'function') {
                            window.generatePreview().then(() => {
                                globalUpdateInProgress = false;
                            }).catch(() => {
                                globalUpdateInProgress = false;
                            });
                        } else {
                            globalUpdateInProgress = false;
                        }
                    }, 100);
                } else {
                    globalUpdateInProgress = false;
                }
            },
            error: function() {
                globalUpdateInProgress = false;
            }
        });
    };

    window.generatePreview = function() {
        // If there's already a preview update in progress, wait for it
        if (previewUpdatePromise) {
            return previewUpdatePromise;
        }

        // Store editor states before preview update
        var editorStates = {};
        $('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var editorId = 'wysiwyg-editor-' + blockIndex;
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                var editor = tinyMCE.get(editorId);
                var state = {
                    content: editor.getContent()
                };
                
                // Only try to get selection if editor is initialized and focused
                try {
                    if (editor.selection && editor.selection.getBookmark && !editor.isHidden()) {
                        state.selection = editor.selection.getBookmark(2, true);
                    }
                } catch(e) {
                    console.log('Could not get editor selection for', editorId);
                }
                
                editorStates[editorId] = state;
            }
        });

        // Store the current state of all checkboxes and their order
        var savedState = {};
        $('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var blockData = window.collectBlockData($block);
            
            savedState[blockIndex] = blockData;
            savedState[blockIndex].selections = {};
            
            $block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
                var $checkbox = $(this);
                var postId = $checkbox.closest('li').data('post-id');
                var $orderInput = $checkbox.closest('li').find('.post-order');
                var isChecked = $checkbox.is(':checked');
                var order = $orderInput.length ? $orderInput.val() : '0';
                
                savedState[blockIndex].selections[postId] = {
                    checked: isChecked,
                    order: order
                };
            });
        });

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
            if (response.success) {
                $('#preview-content').html(response.data);
                
                // Restore editor states after preview update
                $('.block-item').each(function() {
                    var $block = $(this);
                    var blockIndex = $block.data('index');
                    var state = savedState[blockIndex];
                    var editorId = 'wysiwyg-editor-' + blockIndex;
                    
                    if (state) {
                        $block.find('.block-story-count').val(state.storyCount);
                        if (state.template_id) {
                            $block.find('.block-template').val(state.template_id);
                        }
                        
                        // Only restore WYSIWYG content if editor exists and content changed
                        if (state.type === 'wysiwyg' && state.wysiwyg !== undefined) {
                            if (editorStates[editorId] && typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                                var editor = tinyMCE.get(editorId);
                                var currentContent = editor.getContent();
                                if (currentContent !== state.wysiwyg) {
                                    editor.setContent(state.wysiwyg);
                                    try {
                                        if (editorStates[editorId].selection && editor.selection && !editor.isHidden()) {
                                            editor.selection.moveToBookmark(editorStates[editorId].selection);
                                        }
                                    } catch(e) {
                                        console.log('Could not restore editor selection for', editorId);
                                    }
                                }
                            }
                        } else if (state.type === 'html' && state.html !== undefined) {
                            $block.find('.html-block textarea').val(state.html);
                        }

                        Object.keys(state.selections).forEach(function(postId) {
                            var selection = state.selections[postId];
                            var $li = $block.find('li[data-post-id="' + postId + '"]');
                            if ($li.length) {
                                var $checkbox = $li.find('input[type="checkbox"][name*="[selected]"]');
                                var $orderInput = $li.find('.post-order');
                                
                                if ($checkbox.length) {
                                    $checkbox.prop('checked', selection.checked);
                                }
                                if ($orderInput.length) {
                                    $orderInput.val(selection.order);
                                }
                            }
                        });
                    }
                });
            }
        }).always(function() {
            previewUpdatePromise = null;
        });

        return previewUpdatePromise;
    };

    // Debounced updatePreview function
    window.updatePreview = function() {
        // If there's already an update in progress, queue this one
        if (globalUpdateInProgress) {
            if (window.updatePreviewTimeout) {
                clearTimeout(window.updatePreviewTimeout);
            }
            
            window.updatePreviewTimeout = setTimeout(function() {
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
                clearTimeout(window.updatePreviewTimeout);
                window.updatePreviewTimeout = setTimeout(function() {
                    updatePreview();
                }, 100);
            }
        }).catch(function() {
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