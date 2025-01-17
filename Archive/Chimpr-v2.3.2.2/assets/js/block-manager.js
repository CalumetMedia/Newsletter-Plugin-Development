(function($) {
    // Global initialization and update flags
    window.blockManagerInitialized = false;
    let globalUpdateInProgress = false;
    
    // Initialize sortable for posts
    window.initializeSortable = function(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.story-drag-handle',
                update: function(event, ui) {
                    if (globalUpdateInProgress) return;
                    
                    var $block = ui.item.closest('.block-item');
                    
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    
                    globalUpdateInProgress = true;
                    setTimeout(() => {
                        updatePreview('sortable_update');
                        globalUpdateInProgress = false;
                    }, 250);
                }
            });
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

    // Initialize events for a block
    window.initializeBlockEvents = function(block) {
        // Initial setup
        initializeSortable(block);
        var blockType = block.find('.block-type').val();
        handleBlockTypeChange(block, blockType);
            
        // Track initial state
        const initialCategory = block.find('.block-category').val();
        const initialStoryCount = block.find('.block-story-count').val();

        // Only do initial load if needed and if not already loaded
        if (initialCategory && !block.data('posts-loaded')) {
            const dateRange = block.find('.block-date-range').val();
            const blockIndex = block.data('index');
            const storyCount = block.find('.block-story-count').val();

            // Mark block as loaded
            block.data('posts-loaded', true);

            // Create a promise wrapper
            return new Promise((resolve) => {
                loadBlockPosts(block, initialCategory, blockIndex, dateRange, storyCount)
                    .then(() => {
                        if (!globalUpdateInProgress) {
                            globalUpdateInProgress = true;
                            setTimeout(() => {
                                updatePreview('initial_block_load');
                                globalUpdateInProgress = false;
                                resolve();
                            }, 250);
                        } else {
                            resolve();
                        }
                    });
            });
        }
        return Promise.resolve();
    };

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount) {
        // Get current selections for this block
        var savedSelections = {};
        
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

        var data = {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            date_range: dateRange,
            story_count: storyCount,
            newsletter_slug: newsletterData.newsletterSlug,
            saved_selections: JSON.stringify(savedSelections)
        };

        console.log('Loading posts with saved selections:', savedSelections);

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
                if (response.success) {
                    block.find('.block-posts').html(response.data);
                    initializeSortable(block);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                block.find('.block-posts').removeClass('loading');
            }
        });
    };

    // Event handlers for blocks
    function setupBlockEventHandlers(block) {
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
                                <option value="content">${newsletterData.contentLabel}</option>
                                <option value="html">${newsletterData.advertisingLabel}</option>
                                <option value="wysiwyg">WYSIWYG Editor</option>
                            </select>
                        </div>
                        
                        <div class="template-select" style="width: 200px;">
                            <label>${newsletterData.templateLabel}</label>
                            <select name="blocks[${blockIndex}][template_id]" class="block-template" style="width: 100%; height: 36px;">
                                ${newsletterData.availableTemplates.map(template => 
                                    `<option value="${template.id}">${template.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="content-block">
                        <div class="category-select" style="width: 200px; margin-bottom: 10px;">
                            <label>${newsletterData.selectCategoryLabel}</label>
                            <select name="blocks[${blockIndex}][category]" class="block-category" style="width: 100%; height: 36px;">
                                <option value="">${newsletterData.selectCategoryOption}</option>
                                ${newsletterData.categories.map(category => 
                                    `<option value="${category.id}">${category.name}</option>`
                                ).join('')}
                            </select>
                        </div>

                        <div class="date-range-row" style="margin-bottom: 10px;">
                            <label>Date Range (days):</label>
                            <input type="number" name="blocks[${blockIndex}][date_range]" class="block-date-range" value="7" min="1" style="width: 80px;">
                        </div>

                        <div class="story-count-row" style="margin-bottom: 10px;">
                            <label>Story Count:</label>
                            <select name="blocks[${blockIndex}][story_count]" class="block-story-count" style="width: 100px;">
                                <option value="1">1 Story</option>
                                <option value="4">4 Stories</option>
                                <option value="all">All Stories</option>
                            </select>
                        </div>

                        <div class="block-posts">
                            <p>${newsletterData.selectCategoryPrompt}</p>
                        </div>
                    </div>

                    <div class="html-block" style="display:none;">
                        <label>${newsletterData.advertisingHtmlLabel}</label>
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
        initializeBlockEvents(newBlock);

        // Reinitialize accordion for all blocks
        $("#blocks-container").accordion('destroy').accordion({
            header: ".block-header",
            collapsible: true,
            active: blockIndex,
            heightStyle: "content"
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

        // Track initialization of blocks
        var totalBlocks = $('#blocks-container .block-item').length;
        var initializedBlocks = 0;
        var loadPromises = [];

        // Initialize existing blocks
        $('#blocks-container .block-item').each(function() {
            var $block = $(this);
            initializeBlockEvents($block);
        });

        // Remove block triggers a preview update
        $(document).on('click', '.remove-block', function() {
            var block = $(this).closest('.block-item');
            block.remove();
            updatePreview('block_removed');
        });
    });
})(jQuery);