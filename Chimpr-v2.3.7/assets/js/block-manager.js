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
            isUpdateInProgress: false,
            editorContents: {},
            pendingUpdates: new Set()
        };
    }
    
    // Use shared state management
    function isUpdateInProgress() {
        return typeof window.isPreviewUpdateInProgress === 'function'
            ? window.isPreviewUpdateInProgress()
            : false;
    }

    function setUpdateInProgress(value) {
        if (typeof window.setPreviewUpdateInProgress === 'function') {
            window.setPreviewUpdateInProgress(value);
        }
    }

    // Add debug logging function at the top of the file
    function debugLog(action, data) {
        if (window.newsletterDebug) {
            console.group('Newsletter Debug: ' + action);
            console.log('Timestamp:', new Date().toISOString());
            console.log('Data:', data);
            console.groupEnd();
        }
    }

    // Add debug toggle
    window.newsletterDebug = true;

    // Update block indices after sorting
    window.updateBlockIndices = function() {
        // Store editor contents before reindexing
        var editorContents = {};
        $('.block-item').each(function() {
            const $block = $(this);
            const blockIndex = $block.data('index');
            const editorId = 'wysiwyg-editor-' + blockIndex;
            const editor = tinymce.get(editorId);
            
            if (editor) {
                editorContents[editorId] = editor.getContent();
                debugLog('Storing editor content', {
                    editorId: editorId,
                    content: editorContents[editorId]
                });
                // Store in global state as well
                window.newsletterState.editorContents[editorId] = editorContents[editorId];
                editor.remove();
            }
        });

        // Update indices
        $('.block-item').each(function(index) {
            const $block = $(this);
            const oldIndex = $block.data('index');
            
            // Update block index
            $block.data('index', index);
            $block.attr('data-index', index);
            
            // Update editor ID and name
            const $editor = $block.find('.wysiwyg-editor-content');
            if ($editor.length) {
                const oldEditorId = 'wysiwyg-editor-' + oldIndex;
                const newEditorId = 'wysiwyg-editor-' + index;
                $editor.attr('id', newEditorId);
                $editor.attr('name', 'blocks[' + index + '][content]');
                
                // Get content from both local and global state
                const content = editorContents[oldEditorId] || window.newsletterState.editorContents[oldEditorId] || '';
                
                // Set the textarea value before initializing editor
                $editor.val(content);
                
                if (typeof initWysiwygEditor === 'function') {
                    debugLog('Reinitializing editor', {
                        oldId: oldEditorId,
                        newId: newEditorId,
                        content: content
                    });
                    
                    // Wait a brief moment for the DOM to settle
                    setTimeout(() => {
                        initWysiwygEditor(newEditorId, content);
                        // Verify content was set correctly
                        const newEditor = tinymce.get(newEditorId);
                        if (newEditor) {
                            const actualContent = newEditor.getContent();
                            debugLog('Editor content verification', {
                                editorId: newEditorId,
                                expectedContent: content,
                                actualContent: actualContent
                            });
                        }
                    }, 50);
                }
            }
            
            // Update other block fields
            $block.find('[name^="blocks[' + oldIndex + ']"]').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const newName = name.replace('blocks[' + oldIndex + ']', 'blocks[' + index + ']');
                $field.attr('name', newName);
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

    // Initialize sortable for posts
    window.initializeBlock = function(block) {
        try {
            var blockIndex = block.data('index');
            var blockType = block.find('.block-type').val();
            
            // Handle HTML block type first
            if (blockType === 'html') {
                block.find('.story-count-row select, .block-story-count')
                    .prop('disabled', true)
                    .closest('div')
                    .css('opacity', '0.7');
            }
            
            // Handle PDF Link type
            if (blockType === 'pdf_link') {
                block.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle, .block-story-count')
                    .each(function() {
                        $(this).prop('disabled', true);
                    });
                
                block.find('.category-select, .date-range-row, .story-count-row')
                    .css('opacity', '0.7');
                block.find('.category-select select, .date-range-row select, .story-count-row select, .block-story-count')
                    .css('opacity', '0.7');
                
                block.find('.template-select select')
                    .prop('disabled', false)
                    .css('opacity', '1');
                block.find('.template-select')
                    .css('opacity', '1');
                
                setupBlockEventHandlers(block);
                return;
            }
            
            // Initialize sortable functionality for non-PDF blocks
            initializeSortable(block);
            
            // Set initial state of story count dropdown based on manual override
            var isManual = block.find('input[name*="[manual_override]"]').prop('checked');
            var $storyCount = block.find('.block-story-count');
            $storyCount.prop('disabled', isManual || blockType === 'html');
            $storyCount.css('opacity', (isManual || blockType === 'html') ? '0.7' : '1');
            
            // Initial block type setup
            handleBlockTypeChange(block, blockType);

            // Set up all event handlers for this block
            setupBlockEventHandlers(block);
            
            // Initial category load if needed
            const initialCategory = block.find('.block-category').val();
            if (initialCategory && !block.data('posts-loaded')) {
                const dateRange = block.find('.block-date-range').val();
                const blockIndex = block.data('index');
                const storyCount = block.find('.block-story-count').val();
                
                // Mark as loaded before making the request to prevent duplicate loads
                block.data('posts-loaded', true);
                
                new Promise((resolve) => {
                    // First save the initial state
                    saveBlockState(block, isManual, () => {
                        // Then load posts
                        loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount)
                            .then(() => {
                                // After loading posts, handle story count if needed
                                if (!isManual && storyCount !== 'disable' && blockType !== 'html') {
                                    handleStoryCountChange(block, storyCount);
                                }
                                resolve();
                            })
                            .catch(function(error) {
                                block.data('posts-loaded', false);
                                resolve();
                            });
                    });
                });
            } else if (!isManual && $storyCount.val() !== 'disable' && blockType !== 'html') {
                handleStoryCountChange(block, $storyCount.val());
            }

            // Update visual state based on manual override
            updateBlockVisuals(block, isManual);

        } catch (error) {
            try {
                updateBlockVisuals(block, block.find('input[name*="[manual_override]"]').prop('checked'));
            } catch (visualError) {}
        }
    };

    // Handle block type changes (show/hide fields)
    function handleBlockTypeChange(block, blockType) {
        if (block.data('type-change-in-progress')) {
            return;
        }
        block.data('type-change-in-progress', true);
        
        // Store existing content before cleanup
        const blockIndex = block.data('index');
        const editorId = 'wysiwyg-editor-' + blockIndex;
        let existingContent = '';
        let oldType = block.find('.block-type').data('previous-type');
        
        // Store content from previous type
        if (oldType === 'wysiwyg' && tinymce.get(editorId)) {
            existingContent = tinymce.get(editorId).getContent();
            tinymce.execCommand('mceRemoveEditor', true, editorId);
        } else if (oldType === 'html') {
            existingContent = block.find('.html-block textarea').val();
        }

        // Hide all content sections
        block.find('.content-block, .html-block, .wysiwyg-block').hide();
        
        // Disable/enable appropriate fields based on type
        setupBlockFields(block, blockType);
        
        // Show and setup new content section
        if (blockType === 'content') {
            block.find('.content-block').show();
            setupContentBlock(block);
        } else if (blockType === 'html') {
            block.find('.html-block').show();
            setupHtmlBlock(block, existingContent);
        } else if (blockType === 'wysiwyg') {
            setupWysiwygBlock(block, existingContent);
        }
        
        // Store new type
        block.find('.block-type').data('previous-type', blockType);
        block.data('type-change-in-progress', false);

        // Update preview after type change
        if (typeof window.updatePreview === 'function') {
            window.updatePreview('block_type_change');
        }
    }

    // Helper function to set up fields based on block type
    function setupBlockFields(block, blockType) {
        const isContentType = blockType === 'content';
        const isPdfLinkType = blockType === 'pdf_link';
        
        // Disable category, date range, and manual override for non-content types
        block.find('.category-select select, .date-range-row select, .manual-override-toggle')
            .prop('disabled', !(isContentType || isPdfLinkType))
            .closest('div')
            .css('opacity', (isContentType || isPdfLinkType) ? '1' : '0.7');
            
        // Always disable story count for HTML blocks
        const $storyCount = block.find('.story-count-row select, .block-story-count');
        if (blockType === 'html') {
            $storyCount.prop('disabled', true)
                .closest('div')
                .css('opacity', '0.7');
        } else {
            $storyCount.prop('disabled', !(isContentType || isPdfLinkType))
                .closest('div')
                .css('opacity', (isContentType || isPdfLinkType) ? '1' : '0.7');
        }
    }

    // Helper function to set up HTML block
    function setupHtmlBlock(block, existingContent) {
        const $container = block.find('.html-block');
        $container.show();
        
        // Initialize textarea with content
        const $textarea = $container.find('textarea');
        if ($textarea.length) {
            $textarea.val(existingContent);
        }
    }

    // Helper function to set up WYSIWYG block
    function setupWysiwygBlock(block, existingContent) {
        const $container = block.find('.wysiwyg-block');
        $container.show();
        
        const blockIndex = block.data('index');
        const editorId = 'wysiwyg-editor-' + blockIndex;

        // Clean up existing editor if needed
        if (tinymce.get(editorId)) {
            tinymce.execCommand('mceRemoveEditor', true, editorId);
        }

        // Ensure we have a clean textarea
        $container.find('textarea').remove();
        $container.append(
            `<textarea id="${editorId}" 
                  name="blocks[${blockIndex}][wysiwyg]" 
                  class="wysiwyg-editor-content">${existingContent}</textarea>`
        );

        // Initialize editor after short delay
        setTimeout(() => {
            initWysiwygEditor(block);
        }, 100);
    }

    // Initialize a single block with all necessary handlers and setup
    window.initializeBlock = function(block) {
        try {
            var blockIndex = block.data('index');
            var blockType = block.find('.block-type').val();
            
            if (blockType === 'pdf_link') {
                block.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle, .block-story-count')
                    .each(function() {
                        $(this).prop('disabled', true);
                    });
                
                block.find('.category-select, .date-range-row, .story-count-row')
                    .css('opacity', '0.7');
                block.find('.category-select select, .date-range-row select, .story-count-row select, .block-story-count')
                    .css('opacity', '0.7');
                
                block.find('.template-select select')
                    .prop('disabled', false)
                    .css('opacity', '1');
                block.find('.template-select')
                    .css('opacity', '1');
                
                setupBlockEventHandlers(block);
                return;
            }
            
            initializeSortable(block);
            
            var isManual = block.find('input[name*="[manual_override]"]').prop('checked');
            var $storyCount = block.find('.block-story-count');
            $storyCount.prop('disabled', isManual);
            $storyCount.css('opacity', isManual ? '0.7' : '1');
            
            handleBlockTypeChange(block, blockType);
            setupBlockEventHandlers(block);
            
            const initialCategory = block.find('.block-category').val();
            if (initialCategory && !block.data('posts-loaded')) {
                const dateRange = block.find('.block-date-range').val();
                const blockIndex = block.data('index');
                const storyCount = block.find('.block-story-count').val();
                
                block.data('posts-loaded', true);
                
                new Promise((resolve) => {
                    saveBlockState(block, isManual, () => {
                        loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount)
                            .then(() => {
                                if (!isManual && storyCount !== 'disable') {
                                    handleStoryCountChange(block, storyCount);
                                }
                                resolve();
                            })
                            .catch(function(error) {
                                block.data('posts-loaded', false);
                                resolve();
                            });
                    });
                });
            } else if (!isManual && $storyCount.val() !== 'disable') {
                handleStoryCountChange(block, $storyCount.val());
            }

            updateBlockVisuals(block, isManual);

        } catch (error) {
            try {
                updateBlockVisuals(block, block.find('input[name*="[manual_override]"]').prop('checked'));
            } catch (visualError) {}
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
        
        // Reset all checkboxes first
        $checkboxInputs.each(function() {
            $(this).prop('checked', false).attr('checked', false);
            $(this).closest('li').find('.post-order').val('');
        });
        
        if (storyCountVal !== 'disable' && parseInt(storyCountVal) > 0) {
            const maxStories = parseInt(storyCountVal);
            
            // Get all list items and sort by date
            const $items = $postsList.find('li').sort(function(a, b) {
                var dateA = new Date($(a).data('post-date'));
                var dateB = new Date($(b).data('post-date'));
                return dateB - dateA; // Descending order
            });
            
            // Check and set order for the first N items
            $items.each(function(index) {
                if (index < maxStories) {
                    const $item = $(this);
                    const $checkbox = $item.find('input[type="checkbox"]');
                    $checkbox.prop('checked', true).attr('checked', 'checked');
                    $item.find('.post-order').val(index);
                }
            });
            
            // Reorder items in the DOM to match the sorted order
            const $container = $postsList;
            $items.each(function() {
                $container.append(this);
            });
        } else {
            // If "All" is selected or invalid count, check all stories and sort by date
            const $items = $postsList.find('li').sort(function(a, b) {
                var dateA = new Date($(a).data('post-date'));
                var dateB = new Date($(b).data('post-date'));
                return dateB - dateA;
            });
            
            $items.each(function(index) {
                const $item = $(this);
                const $checkbox = $item.find('input[type="checkbox"]');
                
                $checkbox.prop('checked', true).attr('checked', 'checked');
                $item.find('.post-order').val(index);
            });
            
            const $container = $postsList;
            $items.each(function() {
                $container.append(this);
            });
        }

        saveBlockState(block, false, function() {
            if (!skipPreview) {
                updatePreview('story_count_change');
            }
            setUpdateInProgress(false);
        });
    };

    // Event handlers for blocks
    function setupBlockEventHandlers(block) {
        let storedContent = {};
        
        block.find('.block-type').off('change').on('change', function(e) {
            e.stopPropagation();
            if (isUpdateInProgress()) return;
            
            var newBlockType = $(this).val();
            var $block = $(this).closest('.block-item');
            var blockIndex = $block.data('index');
            
            var oldEditorId = 'wysiwyg-editor-' + blockIndex;
            if (tinymce.get(oldEditorId)) {
                storedContent[blockIndex] = tinymce.get(oldEditorId).getContent();
                tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(oldEditorId);
                }
            }

            handleBlockTypeChange($block, newBlockType);
            
            if (newBlockType === 'wysiwyg' && storedContent[blockIndex]) {
                setTimeout(() => {
                    const newEditor = tinymce.get('wysiwyg-editor-' + blockIndex);
                    if (newEditor) {
                        newEditor.setContent(storedContent[blockIndex]);
                    }
                }, 100);
            }
            
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('block_type_change');
                setUpdateInProgress(false);
            }, 250);
        });

        block.find('.block-title-input').off('change keyup paste input').on('change keyup paste input', function() {
            if (isUpdateInProgress()) return;
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('title_change');
                setUpdateInProgress(false);
            }, 250);
        });

        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            if (typeof wp !== 'undefined' && wp.editor && editorId) {
                wp.editor.remove(editorId);
                
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                        setup: function(editor) {
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

                $('#' + editorId).on('change keyup paste input', function() {
                    if (isUpdateInProgress()) return;
                    
                    clearTimeout(this.textareaTimer);
                    this.textareaTimer = setTimeout(() => {
                        var content = $(this).val();
                        var previousContent = $(this).data('previous-content');
                        
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

        block.find('.block-posts').off('change', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]').on('change', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]', function() {
            if (isUpdateInProgress()) {
                return;
            }
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('post_selection_change');
                setUpdateInProgress(false);
            }, 250);
        });

        block.find('.block-story-count').off('change.storyCount change').on('change.storyCount', function(e) {
            handleStoryCountChange(block, $(this).val());
        });

        block.find('.block-category, .block-date-range').off('change').on('change', function() {
            if (isUpdateInProgress()) return;
            
            setUpdateInProgress(true);
            const $block = $(this).closest('.block-item');
            const manualOverride = $block.find('input[name*="[manual_override]"]').prop('checked');
            const categoryId = $block.find('select[name*="[category]"]').val();
            const dateRange = $block.find('select[name*="[date_range]"]').val();
            const blockIndex = $block.data('index');
            const storyCount = $block.find('select[name*="[story_count]"]').val();

            if (categoryId) {
                saveBlockState($block, manualOverride, function() {
                    loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                        .then(() => {
                            if (!manualOverride && storyCount !== 'disable') {
                                const storyItems = $block.find('.sortable-post-item');
                                const maxStories = parseInt(storyCount);
                                
                                const sortedItems = Array.from(storyItems).sort((a, b) => {
                                    const dateA = new Date($(a).data('post-date'));
                                    const dateB = new Date($(b).data('post-date'));
                                    return dateB - dateA;
                                });
                                
                                sortedItems.forEach((item, index) => {
                                    const $item = $(item);
                                    const $checkbox = $item.find('input[type="checkbox"]');
                                    const shouldBeChecked = index < maxStories;
                                    $checkbox.prop('checked', shouldBeChecked);
                                    if (shouldBeChecked) {
                                        $item.find('.post-order').val(index + 1);
                                    }
                                });
                                
                                const $container = storyItems.first().parent();
                                sortedItems.forEach(item => {
                                    $container.append(item);
                                });
                                
                                saveBlockState($block, false);
                            }
                            
                            setTimeout(() => {
                                updatePreview('category_date_change');
                                setUpdateInProgress(false);
                            }, 250);
                        });
                });
            } else {
                setUpdateInProgress(false);
            }
        });

        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            if (isUpdateInProgress()) return;
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('content_change');
                setUpdateInProgress(false);
            }, 250);
        });

        function initializeWysiwygEditor($block, editorId) {
            if (tinymce.get(editorId)) {
                tinymce.execCommand('mceRemoveEditor', true, editorId);
            }
            
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                        setup: function(editor) {
                            editor.on('init', function(e) {
                                const savedContent = window.newsletterState.editorContents[editorId] || 
                                                   $block.find('#' + editorId).val();
                                if (savedContent) {
                                    editor.setContent(savedContent);
                                }
                                
                                editor.on('change keyup paste input', function() {
                                    if (isUpdateInProgress()) return;
                                    editor.save();
                                    trackEditorChanges(editorId, editor.getContent());
                                });
                            });
                        }
                    }
                });
            }
        }

        block.find('.show-title-toggle').on('change', function() {
            if (window.newsletterState.isReady) {
                if (typeof debouncedAutoSave === 'function') {
                    debouncedAutoSave();
                }
                updatePreview('show_title_change');
            }
        });

        initializeManualOverrideHandlers(block);
    }

    window.handleManualOverrideToggle = function($block, isManual) {
        const $postsList = $block.find('.sortable-posts');
        const $storyCount = $block.find('.block-story-count');
        
        if (!isManual && $storyCount.val() !== 'disable') {
            handleStoryCountChange($block, $storyCount.val());
        }
        
        $postsList.css({
            'pointer-events': isManual ? 'auto' : 'none',
            'opacity': isManual ? '1' : '0.7'
        });
        
        $storyCount.prop('disabled', isManual);
        $postsList.find('input[type="checkbox"]').prop('disabled', !isManual);

        const sortableList = $block.find('ul.sortable-posts');
        if (sortableList.length && sortableList.hasClass('ui-sortable')) {
            if (isManual) {
                sortableList.sortable('enable');
            } else {
                sortableList.sortable('disable');
            }
        }

        saveBlockState($block, isManual, function() {
            if (window.newsletterState.isReady) {
                updatePreview('manual_override_change');
            }
        });
    };

    function initializeManualOverrideHandlers($block) {
        $block.find('.manual-override-toggle').off('change').on('change', function() {
            const isManual = $(this).prop('checked');
            handleManualOverrideToggle($block, isManual);
        });

        const initialManual = $block.find('.manual-override-toggle').prop('checked');
        handleManualOverrideToggle($block, initialManual);
    }

    window.addBlock = function() {
        var blockIndex = $('#blocks-container .block-item').length;
        var blockHtml = `
            <div class="block-item" data-index="${blockIndex}" data-block-index="${blockIndex}">
                <h3 class="block-header">
                    <div class="block-header-content">
                        <span class="dashicons dashicons-sort block-drag-handle"></span>
                        <span class="block-title">${newsletterData.blockLabel}</span>
                    </div>
                </h3>
                <div class="block-content">
                    <!-- Title Row -->
                    <div class="block-row">
                        <div class="block-field">
                            <label>${newsletterData.blockTitleLabel}</label>
                            <input type="text" name="blocks[${blockIndex}][title]" class="block-title-input" value="">
                        </div>
                        <div class="block-checkbox">
                            <input type="checkbox" name="blocks[${blockIndex}][show_title]" class="show-title-toggle toggle-switch" value="1" id="show-title-${blockIndex}">
                            <label for="show-title-${blockIndex}">Show Title in Preview</label>
                        </div>
                    </div>
                    
                    <!-- Block Type and Template Row -->
                    <div class="block-row">
                        <div class="block-field">
                            <label>Block Type:</label>
                            <select name="blocks[${blockIndex}][type]" class="block-type">
                                <option value="content">Content</option>
                                <option value="html">HTML</option>
                                <option value="wysiwyg">WYSIWYG Editor</option>
                                <option value="pdf_link">PDF Link</option>
                            </select>
                        </div>

                        <div class="block-field category-select">
                            <label>Category:</label>
                            <select name="blocks[${blockIndex}][category]" class="block-category">
                                <option value="">${newsletterData.selectCategoryOption}</option>
                                ${newsletterData.categories.map(category => 
                                    `<option value="${category.id}">${category.name}</option>`
                                ).join('')}
                            </select>
                        </div>

                        <div class="block-field template-select">
                            <label>Template:</label>
                            <select name="blocks[${blockIndex}][template_id]" class="block-template">
                                ${newsletterData.availableTemplates.map(template => 
                                    `<option value="${template.id}">${template.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="date-range-row block-row">
                        <div class="block-field">
                            <label>Date Range:</label>
                            <select name="blocks[${blockIndex}][date_range]" class="block-date-range">
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

                        <div class="block-field story-count-row">
                            <label>Story Count:</label>
                            <select name="blocks[${blockIndex}][story_count]" class="block-story-count">
                                <option value="disable">All Stories</option>
                                ${Array.from({length: 10}, (_, i) => `<option value="${i + 1}">${i + 1}</option>`).join('')}
                            </select>
                        </div>

                        <div class="block-checkbox">
                            <input type="checkbox" name="blocks[${blockIndex}][manual_override]" class="manual-override-toggle toggle-switch" value="1" id="manual-override-${blockIndex}">
                            <label for="manual-override-${blockIndex}">Manual Override Stories</label>
                        </div>
                    </div>

                    <div class="content-block">
                        <div class="block-posts">
                            <p>${newsletterData.selectCategoryPrompt}</p>
                        </div>
                    </div>

                    <div class="html-block" style="display:none;">
                        <div class="block-field">
                            <label>Custom HTML:</label>
                            <textarea name="blocks[${blockIndex}][html]" rows="5" class="block-html"></textarea>
                        </div>
                    </div>

                    <div class="wysiwyg-block" style="display:none;">
                        <div class="block-field">
                            <label>WYSIWYG Content:</label>
                            <textarea name="blocks[${blockIndex}][wysiwyg]" class="wysiwyg-editor-content" id="wysiwyg-editor-${blockIndex}"></textarea>
                        </div>
                    </div>

                    <button type="button" class="button button-large action-button remove-button block-remove-btn" data-block-index="${blockIndex}">
                        <span class="dashicons dashicons-trash button-icon"></span>
                        <strong>REMOVE BLOCK</strong>
                    </button>
                </div>
            </div>
        `;

        $('#blocks-container').append(blockHtml);
        var newBlock = $('#blocks-container .block-item').last();
        
        var initialBlockType = newBlock.find('.block-type').val();
        if (initialBlockType === 'pdf_link') {
            newBlock.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle, .block-story-count')
                .prop('disabled', true);
            
            newBlock.find('.category-select, .date-range-row, .story-count-row')
                .css('opacity', '0.7');
            newBlock.find('.category-select select, .date-range-row select, .story-count-row select, .block-story-count')
                .css('opacity', '0.7');
            
            newBlock.find('.template-select select')
                .prop('disabled', false)
                .css('opacity', '1');
            newBlock.find('.template-select')
                .css('opacity', '1');
        }

        newBlock.find('.block-posts').on('mouseenter', function() {
            var $block = $(this).closest('.block-item');
            var isManual = $block.find('.manual-override-toggle').prop('checked');
            handleManualOverrideToggle($block, isManual);
        });

        newBlock.find('.manual-override-toggle').on('change', function() {
            var $block = $(this).closest('.block-item');
            var isManual = $(this).prop('checked');
            handleManualOverrideToggle($block, isManual);
            
            if (window.newsletterState.isReady) {
                updatePreview('manual_override_change');
            }
        });

        newBlock.find('.block-story-count').on('change', function() {
            var $block = $(this).closest('.block-item');
            var storyCount = $(this).val();
            var isManual = $block.find('.manual-override-toggle').prop('checked');
            
            if (!isManual) {
                handleStoryCountChange($block, storyCount);
                
                if (window.newsletterState.isReady) {
                    updatePreview('story_count_change');
                }
            }
        });

        newBlock.find('.sortable-posts').sortable({
            handle: '.story-drag-handle',
            update: function(event, ui) {
                updatePostOrder($(this).closest('.block-item'));
                
                if (window.newsletterState.isReady) {
                    updatePreview('post_order_change');
                }
            }
        });

        function handleManualOverrideToggle($block, isManual) {
            const $postsList = $block.find('.sortable-posts');
            const $storyCount = $block.find('.block-story-count');
            
            if (!isManual && $storyCount.val() !== 'disable') {
                handleStoryCountChange($block, $storyCount.val());
            }
            
            $postsList.css({
                'pointer-events': isManual ? 'auto' : 'none',
                'opacity': isManual ? '1' : '0.7'
            });
            
            $storyCount.prop('disabled', isManual);
            $postsList.find('input[type="checkbox"]').prop('disabled', !isManual);
        }

        function handleStoryCountChange($block, storyCountVal) {
            const $postsList = $block.find('.sortable-posts');
            const $checkboxInputs = $postsList.find('input[type="checkbox"]');
            
            $checkboxInputs.prop('checked', false);
            
            if (storyCountVal !== 'disable' && parseInt(storyCountVal) > 0) {
                const maxStories = parseInt(storyCountVal);
                const $items = $postsList.find('li').sort((a, b) => {
                    return new Date($(b).data('post-date')) - new Date($(a).data('post-date'));
                });
                
                $items.each(function(index) {
                    if (index < maxStories) {
                        $(this).find('input[type="checkbox"]').prop('checked', true);
                        $(this).find('.post-order').val(index);
                    }
                });
            } else {
                const $items = $postsList.find('li').sort((a, b) => {
                    return new Date($(b).data('post-date')) - new Date($(a).data('post-date'));
                });
                
                $items.each(function(index) {
                    $(this).find('input[type="checkbox"]').prop('checked', true);
                    $(this).find('.post-order').val(index);
                });
            }
        }

        function updatePostOrder($block) {
            var $items = $block.find('.sortable-posts li');
            $items.each(function(index) {
                var $item = $(this);
                if ($item.find('input[type="checkbox"]').prop('checked')) {
                    $item.find('.post-order').val(index);
                }
            });
        }

        initializeBlock(newBlock);

        $("#blocks-container").accordion('destroy').accordion({
            header: ".block-header",
            collapsible: true,
            active: blockIndex,
            heightStyle: "content",
            icons: false
        });

        updatePreview('new_block_added');
    };

    function initializeBlocks() {
        window.newsletterState = {
            blocksLoaded: 0,
            totalBlocks: $('.block-item').length,
            postsData: {},
            isReady: false,
            isUpdateInProgress: false,
            editorContents: {},
            pendingUpdates: new Set()
        };

        var blocks = $('.block-item');
        var totalBlocks = blocks.length;
        var loadedBlocks = 0;
        
        if (totalBlocks === 0) {
            window.newsletterState.isReady = true;
            return;
        }
        
        // First pass: handle PDF Link blocks
        blocks.each(function() {
            var block = $(this);
            var blockType = block.find('.block-type').val();
            
            if (blockType === 'pdf_link') {
                block.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle, .block-story-count')
                    .prop('disabled', true)
                    .css('opacity', '0.7');
                
                block.find('.category-select, .date-range-row, .story-count-row')
                    .css('opacity', '0.7');
                
                block.find('.template-select select')
                    .prop('disabled', false)
                    .css('opacity', '1');
                block.find('.template-select')
                    .css('opacity', '1');
                
                loadedBlocks++;
                window.newsletterState.blocksLoaded = loadedBlocks;
            }
        });
        
        // Second pass: initialize remaining blocks
        blocks.each(function(index) {
            var block = $(this);
            var blockType = block.find('.block-type').val();
            
            if (blockType === 'pdf_link') {
                return;
            }
            
            block.attr('data-block-index', index);
            setupBlockEventHandlers(block);
            
            var categorySelect = block.find('.block-category');
            var categoryId = categorySelect.val();
            var currentIndex = index;
            var dateRange = block.find('.block-date-range').val() || '7';
            var storyCount = block.find('.block-story-count').val() || 'disable';
            var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked') || false;
            
            if (!categoryId) {
                loadedBlocks++;
                window.newsletterState.blocksLoaded = loadedBlocks;
                
                if (loadedBlocks === totalBlocks) {
                    window.newsletterState.isReady = true;
                    updatePreview('initialization_complete');
                }
                return;
            }
            
            loadBlockPosts(block, categoryId, currentIndex, dateRange, storyCount, true)
                .then(function() {
                    loadedBlocks++;
                    window.newsletterState.blocksLoaded = loadedBlocks;
                    
                    if (loadedBlocks === totalBlocks) {
                        window.newsletterState.isReady = true;
                        updatePreview('initialization_complete');
                    }
                })
                .catch(function(error) {
                    loadedBlocks++;
                    window.newsletterState.blocksLoaded = loadedBlocks;
                    
                    if (loadedBlocks === totalBlocks) {
                        window.newsletterState.isReady = true;
                        updatePreview('initialization_complete');
                    }
                });
        });
    }

    $(document).ready(function() {
        initializeBlocks();
    });

    window.collectPostData = function($block) {
        var posts = {};
        var $items = $block.find('.block-posts li');
        
        $items.each(function(index) {
            var $post = $(this);
            var postId = $post.data('post-id');
            var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            var currentOrder = $post.find('.post-order').val();
            
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
        
        $('#blocks-container .block-item').each(function(index) {
            var $currentBlock = $(this);
            var blockType = $currentBlock.find('.block-type').val();
            
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
        
        $postsList.css({
            'pointer-events': isManual ? 'auto' : 'none',
            'opacity': isManual ? '1' : '0.7'
        });
        
        $checkboxes.prop('disabled', !isManual);
        
        $postsList.find('.story-drag-handle').css('cursor', isManual ? 'move' : 'default');
        
        var $storyCount = $block.find('.block-story-count');
        $storyCount.prop('disabled', isManual);
        $storyCount.css('opacity', isManual ? '0.7' : '1');
    };

    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount, skipPreview = false) {
        if (!block || !categoryId) {
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

        var data = {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            date_range: dateRange,
            story_count: storyCount,
            newsletter_slug: newsletterData.newsletterSlug,
            saved_selections: JSON.stringify(savedSelections),
            manual_override: manualOverride ? 'true' : 'false'
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
                        
                        if (manualOverride) {
                            $temp.find('input[type="checkbox"]').each(function() {
                                var $checkbox = $(this);
                                var postId = $checkbox.closest('li').data('post-id');
                                if (currentSelections[postId] && currentSelections[postId].checked === '1') {
                                    $checkbox.prop('checked', true);
                                    $checkbox.closest('li').find('.post-order').val(currentSelections[postId].order || '0');
                                } else {
                                    $checkbox.prop('checked', false);
                                }
                            });
                        }
                        
                        $postsContainer.append($temp.children());
                        initializeSortable(block);
                        
                        return saveBlockState(block, manualOverride, function() {
                            if (!manualOverride && storyCount !== 'disable') {
                                handleStoryCountChange(block, storyCount, true);
                            }
                            if (!skipPreview && !isUpdateInProgress()) {
                                setUpdateInProgress(true);
                                setTimeout(() => {
                                    updatePreview('posts_loaded');
                                    setUpdateInProgress(false);
                                }, 250);
                            }
                        });
                    } catch (error) {
                        $postsContainer.html(response.data);
                        return Promise.reject(error);
                    }
                }
            }
        });
    };

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
                            window.debouncedAutoSave();
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

    function verifyWysiwygContent(block) {
        const blockIndex = block.data('index');
        const editorId = 'wysiwyg-editor-' + blockIndex;
        
        if (tinymce.get(editorId)) {
            const editor = tinymce.get(editorId);
            const content = editor.getContent();
            const textarea = editor.getElement();
            
            if (content !== textarea.value) {
                textarea.value = content;
            }
            
            return content;
        }
        
        return '';
    }

    function trackEditorChanges(editorId, content) {
        window.newsletterState.editorContents[editorId] = content;
        window.newsletterState.pendingUpdates.add(editorId);
    }

    function clearPendingUpdates(editorId) {
        window.newsletterState.pendingUpdates.delete(editorId);
    }

    // Initialize sortable for blocks container
    $('#blocks-container').sortable({
        handle: '.block-drag-handle',
        items: '> .block-item',
        placeholder: 'block-placeholder',
        start: function(event, ui) {
            debugLog('Drag Start', {
                blockIndex: ui.item.data('index'),
                editors: ui.item.find('.wysiwyg-editor-content').map(function() {
                    var editorId = $(this).attr('id');
                    var editor = tinymce.get(editorId);
                    return {
                        id: editorId,
                        content: editor ? editor.getContent() : null,
                        textareaContent: $(this).val()
                    };
                }).get()
            });
        },
        sort: _.throttle(function(event, ui) {
            debugLog('During Sort', {
                blockIndex: ui.item.data('index'),
                placeholder: {
                    index: ui.placeholder.index()
                }
            });
        }, 250), // Throttle to max 4 times per second
        stop: function(event, ui) {
            debugLog('Drop Complete', {
                newIndex: ui.item.index(),
                editors: ui.item.find('.wysiwyg-editor-content').map(function() {
                    var editorId = $(this).attr('id');
                    var editor = tinymce.get(editorId);
                    return {
                        id: editorId,
                        content: editor ? editor.getContent() : null,
                        textareaContent: $(this).val()
                    };
                }).get()
            });
        },
        update: function(event, ui) {
            // Store the content before updating indices
            const $block = ui.item;
            const oldIndex = $block.data('index');
            const oldEditorId = 'wysiwyg-editor-' + oldIndex;
            const editor = tinymce.get(oldEditorId);
            
            if (editor) {
                window.newsletterState.editorContents[oldEditorId] = editor.getContent();
            }
            
            // Update indices after a short delay to ensure DOM is settled
            setTimeout(function() {
                window.updateBlockIndices();
            }, 100);
        }
    });

    // Handle load more functionality
    $(document).on('click', '.load-more-posts', function() {
        const $button = $(this);
        const $block = $button.closest('.block-item');
        const nextPage = parseInt($button.data('page'));
        
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: $button.data('category'),
                block_index: $button.data('block-index'),
                date_range: $button.data('date-range'),
                page: nextPage
            },
            success: function(response) {
                if (response.success) {
                    const $newContent = $(response.data);
                    $button.before($newContent.find('.sortable-posts li'));
                    
                    // Replace the load more button and posts count
                    $button.replaceWith($newContent.find('.load-more-posts'));
                    $block.find('.posts-count').replaceWith($newContent.find('.posts-count'));
                    
                    // Reinitialize sortable if needed
                    if (typeof initializeSortable === 'function') {
                        initializeSortable($block);
                    }
                }
            }
        });
    });

})(jQuery);