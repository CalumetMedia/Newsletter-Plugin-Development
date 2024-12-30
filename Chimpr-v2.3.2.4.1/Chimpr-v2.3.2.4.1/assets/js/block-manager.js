(function($) {
    // Global initialization
    window.blockManagerInitialized = false;
    
    // Use shared state management
    function isUpdateInProgress() {
        return typeof window.isPreviewUpdateInProgress === 'function' ? 
            window.isPreviewUpdateInProgress() : false;
    }

    function setUpdateInProgress(value) {
        if (typeof window.setPreviewUpdateInProgress === 'function') {
            window.setPreviewUpdateInProgress(value);
        }
    }

    // Update block indices after sorting
    window.updateBlockIndices = function() {
        console.log('Updating block indices');
        
        // Store editor contents before reindexing
        var editorContents = {};
        $('#blocks-container .block-item').each(function() {
            var oldEditorId = $(this).find('.wysiwyg-editor-content').attr('id');
            if (oldEditorId && tinymce.get(oldEditorId)) {
                editorContents[oldEditorId] = tinymce.get(oldEditorId).getContent();
                // Remove the editor instance
                tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
            }
        });
        
        // Update indices and names
        $('#blocks-container .block-item').each(function(index) {
            $(this).data('index', index);
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/blocks\[\d+\]/, 'blocks[' + index + ']');
                    $(this).attr('name', newName);
                    
                    // Update editor ID if this is a WYSIWYG textarea
                    if ($(this).hasClass('wysiwyg-editor-content')) {
                        var oldId = $(this).attr('id');
                        var newId = 'wysiwyg-editor-' + index;
                        $(this).attr('id', newId);
                        
                        // Initialize new editor with previous content
                        if (oldId && editorContents[oldId]) {
                            var content = editorContents[oldId];
                            setTimeout(function() {
                                wp.editor.initialize(newId, {
                                    tinymce: {
                                        wpautop: true,
                                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                                        setup: function(editor) {
                                            editor.on('init', function() {
                                                editor.setContent(content);
                                            });
                                            editor.on('change keyup paste input', function() {
                                                if (isUpdateInProgress()) return;
                                                editor.save();
                                                setUpdateInProgress(true);
                                                setTimeout(() => {
                                                    updatePreview('wysiwyg_content_change');
                                                    setUpdateInProgress(false);
                                                }, 250);
                                            });
                                        }
                                    },
                                    quicktags: true,
                                    mediaButtons: true
                                });
                            }, 100);
                        }
                    }
                }
            });
        });
        
        // Trigger preview update after reordering
        if (!isUpdateInProgress()) {
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('block_reorder');
                setUpdateInProgress(false);
            }, 250);
        }
    };
    
    // Initialize sortable for posts
    window.initializeSortable = function(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            // Destroy existing sortable if it exists
            if (sortableList.hasClass('ui-sortable')) {
                sortableList.sortable('destroy');
            }
            
            // Check manual override state
            var blockIndex = block.data('index');
            var manualOverride = block.find('input[name="blocks[' + blockIndex + '][manual_override]"]').prop('checked');
            
            // Initialize sortable
            sortableList.sortable({
                handle: '.story-drag-handle',
                items: '> li',
                axis: 'y',
                cursor: 'move',
                containment: 'parent',
                tolerance: 'pointer',
                disabled: !manualOverride,
                cancel: !manualOverride ? "*" : "",
                update: function(event, ui) {
                    if (!manualOverride) {
                        sortableList.sortable('cancel');
                        return false;
                    }
                    
                    // Update order values after sorting
                    sortableList.find('> li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    
                    // Trigger preview update
                    if (!isUpdateInProgress()) {
                        setUpdateInProgress(true);
                        setTimeout(() => {
                            updatePreview('sortable_update');
                            setUpdateInProgress(false);
                        }, 250);
                    }
                }
            }).disableSelection();

            // Set initial visual state
            updateBlockVisuals(block, manualOverride);
        }
    };

    // Handle block type changes (show/hide fields)
    window.handleBlockTypeChange = function(block, blockType) {
        block.find('.content-block').hide();
        block.find('.html-block').hide();
        block.find('.wysiwyg-block').hide();
        block.find('.category-select').hide();
        block.find('.template-select').hide();
        block.find('.date-range-row').hide();
        block.find('.story-count-row').hide();

        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.category-select').show();
            block.find('.template-select').show();
            block.find('.date-range-row').show();
            block.find('.story-count-row').show();
        } else if (blockType === 'html') {
            block.find('.html-block').show();
        } else if (blockType === 'wysiwyg') {
            block.find('.wysiwyg-block').show();
        }
    };

    // Initialize a single block with all necessary handlers and setup
    window.initializeBlock = function(block) {
        try {
            console.log('Initializing block:', block.data('index'));
            
            // Initialize sortable functionality
            initializeSortable(block);
            
            // Set initial state of story count dropdown based on manual override
            var isManual = block.find('input[name*="[manual_override]"]').prop('checked');
            var $storyCount = block.find('.block-story-count');
            $storyCount.prop('disabled', isManual);
            $storyCount.css('opacity', isManual ? '0.7' : '1');
            
            // Initialize WYSIWYG editors in this block
            block.find('.wysiwyg-editor-content').each(function() {
                var editorId = $(this).attr('id');
                if (typeof wp !== 'undefined' && wp.editor && editorId) {
                    try {
                        // Remove any existing editor first
                        wp.editor.remove(editorId);
                        
                        // Initialize the editor
                        wp.editor.initialize(editorId, {
                            tinymce: {
                                wpautop: true,
                                plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                                toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                                setup: function(editor) {
                                    editor.on('change keyup paste input', function() {
                                        console.log('WYSIWYG editor content changed');
                                        if (isUpdateInProgress()) return;
                                        
                                        editor.save();
                                        setUpdateInProgress(true);
                                        setTimeout(() => {
                                            updatePreview('wysiwyg_content_change');
                                            setUpdateInProgress(false);
                                        }, 250);
                                    });
                                }
                            },
                            quicktags: true,
                            mediaButtons: true
                        });
                    } catch (editorError) {
                        console.error('Error initializing editor:', editorError);
                    }
                }
            });

            // Initial block type setup
            var blockType = block.find('.block-type').val();
            handleBlockTypeChange(block, blockType);

            // Initial category load if needed
            const initialCategory = block.find('.block-category').val();
            if (initialCategory && !block.data('posts-loaded')) {
                const dateRange = block.find('.block-date-range').val();
                const blockIndex = block.data('index');
                const storyCount = block.find('.block-story-count').val();
                
                // Mark as loaded before making the request to prevent duplicate loads
                block.data('posts-loaded', true);
                
                loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount)
                    .catch(function(error) {
                        console.error('Error loading initial posts:', error);
                        // Reset the loaded flag on error so it can be retried
                        block.data('posts-loaded', false);
                    });
            }

            // Update visual state based on manual override
            updateBlockVisuals(block, isManual);

            console.log('Block initialization complete:', block.data('index'));
        } catch (error) {
            console.error('Error initializing block:', error);
            // Attempt to recover visual state
            try {
                updateBlockVisuals(block, block.find('input[name*="[manual_override]"]').prop('checked'));
            } catch (visualError) {
                console.error('Error recovering visual state:', visualError);
            }
        }
    };

    // Utility functions for post data handling
    function collectBlockData($block, isManual) {
        return {
            type: $block.find('.block-type').val(),
            title: $block.find('.block-title-input').val(),
            show_title: $block.find('.show-title-toggle').prop('checked') ? 1 : 0,
            template_id: $block.find('.block-template').val(),
            category: $block.find('.block-category').val(),
            date_range: $block.find('.block-date-range').val(),
            story_count: $block.find('.block-story-count').val(),
            manual_override: isManual ? 1 : 0,
            posts: collectPostData($block)
        };
    }

    function collectPostData($block) {
        var posts = {};
        $block.find('.block-posts li').each(function() {
            var $post = $(this);
            var postId = $post.data('post-id');
            var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            var $orderInput = $post.find('.post-order');
            
            // Only store data for checked posts
            if ($checkbox.prop('checked')) {
                console.log('Post ' + postId + ' is checked, storing data');
                posts[postId] = {
                    checked: '1',
                    order: $orderInput.val() || '9223372036854775807'
                };
            }
        });
        return posts;
    }

    function saveBlockState($block, isManual, callback) {
        var blockIndex = $block.data('index');
        var blocks = [];
        blocks[blockIndex] = collectBlockData($block, isManual);

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
                if (response.success && callback) {
                    callback(response);
                }
            }
        });
    }

    function updateBlockVisuals($block, isManual) {
        var $postsList = $block.find('.sortable-posts');
        var $checkboxes = $postsList.find('input[type="checkbox"]');
        
        // Update visual state
        $postsList.css({
            'pointer-events': isManual ? 'auto' : 'none',
            'opacity': isManual ? '1' : '0.7'
        });
        
        // Enable/disable checkboxes
        $checkboxes.prop('disabled', !isManual);
        
        // Update drag handles cursor
        $postsList.find('.story-drag-handle').css('cursor', isManual ? 'move' : 'default');
        
        // Update story count dropdown
        var $storyCount = $block.find('.block-story-count');
        $storyCount.prop('disabled', isManual);
        $storyCount.css('opacity', isManual ? '0.7' : '1');
    }

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount) {
        var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked');
        console.log('Loading posts with manual override:', manualOverride);
        
        // Collect current selections before loading
        var currentSelections = collectPostData(block);
        console.log('Current selections before loading:', currentSelections);
        
        var savedSelections = { 
            [currentIndex]: { 
                posts: currentSelections,
                manual_override: manualOverride ? '1' : '',
                storyCount: storyCount
            } 
        };

        var data = {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            date_range: dateRange,
            story_count: storyCount,
            newsletter_slug: newsletterData.newsletterSlug,
            saved_selections: JSON.stringify(savedSelections),
            manual_override: manualOverride ? '1' : ''
        };

        return $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: data,
            beforeSend: function() {
                block.find('.block-posts').addClass('loading');
            },
            success: function(response) {
                block.find('.block-posts').removeClass('loading');
                
                if (response.success && response.data) {
                    var $postsContainer = block.find('.block-posts');
                    
                    try {
                        $postsContainer.empty();
                        var $temp = $('<div>').html(response.data);
                        
                        // If in manual override mode, restore saved selections
                        if (manualOverride) {
                            console.log('Restoring selections in manual mode:', currentSelections);
                            $temp.find('input[type="checkbox"]').each(function() {
                                var $checkbox = $(this);
                                var postId = $checkbox.closest('li').data('post-id');
                                // If post exists in selections and is checked, check it
                                if (currentSelections[postId] && currentSelections[postId].checked === '1') {
                                    console.log('Setting checkbox checked for post', postId);
                                    $checkbox.prop('checked', true);
                                } else {
                                    console.log('Setting checkbox unchecked for post', postId);
                                    $checkbox.prop('checked', false);
                                }
                            });
                        }
                        
                        $postsContainer.append($temp.children());
                        initializeSortable(block);
                        
                        // Save state and update preview
                        saveBlockState(block, manualOverride, function() {
                            if (!isUpdateInProgress()) {
                                setUpdateInProgress(true);
                                setTimeout(() => {
                                    updatePreview('posts_loaded');
                                    setUpdateInProgress(false);
                                }, 250);
                            }
                        });
                    } catch (error) {
                        console.error('Error updating content:', error);
                        $postsContainer.html(response.data);
                    }
                }
            }
        });
    };

    // Handle manual override toggle
    function handleManualOverrideToggle($block, isManual) {
        console.log('Manual override toggled:', isManual);
        
        // Save current selections before updating visuals
        const currentSelections = collectPostData($block);
        console.log('Current selections before toggle:', currentSelections);
        
        updateBlockVisuals($block, isManual);
        
        const categoryId = $block.find('.block-category').val();
        if (!categoryId) return;

        const dateRange = $block.find('.block-date-range').val();
        const blockIndex = $block.data('index');
        const storyCount = $block.find('.block-story-count').val();

        // First save the current state with manual override flag
        saveBlockState($block, isManual, function() {
            console.log('State saved with manual override:', isManual);
            
            // Then load posts, preserving selections in manual mode
            loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                .then(() => {
                    if (isManual) {
                        // Restore saved selections in manual mode
                        console.log('Restoring selections in manual mode:', currentSelections);
                        $block.find('input[type="checkbox"]').prop('checked', false); // Reset all checkboxes
                        Object.entries(currentSelections).forEach(([postId, data]) => {
                            const $checkbox = $block.find(`li[data-post-id="${postId}"] input[type="checkbox"]`);
                            if ($checkbox.length && data.checked === '1') {
                                $checkbox.prop('checked', true);
                            }
                        });
                        
                        // Save restored state
                        saveBlockState($block, isManual);
                    }
                    
                    if (!isUpdateInProgress()) {
                        setUpdateInProgress(true);
                        setTimeout(() => {
                            updatePreview('manual_override_change');
                            setUpdateInProgress(false);
                        }, 250);
                    }
                });
        });
    }

    // Event handlers for blocks
    function setupBlockEventHandlers(block) {
        // Initialize WYSIWYG editors in this block
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            if (typeof wp !== 'undefined' && wp.editor && editorId) {
                // Remove any existing editor first
                wp.editor.remove(editorId);
                
                // Initialize the editor
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                        setup: function(editor) {
                            editor.on('change keyup paste input', function() {
                                console.log('WYSIWYG editor content changed');
                                if (isUpdateInProgress()) return;
                                
                                editor.save();
                                setUpdateInProgress(true);
                                setTimeout(() => {
                                    updatePreview('wysiwyg_content_change');
                                    setUpdateInProgress(false);
                                }, 250);
                            });
                        }
                    },
                    quicktags: true,
                    mediaButtons: true
                });

                // Handle direct textarea changes for HTML view
                $('#' + editorId).on('change keyup paste input', function() {
                    console.log('WYSIWYG textarea content changed');
                    if (isUpdateInProgress()) return;
                    
                    setUpdateInProgress(true);
                    setTimeout(() => {
                        updatePreview('wysiwyg_content_change');
                        setUpdateInProgress(false);
                    }, 250);
                });
            }
        });

        // Add checkbox change handler for posts
        block.find('.block-posts').off('change', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]').on('change', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]', function() {
            console.log('Checkbox changed!');
            if (isUpdateInProgress()) {
                console.log('Update in progress, skipping...');
                return;
            }
            
            console.log('Triggering preview update for checkbox change');
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('post_selection_change');
                setUpdateInProgress(false);
            }, 250);
        });

        // Story count change handler
        block.find('.block-story-count').off('change').on('change', function() {
            if (isUpdateInProgress()) return;
            
            setUpdateInProgress(true);
            const $block = $(this).closest('.block-item');
            console.log('[Preview Debug] Story count changed - Block:', $block.data('index'), 'New count:', $(this).val());
            const categoryId = $block.find('.block-category').val();
            const dateRange = $block.find('.block-date-range').val();
            const blockIndex = $block.data('index');
            const storyCount = $(this).val();

            if (categoryId) {
                console.log('[Preview Debug] Before loadBlockPosts - Category:', categoryId, 'Story Count:', storyCount);
                loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                    .then(() => {
                        console.log('[Preview Debug] After loadBlockPosts success - Selected posts:', $block.find('input[type="checkbox"]:checked').length);
                        setTimeout(() => {
                            console.log('[Preview Debug] Before updatePreview - Block state:', {
                                category: $block.find('.block-category').val(),
                                storyCount: $block.find('.block-story-count').val(),
                                selectedPosts: $block.find('input[type="checkbox"]:checked').length
                            });
                            updatePreview('story_count_change');
                            setUpdateInProgress(false);
                        }, 250);
                    });
            }
        });

        // Category and date range change handlers
        block.find('.block-category, .block-date-range').off('change').on('change', function() {
            if (isUpdateInProgress()) return;
            
            setUpdateInProgress(true);
            const $block = $(this).closest('.block-item');
            const categoryId = $block.find('.block-category').val();
            const dateRange = $block.find('.block-date-range').val();
            const blockIndex = $block.data('index');
            const storyCount = $block.find('.block-story-count').val();

            console.log('[Preview Debug] Category/Date changed - Block state:', {
                block: blockIndex,
                category: categoryId,
                dateRange: dateRange,
                storyCount: storyCount
            });

            if (categoryId) {
                loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                    .then(() => {
                        console.log('[Preview Debug] After category/date loadBlockPosts - Selected:', $block.find('input[type="checkbox"]:checked').length);
                        setTimeout(() => {
                            console.log('[Preview Debug] Before category/date updatePreview');
                            updatePreview('category_date_change');
                            setUpdateInProgress(false);
                        }, 250);
                    });
            }
        });

        // WYSIWYG and HTML content change handlers
        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            if (isUpdateInProgress()) return;
            
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('content_change');
                setUpdateInProgress(false);
            }, 250);
        });

        // Block type change handler
        block.find('.block-type').off('change').on('change', function() {
            if (isUpdateInProgress()) return;
            
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('block_type_change');
                setUpdateInProgress(false);
            }, 250);
        });
    }

    // Add a new block
    window.addBlock = function() {
        var blockIndex = $('#blocks-container .block-item').length;
        var blockHtml = `
            <div class="block-item" data-index="${blockIndex}">
                <h3 class="block-header">
                    <div style="display: flex; align-items: center; width: 100%;">
                        <span class="dashicons dashicons-sort block-drag-handle"></span>
                        <span class="block-title" style="flex: 1; font-size: 14px; margin: 0 10px;">${newsletterData.blockLabel}</span>
                    </div>
                </h3>
                <div class="block-content">
                    <div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="width: 25%;">
                            <label>${newsletterData.blockTitleLabel}</label>
                            <input type="text" name="blocks[${blockIndex}][title]" class="block-title-input" value="" style="width: 100%; height: 36px;" />
                        </div>
                        <div style="margin-left: 15px;">
                            <label>
                                <input type="checkbox" name="blocks[${blockIndex}][show_title]" class="show-title-toggle" value="1" checked>
                                Show Title in Preview
                            </label>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                        <div style="width: 200px;">
                            <label>${newsletterData.blockTypeLabel}</label>
                            <select name="blocks[${blockIndex}][type]" class="block-type" style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                                <option value="content">Content</option>
                                <option value="html">HTML</option>
                                <option value="wysiwyg">WYSIWYG Editor</option>
                            </select>
                        </div>

                        <div style="width: 200px;" class="category-select">
                            <label>${newsletterData.selectCategoryLabel}</label>
                            <select name="blocks[${blockIndex}][category]" class="block-category" style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                                <option value="">${newsletterData.selectCategoryOption}</option>
                                ${newsletterData.categories.map(category => 
                                    `<option value="${category.id}">${category.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                
                        <div style="width: 200px;" class="template-select">
                            <label>${newsletterData.templateLabel}</label>
                            <select name="blocks[${blockIndex}][template_id]" class="block-template" style="width: 100%; height: 36px; line-height: 1.4; padding: 0 6px;">
                                ${newsletterData.availableTemplates.map(template => 
                                    `<option value="${template.id}">${template.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="date-range-row" style="margin-bottom: 10px;">
                        <label>Date Range:</label>
                        <select name="blocks[${blockIndex}][date_range]" class="block-date-range" style="width: 200px; height: 36px; line-height: 1.4; padding: 0 6px;">
                            <option value="1">Previous 1 Day</option>
                            <option value="2">Previous 2 Days</option>
                            <option value="3">Previous 3 Days</option>
                            <option value="5">Previous 5 Days</option>
                            <option value="7" selected>Previous 7 Days</option>
                            <option value="14">Previous 14 Days</option>
                            <option value="30">Previous 30 Days</option>
                            <option value="60">Previous 60 Days</option>
                            <option value="90">Previous 90 Days</option>
                            <option value="0">All</option>
                        </select>
                    </div>

                    <div class="story-count-row" style="margin-bottom: 10px;">
                        <label>Number of Stories:</label>
                        <select name="blocks[${blockIndex}][story_count]" class="block-story-count" style="width: 200px; height: 36px; line-height: 1.4; padding: 0 6px;">
                            <option value="disable" selected>All</option>
                            ${Array.from({length: 10}, (_, i) => `<option value="${i + 1}">${i + 1}</option>`).join('')}
                        </select>
                    </div>

                    <div class="content-block">
                        <div class="block-posts">
                            <p>${newsletterData.selectCategoryPrompt}</p>
                        </div>
                    </div>

                    <div class="html-block" style="display:none;">
                        <label>${newsletterData.customHtmlLabel}</label>
                        <textarea name="blocks[${blockIndex}][html]" rows="5" style="width:100%;"></textarea>
                    </div>

                    <div class="wysiwyg-block" style="display:none;">
                        <label>WYSIWYG Content:</label>
                        <textarea name="blocks[${blockIndex}][wysiwyg]" class="wysiwyg-editor-content" id="wysiwyg-editor-${blockIndex}"></textarea>
                    </div>

                    <button type="button" class="button remove-block">${newsletterData.removeBlockLabel}</button>
                </div>
            </div>
        `;

        $('#blocks-container').append(blockHtml);
        var newBlock = $('#blocks-container .block-item').last();
        
        initializeBlock(newBlock);

        // Reinitialize accordion for all blocks
        $("#blocks-container").accordion('destroy').accordion({
            header: ".block-header",
            collapsible: true,
            active: blockIndex,
            heightStyle: "content",
            icons: false
        });

        // After adding a new block, update the preview
        updatePreview('new_block_added');
    };

    $(document).ready(function() {
        // Prevent multiple initializations
        if (window.blockManagerInitialized) {
            return;
        }
        window.blockManagerInitialized = true;

        if ($.fn.accordion) {
            $('#blocks-container').accordion({
                header: '.block-header',
                icons: false,
                heightStyle: "content",
                collapsible: true,
                active: false
            });
        }

        // Initialize existing blocks
        $('#blocks-container .block-item').each(function() {
            initializeBlock($(this));
        });

        // Remove block triggers a preview update
        $(document).on('click', '.remove-block', function() {
            var block = $(this).closest('.block-item');
            block.remove();
            updatePreview('block_removed');
        });

        // Handle manual override toggle
        $(document).off('change', 'input[name*="[manual_override]"]')
            .on('change', 'input[name*="[manual_override]"]', function() {
                handleManualOverrideToggle($(this).closest('.block-item'), $(this).prop('checked'));
            });
    });
})(jQuery);