(function($) {
    // Global initialization and update flags
    window.blockManagerInitialized = false;
    let globalUpdateInProgress = false;
    
    // Update block indices after sorting
    window.updateBlockIndices = function() {
        console.log('Updating block indices');
        $('#blocks-container .block-item').each(function(index) {
            $(this).data('index', index);
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/blocks\[\d+\]/, 'blocks[' + index + ']'));
                }
            });
        });
        
        // Trigger preview update after reordering
        if (!globalUpdateInProgress) {
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('block_reorder');
                globalUpdateInProgress = false;
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
                cancel: !manualOverride ? "*" : "", // Prevent any interaction when disabled
                update: function(event, ui) {
                    if (!manualOverride) {
                        // If manual override is disabled, prevent sorting and revert
                        sortableList.sortable('cancel');
                        return false;
                    }
                    
                    console.log('Sortable update triggered');
                    
                    // Update order values after sorting
                    sortableList.find('> li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    
                    // Trigger preview update
                    if (!globalUpdateInProgress) {
                        globalUpdateInProgress = true;
                        setTimeout(() => {
                            updatePreview('sortable_update');
                            globalUpdateInProgress = false;
                        }, 250);
                    }
                }
            }).disableSelection();

            // Set initial visual state
            sortableList.css({
                'pointer-events': manualOverride ? 'auto' : 'none',
                'opacity': manualOverride ? '1' : '0.7'
            });
            
            // Set initial checkbox state
            sortableList.find('input[type="checkbox"]').prop('disabled', !manualOverride);
            
            // Set initial cursor state for drag handles
            sortableList.find('.story-drag-handle').css('cursor', manualOverride ? 'move' : 'default');
            
            // If not manual override, ensure items can't be dragged
            if (!manualOverride) {
                sortableList.sortable('disable');
            }
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
        // Initialize sortable functionality
        initializeSortable(block);
        
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
                                if (globalUpdateInProgress) return;
                                
                                editor.save();
                                globalUpdateInProgress = true;
                                setTimeout(() => {
                                    updatePreview('wysiwyg_content_change');
                                    globalUpdateInProgress = false;
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
                    if (globalUpdateInProgress) return;
                    
                    globalUpdateInProgress = true;
                    setTimeout(() => {
                        updatePreview('wysiwyg_content_change');
                        globalUpdateInProgress = false;
                    }, 250);
                });
            }
        });

        // Add checkbox change handler for posts
        block.find('.block-posts').off('change', 'input[type="checkbox"][name*="[posts]"][name*="[selected]"]')
            .on('change', 'input[type="checkbox"][name*="[posts]"][name*="[selected]"]', function() {
                console.log('Checkbox changed!');
                if (globalUpdateInProgress) {
                    console.log('Update in progress, skipping...');
                    return;
                }
                
                console.log('Triggering preview update for checkbox change');
                globalUpdateInProgress = true;
                setTimeout(() => {
                    updatePreview('post_selection_change');
                    globalUpdateInProgress = false;
                }, 250);
            });

        // Add manual override change handler
        block.find('input[name*="[manual_override]"]').off('change').on('change', function() {
            var isManual = $(this).prop('checked');
            var $postsList = block.find('.sortable-posts');
            
            // Update sortable state
            $postsList.sortable('option', 'disabled', !isManual);
            
            // Update visual state
            $postsList.css({
                'pointer-events': isManual ? 'auto' : 'none',
                'opacity': isManual ? '1' : '0.7'
            });
            
            // Update checkboxes
            $postsList.find('input[type="checkbox"]').prop('disabled', !isManual);
            
            // Update drag handles
            $postsList.find('.story-drag-handle').css('cursor', isManual ? 'move' : 'default');
            
            // If switching to automatic mode, trigger a reload of posts
            if (!isManual) {
                const dateRange = block.find('.block-date-range').val();
                const categoryId = block.find('.block-category').val();
                const blockIndex = block.data('index');
                const storyCount = block.find('.block-story-count').val();
                
                if (categoryId) {
                    loadBlockPosts(block, categoryId, blockIndex, dateRange, storyCount);
                }
            }
        });

        // Story count change handler
        block.find('.block-story-count').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
            const $block = $(this).closest('.block-item');
            const categoryId = $block.find('.block-category').val();
            const dateRange = $block.find('.block-date-range').val();
            const blockIndex = $block.data('index');
            const storyCount = $(this).val();

            if (categoryId) {
                loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                    .then(() => {
                        setTimeout(() => {
                            updatePreview('story_count_change');
                            globalUpdateInProgress = false;
                        }, 250);
                    });
            }
        });

        // Category and date range change handlers
        block.find('.block-category, .block-date-range').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
            const $block = $(this).closest('.block-item');
            const categoryId = $block.find('.block-category').val();
            const dateRange = $block.find('.block-date-range').val();
            const blockIndex = $block.data('index');
            const storyCount = $block.find('.block-story-count').val();

            if (categoryId) {
                loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                    .then(() => {
                        setTimeout(() => {
                            updatePreview('category_date_change');
                            globalUpdateInProgress = false;
                        }, 250);
                    });
            }
        });

        // Block type change handler
        block.find('.block-type').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('block_type_change');
                globalUpdateInProgress = false;
            }, 250);
        });

        // HTML block content change handler
        block.find('.html-block textarea').off('input').on('input', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('html_content_change');
                globalUpdateInProgress = false;
            }, 250);
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
            block.data('posts-loaded', true);
            loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount);
        }
    };

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount, manualOverride) {
        // Get current selections for this block
        var savedSelections = {};
        
        // Only get saved selections if manual override is enabled
        if (manualOverride) {
            // First get any checked checkboxes
            block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]:checked').each(function() {
                var $checkbox = $(this);
                var postId = $checkbox.closest('li').data('post-id');
                var $orderInput = $checkbox.closest('li').find('.post-order');
                savedSelections[postId] = {
                    selected: true,
                    order: $orderInput.length ? $orderInput.val() : '0'
                };
            });
        }

        var data = {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            date_range: dateRange,
            story_count: storyCount,
            newsletter_slug: newsletterData.newsletterSlug,
            saved_selections: JSON.stringify(savedSelections),
            manual_override: manualOverride
        };

        console.log('Loading posts with params:', data);

        return $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: data,
            beforeSend: function() {
                console.log('Starting AJAX request to load posts...');
                block.find('.block-posts').addClass('loading');
            },
            success: function(response) {
                console.log('AJAX response received:', response);
                block.find('.block-posts').removeClass('loading');
                
                if (response.success && response.data) {
                    console.log('Response data length:', response.data.length);
                    
                    // Get the target element
                    var $postsContainer = block.find('.block-posts');
                    console.log('Posts container found:', $postsContainer.length > 0);
                    
                    try {
                        // Clear existing content
                        $postsContainer.empty();
                        
                        // Create a temporary div to parse the HTML
                        var $temp = $('<div>').html(response.data);
                        console.log('Parsed HTML elements:', $temp.children().length);
                        
                        // Append the new content
                        $postsContainer.append($temp.children());
                        console.log('New content appended');
                        
                        // Initialize sortable and event handlers
                        console.log('Initializing sortable and event handlers...');
                        initializeSortable(block);
                        
                        // Trigger preview update
                        if (!globalUpdateInProgress) {
                            console.log('Triggering preview update...');
                            globalUpdateInProgress = true;
                            setTimeout(() => {
                                updatePreview('date_range_change');
                                globalUpdateInProgress = false;
                            }, 250);
                        }
                    } catch (error) {
                        console.error('Error updating content:', error);
                        // Fallback direct HTML update
                        $postsContainer.html(response.data);
                    }
                } else {
                    console.error('AJAX response invalid:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                block.find('.block-posts').removeClass('loading');
            }
        });
    };

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
                                if (globalUpdateInProgress) return;
                                
                                editor.save();
                                globalUpdateInProgress = true;
                                setTimeout(() => {
                                    updatePreview('wysiwyg_content_change');
                                    globalUpdateInProgress = false;
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
                    if (globalUpdateInProgress) return;
                    
                    globalUpdateInProgress = true;
                    setTimeout(() => {
                        updatePreview('wysiwyg_content_change');
                        globalUpdateInProgress = false;
                    }, 250);
                });
            }
        });

        // Add checkbox change handler for posts
        block.find('.block-posts').off('change', 'input[type="checkbox"][name*="[posts]"][name*="[selected]"]').on('change', 'input[type="checkbox"][name*="[posts]"][name*="[selected]"]', function() {
            console.log('Checkbox changed!');
            if (globalUpdateInProgress) {
                console.log('Update in progress, skipping...');
                return;
            }
            
            console.log('Triggering preview update for checkbox change');
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('post_selection_change');
                globalUpdateInProgress = false;
            }, 250);
        });

        // Story count change handler
        block.find('.block-story-count').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
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
                            globalUpdateInProgress = false;
                        }, 250);
                    });
            }
        });

        // Category and date range change handlers
        block.find('.block-category, .block-date-range').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
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
                            globalUpdateInProgress = false;
                        }, 250);
                    });
            }
        });

        // WYSIWYG and HTML content change handlers
        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            if (globalUpdateInProgress) return;
            
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('content_change');
                globalUpdateInProgress = false;
            }, 250);
        });

        // Block type change handler
        block.find('.block-type').off('change').on('change', function() {
            if (globalUpdateInProgress) return;
            
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            
            globalUpdateInProgress = true;
            setTimeout(() => {
                updatePreview('block_type_change');
                globalUpdateInProgress = false;
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
                var $block = $(this).closest('.block-item');
                var isManual = $(this).prop('checked');
                var blockIndex = $block.data('index');
                
                // Always reload posts when toggling manual override
                const dateRange = $block.find('.block-date-range').val();
                const categoryId = $block.find('.block-category').val();
                const storyCount = $block.find('.block-story-count').val();
                
                if (categoryId) {
                    // Pass the manual override state to loadBlockPosts
                    loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount, isManual)
                        .then(() => {
                            // Reinitialize sortable after posts are loaded
                            initializeSortable($block);
                        });
                }
            });
    });
})(jQuery);