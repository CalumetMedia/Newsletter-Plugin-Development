(function($) {
    // Global initialization
    window.blockManagerInitialized = false;
    
    // Initialize newsletter state if not already done
    if (!window.newsletterState) {
        window.newsletterState = {
            blocksLoaded: 0,
            totalBlocks: 0,
            postsData: {},
            isReady: false,
            isUpdateInProgress: false
        };
    }
    
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
            var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked');
            
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
            });

            // Enable/disable sortable based on manual override
            if (manualOverride) {
                sortableList.sortable('enable');
            } else {
                sortableList.sortable('disable');
            }

            // Set initial visual state
            updateBlockVisuals(block, manualOverride);
        }
    };

    // Handle block type changes (show/hide fields)
    window.handleBlockTypeChange = function(block, blockType) {
        console.log('Block type change triggered:', blockType);
        
        // Prevent multiple initializations
        if (block.data('type-change-in-progress')) {
            console.log('Type change already in progress, skipping');
            return;
        }
        block.data('type-change-in-progress', true);
        
        // Clean up any existing WYSIWYG editor
        var blockIndex = block.data('index');
        var editorId = 'wysiwyg-editor-' + blockIndex;
        console.log('Editor ID for block', blockIndex, ':', editorId);
        
        // Store existing content before cleanup
        var existingContent = '';
        if (tinymce.get(editorId)) {
            existingContent = tinymce.get(editorId).getContent();
            console.log('Preserving existing content:', existingContent);
            tinymce.execCommand('mceRemoveEditor', true, editorId);
        } else if ($('#' + editorId).length) {
            existingContent = $('#' + editorId).val();
            console.log('Preserving textarea content:', existingContent);
        }

        block.find('.content-block').hide();
        block.find('.html-block').hide();
        block.find('.wysiwyg-block').hide();
        
        // Enable/disable fields based on block type
        var isContentType = blockType === 'content';
        block.find('.category-select select, .template-select select, .date-range-row select, .story-count-row select, .manual-override-toggle')
            .prop('disabled', !isContentType);
        
        // Set opacity for visual feedback
        block.find('.category-select, .template-select, .date-range-row, .story-count-row')
            .css('opacity', isContentType ? '1' : '0.7');
        
        if (blockType === 'content') {
            block.find('.content-block').show();
        } else if (blockType === 'html') {
            block.find('.html-block').show();
        } else if (blockType === 'wysiwyg') {
            console.log('Showing WYSIWYG block');
            var $container = block.find('.wysiwyg-block');
            $container.show();
            
            // Initialize WYSIWYG editor after showing the block
            if (typeof wp !== 'undefined' && wp.editor) {
                console.log('Initializing editor for:', editorId);
                
                // Ensure we have a clean textarea with preserved content
                $container.find('textarea').remove(); // Remove any existing textarea
                $container.append(
                    '<textarea id="' + editorId + '" ' +
                    'name="blocks[' + blockIndex + '][wysiwyg]" ' +
                    'class="wysiwyg-editor-content">' + existingContent + '</textarea>'
                );
                
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    if (tinymce.get(editorId)) {
                        console.log('Warning: Editor already exists:', editorId);
                        block.data('type-change-in-progress', false);
                        return;
                    }
                    
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                            toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                            setup: function(editor) {
                                console.log('Editor setup complete:', editor.id);
                                editor.on('change keyup paste input', function() {
                                    if (isUpdateInProgress()) return;
                                    editor.save();
                                    setUpdateInProgress(true);
                                    setTimeout(() => {
                                        updatePreview('wysiwyg_content_change');
                                        setUpdateInProgress(false);
                                    }, 250);
                                });
                            },
                            init_instance_callback: function(editor) {
                                console.log('Editor initialized:', editor.id);
                                block.data('type-change-in-progress', false);
                            }
                        },
                        quicktags: true,
                        mediaButtons: true
                    });
                }, 100);
            } else {
                block.data('type-change-in-progress', false);
            }
        } else {
            block.data('type-change-in-progress', false);
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
            
            // Ensure story count is properly initialized
            console.log('Initializing story count - Manual override:', isManual);
            $storyCount.prop('disabled', isManual);
            $storyCount.css('opacity', isManual ? '0.7' : '1');
            
            // Initial block type setup
            var blockType = block.find('.block-type').val();
            handleBlockTypeChange(block, blockType);

            // Set up all event handlers for this block
            console.log('Setting up block event handlers');
            setupBlockEventHandlers(block);
            
            // Initial category load if needed
            const initialCategory = block.find('.block-category').val();
            if (initialCategory && !block.data('posts-loaded')) {
                const dateRange = block.find('.block-date-range').val();
                const blockIndex = block.data('index');
                const storyCount = block.find('.block-story-count').val();
                
                // Mark as loaded before making the request to prevent duplicate loads
                block.data('posts-loaded', true);
                
                // Wrap the initial load in a promise
                new Promise((resolve) => {
                    // First save the initial state
                    saveBlockState(block, isManual, () => {
                        // Then load posts
                        loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount)
                            .then(() => {
                                // After loading posts, handle story count if needed
                                if (!isManual && storyCount !== 'disable') {
                                    console.log('Triggering initial story count handling');
                                    // Don't trigger the change event, call the handler directly
                                    handleStoryCountChange(block, storyCount);
                                }
                                resolve();
                            })
                            .catch(function(error) {
                                console.error('Error loading initial posts:', error);
                                block.data('posts-loaded', false);
                                resolve();
                            });
                    });
                });
            } else if (!isManual && $storyCount.val() !== 'disable') {
                // If no category load needed but story count needs handling
                handleStoryCountChange(block, $storyCount.val());
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

    // New function to handle story count changes without triggering events
    window.handleStoryCountChange = function(block, storyCountVal, skipPreview = false) {
        if (isUpdateInProgress()) return;
        
        const manualOverride = block.find('input[name*="[manual_override]"]').prop('checked');
        if (manualOverride) return;

        setUpdateInProgress(true);
        const $postsList = block.find('.sortable-posts');
        const $checkboxInputs = $postsList.find('input[type="checkbox"][name*="[posts]"][name*="[checked]"]');
        
        console.log('Found checkboxes:', $checkboxInputs.length);
        console.log('Setting story count to:', storyCountVal);
        
        // Reset all checkboxes first
        $checkboxInputs.each(function() {
            $(this).prop('checked', false).attr('checked', false);
            $(this).closest('li').find('.post-order').val('');
        });
        
        // If story count is not "disable", check the specified number of stories
        if (storyCountVal !== 'disable' && parseInt(storyCountVal) > 0) {
            const maxStories = parseInt(storyCountVal);
            console.log('Setting first', maxStories, 'stories as checked');
            
            // Get all list items and sort by date
            const $items = $postsList.find('li').sort(function(a, b) {
                var dateA = new Date($(a).data('post-date'));
                var dateB = new Date($(b).data('post-date'));
                return dateB - dateA; // Sort in descending order (newest first)
            });
            
            // Log current DOM order
            console.log('Current DOM order before setting checkboxes:');
            $items.each(function(idx) {
                const postId = $(this).data('post-id');
                const title = $(this).find('label').text().trim();
                console.log(`[${idx}] Post ID: ${postId}, Title: ${title}`);
            });
            
            // Check and set order for the first N items
            $items.each(function(index) {
                if (index < maxStories) {
                    console.log('Checking checkbox at index:', index);
                    const $item = $(this);
                    const $checkbox = $item.find('input[type="checkbox"]');
                    const postId = $item.data('post-id');
                    
                    $checkbox.prop('checked', true).attr('checked', 'checked');
                    // Set order to match date-sorted position
                    $item.find('.post-order').val(index);
                    
                    console.log('Set order', index, 'for post', postId);
                }
            });
            
            // Reorder items in the DOM to match the sorted order
            const $container = $postsList;
            $items.each(function() {
                $container.append(this);
            });
        } else {
            console.log('Setting all stories as checked');
            // If "All" is selected or invalid count, check all stories and sort by date
            const $items = $postsList.find('li').sort(function(a, b) {
                var dateA = new Date($(a).data('post-date'));
                var dateB = new Date($(b).data('post-date'));
                return dateB - dateA; // Sort in descending order (newest first)
            });
            
            $items.each(function(index) {
                const $item = $(this);
                const $checkbox = $item.find('input[type="checkbox"]');
                const postId = $item.data('post-id');
                
                $checkbox.prop('checked', true).attr('checked', 'checked');
                // Set order to match date-sorted position
                $item.find('.post-order').val(index);
                console.log('Set order', index, 'for post', postId);
            });
            
            // Reorder items in the DOM to match the sorted order
            const $container = $postsList;
            $items.each(function() {
                $container.append(this);
            });
        }

        // Save state and update preview if not skipping
        saveBlockState(block, false, function() {
            console.log('Block state saved, updating preview');
            if (!skipPreview) {
                updatePreview('story_count_change');
            }
            setUpdateInProgress(false);
        });
    };

    // Event handlers for blocks
    function setupBlockEventHandlers(block) {
        // Title change handler
        block.find('.block-title-input').off('change keyup paste input').on('change keyup paste input', function() {
            if (isUpdateInProgress()) return;
            
            console.log('Title changed:', $(this).val());
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('title_change');
                setUpdateInProgress(false);
            }, 250);
        });

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
                    
                    clearTimeout(this.textareaTimer);
                    this.textareaTimer = setTimeout(() => {
                        var content = $(this).val();
                        var previousContent = $(this).data('previous-content');
                        
                        // Only trigger update if content has actually changed
                        if (content !== previousContent) {
                            $(this).data('previous-content', content);
                            setUpdateInProgress(true);
                            updatePreview('wysiwyg_content_change');
                            setUpdateInProgress(false);
                        }
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
        block.find('.block-story-count').off('change.storyCount change').on('change.storyCount', function(e) {
            console.log('Story count change triggered - Value:', $(this).val());
            handleStoryCountChange(block, $(this).val());
        });

        // Category and date range change handlers
        block.find('.block-category, .block-date-range').off('change').on('change', function() {
            if (isUpdateInProgress()) return;
            
            setUpdateInProgress(true);
            const $block = $(this).closest('.block-item');
            const manualOverride = $block.find('input[name*="[manual_override]"]').prop('checked');
            const categoryId = $block.find('select[name*="[category]"]').val();
            const dateRange = $block.find('select[name*="[date_range]"]').val();
            const blockIndex = $block.data('index');
            const storyCount = $block.find('select[name*="[story_count]"]').val();

            console.log('[Preview Debug] Category/Date changed - Block state:', {
                block: blockIndex,
                category: categoryId,
                dateRange: dateRange,
                storyCount: storyCount,
                manualOverride: manualOverride
            });

            if (categoryId) {
                // First save current state
                saveBlockState($block, manualOverride, function() {
                    loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                        .then(() => {
                            // After loading posts, ensure story count is respected if not in manual mode
                            if (!manualOverride && storyCount !== 'disable') {
                                const storyItems = $block.find('.sortable-post-item');
                                const maxStories = parseInt(storyCount);
                                
                                // Sort story items by date when not in manual mode
                                const sortedItems = Array.from(storyItems).sort((a, b) => {
                                    const dateA = new Date($(a).data('post-date'));
                                    const dateB = new Date($(b).data('post-date'));
                                    return dateB - dateA; // Sort in descending order (newest first)
                                });
                                
                                // Update order based on sorted items
                                sortedItems.forEach((item, index) => {
                                    const $item = $(item);
                                    const $checkbox = $item.find('input[type="checkbox"]');
                                    const shouldBeChecked = index < maxStories;
                                    $checkbox.prop('checked', shouldBeChecked);
                                    // Update order for checked items
                                    if (shouldBeChecked) {
                                        $item.find('.post-order').val(index + 1);
                                    }
                                });
                                
                                // Reorder items in the DOM to match the sorted order
                                const $container = storyItems.first().parent();
                                sortedItems.forEach(item => {
                                    $container.append(item);
                                });
                                
                                // Save the updated state
                                saveBlockState($block, false);
                            }
                            
                            setTimeout(() => {
                                console.log('[Preview Debug] Before category/date updatePreview');
                                updatePreview('category_date_change');
                                setUpdateInProgress(false);
                            }, 250);
                        });
                });
            } else {
                setUpdateInProgress(false);
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

        // Helper function to initialize WYSIWYG editor
        function initializeWysiwygEditor($block, editorId) {
            // First remove any existing editor instances
            if (tinymce.get(editorId)) {
                tinymce.execCommand('mceRemoveEditor', true, editorId);
            }
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.remove(editorId);
            }

            // Force textarea to be visible and ensure proper display
            var $textarea = $('#' + editorId);
            $textarea.show().css({
                'visibility': 'visible',
                'display': 'block',
                'height': '300px'  // Set a default height
            });
            $block.find('.wysiwyg-block').show().css('display', 'block');

            // Initialize the editor after a delay
            setTimeout(function() {
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                            toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                            setup: function(editor) {
                                editor.on('init', function() {
                                    editor.show();
                                    var $iframe = $('#' + editorId + '_ifr');
                                    $iframe.show().css({
                                        'visibility': 'visible',
                                        'display': 'block',
                                        'height': '300px'
                                    });
                                    $('.mce-tinymce').show().css('display', 'block');
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
                }
            }, 100);
        }

        // Block type change handler
        block.find('.block-type').off('change').on('change', function(e) {
            // Prevent event from bubbling up
            e.stopPropagation();
            
            if (isUpdateInProgress()) return;
            
            var newBlockType = $(this).val();
            var $block = $(this).closest('.block-item');
            var blockIndex = $block.data('index');
            
            // First clean up any existing editors
            var oldEditorId = 'wysiwyg-editor-' + blockIndex;
            if (tinymce.get(oldEditorId)) {
                tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(oldEditorId);
                }
            }

            // Handle visibility changes
            handleBlockTypeChange($block, newBlockType);
            
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

    // Initialize all blocks
    function initializeBlocks() {
        // Initialize newsletter state
        window.newsletterState = {
            blocksLoaded: 0,
            totalBlocks: $('.block-item').length,
            postsData: {},
            isReady: false,
            isUpdateInProgress: false
        };

        var blocks = $('.block-item');
        var totalBlocks = blocks.length;
        var loadedBlocks = 0;
        
        console.log('Initializing', totalBlocks, 'blocks');
        
        // If no blocks exist, set state to ready immediately
        if (totalBlocks === 0) {
            console.log('No blocks to initialize, setting state to ready');
            window.newsletterState.isReady = true;
            return;
        }
        
        // Initialize each block
        blocks.each(function(index) {
            var block = $(this);
            // Set the block index explicitly
            block.attr('data-block-index', index);
            setupBlockEventHandlers(block);
            
            // Get initial values - check both class and name attribute for category
            var categorySelect = block.find('.block-category');
            var categoryId = categorySelect.val();
            var currentIndex = index;
            var dateRange = block.find('.block-date-range').val() || '7';
            var storyCount = block.find('.block-story-count').val() || 'disable';
            var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked') || false;
            
            console.log('Initializing block', currentIndex, {
                categoryId: categoryId,
                categorySelectExists: categorySelect.length > 0,
                categorySelectValue: categorySelect.val(),
                manualOverride: manualOverride,
                blockHtml: block.html().substring(0, 100) // Log first 100 chars of block HTML for debugging
            });
            
            // Mark block as loaded immediately if no category is selected
            if (!categoryId) {
                loadedBlocks++;
                window.newsletterState.blocksLoaded = loadedBlocks;
                console.log('Block', currentIndex, 'has no category, marking as loaded.');
                
                if (loadedBlocks === totalBlocks) {
                    console.log('All blocks processed, setting state to ready');
                    window.newsletterState.isReady = true;
                    updatePreview('initialization_complete');
                }
                return;
            }
            
            // Only attempt to load posts if we have a valid category
            console.log('Loading posts for block', currentIndex, 'with category', categoryId);
            loadBlockPosts(block, categoryId, currentIndex, dateRange, storyCount, true)
                .then(function() {
                    loadedBlocks++;
                    console.log('Block', currentIndex, 'loaded.', loadedBlocks, 'of', totalBlocks, 'blocks loaded');
                    
                    // Update newsletter state
                    window.newsletterState.blocksLoaded = loadedBlocks;
                    
                    // Only update preview after all blocks are loaded
                    if (loadedBlocks === totalBlocks) {
                        console.log('All blocks loaded, setting state to ready');
                        window.newsletterState.isReady = true;
                        updatePreview('initialization_complete');
                    }
                })
                .catch(function(error) {
                    console.error('Error loading block', currentIndex, ':', error);
                    loadedBlocks++;
                    window.newsletterState.blocksLoaded = loadedBlocks;
                    
                    // Still check if all blocks are loaded, even if some failed
                    if (loadedBlocks === totalBlocks) {
                        console.log('All blocks processed (with errors), setting state to ready');
                        window.newsletterState.isReady = true;
                        updatePreview('initialization_complete');
                    }
                });
        });
    }

    // Call initialization when document is ready
    $(document).ready(function() {
        initializeBlocks();
    });

    // Utility functions for post data handling
    window.collectPostData = function($block) {
        var posts = {};
        
        // Get all list items in their current DOM order
        var $items = $block.find('.block-posts li');
        
        // Store posts in their current order
        $items.each(function(index) {
            var $post = $(this);
            var postId = $post.data('post-id');
            var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            var currentOrder = $post.find('.post-order').val();
            
            // Only store checked posts
            if ($checkbox.prop('checked')) {
                posts[postId] = {
                    checked: '1',
                    order: currentOrder || index.toString()
                };
            }
        });
        
        return posts;
    };

    window.saveBlockState = function($block, isManual, callback) {
        var blocks = [];
        
        // Collect all blocks
        $('#blocks-container .block-item').each(function(index) {
            var $currentBlock = $(this);
            var blockType = $currentBlock.find('.block-type').val();
            
            // Build the base block data
            var blockData = {
                type: blockType,
                title: $currentBlock.find('.block-title-input').val(),
                show_title: $currentBlock.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $currentBlock.find('.block-template').val() || '0',
                category: $currentBlock.find('.block-category').val() || '',
                date_range: $currentBlock.find('.block-date-range').val() || '7',
                story_count: $currentBlock.find('.block-story-count').val() || 'disable',
                manual_override: ($currentBlock.is($block) ? isManual : $currentBlock.find('.manual-override-toggle').prop('checked')) ? 1 : 0,
                posts: collectPostData($currentBlock)
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
                if (response.success && callback) {
                    callback(response);
                }
            }
        });
    };

    window.updateBlockVisuals = function($block, isManual) {
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
    };

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount, skipPreview = false) {
        // Ensure we have a valid block and category
        if (!block || !categoryId) {
            console.log('Invalid block or category:', { block: !!block, categoryId });
            return Promise.resolve();
        }

        var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked') || false;
        console.log('Loading posts for block', currentIndex, 'with manual override:', manualOverride);
        
        // Collect current selections before loading
        var currentSelections = collectPostData(block);
        console.log('Current selections before loading:', currentSelections);
        
        var savedSelections = { 
            [currentIndex]: { 
                posts: currentSelections,
                manual_override: manualOverride ? '1' : '0',
                story_count: storyCount
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
            manual_override: manualOverride ? 'true' : 'false'  // Match PHP boolean string check
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
                            console.log('Restoring selections in manual mode for block', currentIndex, ':', currentSelections);
                            $temp.find('input[type="checkbox"]').each(function() {
                                var $checkbox = $(this);
                                var postId = $checkbox.closest('li').data('post-id');
                                // If post exists in selections and is checked, check it
                                if (currentSelections[postId] && currentSelections[postId].checked === '1') {
                                    console.log('Setting checkbox checked for post', postId);
                                    $checkbox.prop('checked', true);
                                    // Also ensure the order is preserved
                                    $checkbox.closest('li').find('.post-order').val(currentSelections[postId].order || '0');
                                } else {
                                    console.log('Setting checkbox unchecked for post', postId);
                                    $checkbox.prop('checked', false);
                                }
                            });
                        }
                        
                        $postsContainer.append($temp.children());
                        initializeSortable(block);
                        
                        // Save state and update preview if not skipping
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
                        console.error('Error updating content:', error);
                        $postsContainer.html(response.data);
                        return Promise.reject(error);
                    }
                }
            }
        });
    };

    // Handle manual override toggle
    window.handleManualOverrideToggle = function($block, isManual) {
        console.log('Manual override toggled:', isManual);
        
        // Prevent any ongoing updates
        if (isUpdateInProgress()) {
            console.log('Update in progress, deferring toggle');
            setTimeout(function() {
                handleManualOverrideToggle($block, isManual);
            }, 250);
            return;
        }
        
        setUpdateInProgress(true);
        
        // Update visual state first
        updateBlockVisuals($block, isManual);
        
        // Initialize sortable with new state
        var $sortableList = $block.find('.sortable-posts');
        if ($sortableList.length) {
            if ($sortableList.hasClass('ui-sortable')) {
                $sortableList.sortable('destroy');
            }
            
            $sortableList.sortable({
                handle: '.story-drag-handle',
                items: '> li',
                axis: 'y',
                cursor: 'move',
                containment: 'parent',
                tolerance: 'pointer',
                disabled: !isManual,
                cancel: !isManual ? "*" : "",
                update: function(event, ui) {
                    if (!isManual) {
                        $sortableList.sortable('cancel');
                        return false;
                    }
                    
                    // Update order values after sorting
                    $sortableList.find('> li').each(function(index) {
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
            });

            if (isManual) {
                $sortableList.sortable('enable').disableSelection();
            } else {
                $sortableList.sortable('disable');
            }
        }
        
        // Save the state with manual override flag
        saveBlockState($block, isManual, function() {
            console.log('State saved with manual override:', isManual);
            
            // Only handle story count if switching to non-manual mode
            if (!isManual && $block.find('.block-story-count').val() !== 'disable') {
                handleStoryCountChange($block, $block.find('.block-story-count').val(), true); // Skip preview update
            }
            
            setUpdateInProgress(false);
            
            // Single preview update after all changes
            updatePreview('manual_override_change');
        });
    };
})(jQuery);