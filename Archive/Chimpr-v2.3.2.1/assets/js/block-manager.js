(function($) {
    // Initialize sortable for posts
    window.initializeSortable = function(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.story-drag-handle',
                update: function(event, ui) {
                    var $block = ui.item.closest('.block-item');
                    var $storyCount = $block.find('.block-story-count');
                    
                    // Switch to manual mode if user manually sorts
                    if ($storyCount.val() !== 'disable') {
                        $storyCount.val('disable');
                    }
                    
                    // Update order values for all posts in this block
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
                }
            });
        }
    };

    // Handle post selection changes
    window.handlePostSelection = function(block) {
        var blockIndex = block.data('index');
        var $storyCount = block.find('.block-story-count');
        var storyCount = $storyCount.val();
        
        // Get all checkboxes in this block
        var $checkboxes = block.find('.post-checkbox');
        
        // If story count is a number, check that many posts
        if (storyCount !== 'all' && storyCount !== 'disable') {
            var count = parseInt(storyCount);
            $checkboxes.each(function(index) {
                $(this).prop('checked', index < count);
            });
        }
        
        // Update order values
        var orderIndex = 0;
        $checkboxes.filter(':checked').each(function() {
            $(this).closest('li').find('.post-order').val(orderIndex++);
        });
        
        updatePreview();
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
        initializeSortable(block);

        // When a post is selected/unselected
        block.on('change', '.post-checkbox', function() {
            var $block = $(this).closest('.block-item');
            var $storyCount = $block.find('.block-story-count');
            
            // Switch to manual mode if user manually changes checkbox
            if ($storyCount.val() !== 'disable') {
                $storyCount.val('disable');
            }
            
            handlePostSelection($block);
        });

        // Story count change handler
        block.find('.block-story-count').off('change').on('change', function() {
            var $block = $(this).closest('.block-item');
            handlePostSelection($block);
        });

        // When WYSIWYG or HTML content changes, update preview
        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            updatePreview();
        });

        // Category change triggers AJAX load and preview update
        block.find('.block-category').off('change').on('change', function() {
            var $block = $(this).closest('.block-item');
            var categoryId = $(this).val();
            var dateRange = $block.find('.block-date-range').val();
            var blockIndex = $block.data('index');
            var storyCount = $block.find('.block-story-count').val();
            
            console.log('Category changed:', {
                categoryId: categoryId,
                dateRange: dateRange,
                blockIndex: blockIndex,
                storyCount: storyCount,
                $storyCount: $block.find('.block-story-count'),
                $storyCountVal: $block.find('.block-story-count').val()
            });
            
            if (categoryId) {
                var data = {
                    action: 'load_block_posts',
                    security: newsletterData.nonceLoadPosts,
                    category_id: categoryId,
                    block_index: blockIndex,
                    date_range: dateRange,
                    story_count: storyCount,
                    newsletter_slug: newsletterData.newsletterSlug
                };
                
                console.log('Sending request with data:', data);
                
                $.ajax({
                    url: newsletterData.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: data,
                    beforeSend: function() {
                        $block.find('.block-posts').addClass('loading');
                    },
                    success: function(response) {
                        $block.find('.block-posts').removeClass('loading');
                        if (response.success) {
                            $block.find('.block-posts').html(response.data);
                            initializeSortable($block);
                            updatePreview();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        $block.find('.block-posts').removeClass('loading');
                    }
                });
            } else {
                $block.find('.block-posts').html('<p>Please select a category to display posts.</p>');
                updatePreview();
            }
        });

        // Date range change triggers AJAX load if category selected
        block.find('.block-date-range').off('change').on('change', function() {
            var $block = $(this).closest('.block-item');
            var categoryId = $block.find('.block-category').val();
            var dateRange = $(this).val();
            var blockIndex = $block.data('index');
            var storyCount = $block.find('.block-story-count').val();
            
            if (categoryId) {
                loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount);
            }
        });

        // Block type change
        block.find('.block-type').off('change').on('change', function() {
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            updatePreview();
        });

        // Template change
        block.find('.block-template').off('change').on('change', function() {
            console.log('Template changed:', $(this).val());
            updatePreview();
        });

        // Initial setup
        var blockType = block.find('.block-type').val();
        handleBlockTypeChange(block, blockType);

        // Initial preview update
        updatePreview();
    };

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount) {
        var data = {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            date_range: dateRange,
            story_count: storyCount,
            newsletter_slug: newsletterData.newsletterSlug
        };

        console.log('Sending request:', data);

        $.ajax({
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
                    // Only update the posts list, not the entire block
                    var $postsContainer = block.find('.block-posts');
                    $postsContainer.html(response.data);
                    
                    // Re-initialize sortable just for this block's posts
                    var $sortableList = $postsContainer.find('.sortable-posts');
                    if ($sortableList.length) {
                        $sortableList.sortable({
                            handle: '.story-drag-handle',
                            update: function(event, ui) {
                                var $block = ui.item.closest('.block-item');
                                $sortableList.find('li').each(function(index) {
                                    $(this).find('.post-order').val(index);
                                });
                                updatePreview();
                            }
                        });
                    }
                    
                    // Update checkboxes based on story count
                    var storyCountVal = block.find('.block-story-count').val();
                    var checkboxes = $postsContainer.find('input[type="checkbox"]');
                    
                    checkboxes.prop('checked', false);
                    if (storyCountVal === 'all') {
                        checkboxes.prop('checked', true);
                    } else {
                        checkboxes.slice(0, parseInt(storyCountVal)).prop('checked', true);
                    }
                    
                    updatePreview();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                block.find('.block-posts').removeClass('loading');
            }
        });
    };

    // Add a new block
    window.addBlock = function() {
        var blockIndex = $('#blocks-container .block-item').length;
        var blockHtml = `
<div class="block-item" data-index="${blockIndex}" data-original-index="${blockIndex}">
    <h3 class="block-header">
        <div style="display: flex; align-items: center; width: 100%;">
            <span class="dashicons dashicons-sort block-drag-handle"></span>
            <span class="block-title" style="flex: 1; font-size: 14px; margin: 0 10px;">Block</span>
            <span class="dashicons dashicons-arrow-down-alt2 block-accordion-toggle"></span>
        </div>
    </h3>
    <div class="block-content">
        <div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">
            <div style="width: 25%;">
                <label>Block Title:</label>
                <input type="text" name="blocks[${blockIndex}][title]" class="block-title-input" style="width: 100%; height: 36px;" />
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
                <label>Block Type:</label>
                <select name="blocks[${blockIndex}][type]" class="block-type" style="width:100%; height:36px; padding:0 6px;">
                    <option value="content">Content</option>
                    <option value="html">HTML</option>
                    <option value="wysiwyg">WYSIWYG Editor</option>
                </select>
            </div>
            <div style="width: 200px;" class="category-select">
                <label>Select Category:</label>
                <select name="blocks[${blockIndex}][category]" class="block-category" style="width:100%; height:36px; padding:0 6px;">
                    <option value="">-- Select Category --</option>
                    ${newsletterData.categories.map(cat => `<option value="${cat.term_id}">${cat.name}</option>`).join('')}
                </select>
            </div>
            <div style="width: 200px;" class="template-select">
                <label>Template:</label>
                <select name="blocks[${blockIndex}][template_id]" class="block-template" style="width:100%; height:36px; padding:0 6px;">
                    <option value="default">Default Template</option>
                    ${newsletterData.availableTemplates.filter(t => t.id !== 'default').map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                </select>
            </div>
        </div>
        <div class="date-range-row" style="margin-bottom:10px;">
            <label>Date Range:</label>
            <select name="blocks[${blockIndex}][date_range]" class="block-date-range" style="width:200px; height:36px; padding:0 6px;">
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
        <div class="story-count-row" style="margin-bottom:10px;">
            <label>Number of Stories:</label>
            <select name="blocks[${blockIndex}][story_count]" class="block-story-count" style="width:200px; height:36px; padding:0 6px;">
                <option value="all">All</option>
                ${Array.from({length: 10}, (_, i) => i + 1).map(num => 
                    `<option value="${num}" ${num === 5 ? 'selected' : ''}>${num}</option>`
                ).join('')}
            </select>
        </div>
        <div class="wysiwyg-block" style="display:none;">
            <label>WYSIWYG Content:</label>
            <textarea name="blocks[${blockIndex}][wysiwyg]" rows="15" style="width:100%;"></textarea>
        </div>
        <div class="content-block">
            <div class="block-posts">
                <p>Please select a category to display posts.</p>
            </div>
        </div>
        <div class="html-block" style="display:none;">
            <label>Custom HTML:</label>
            <textarea name="blocks[${blockIndex}][html]" rows="5" style="width:100%;"></textarea>
        </div>
        <button type="button" class="button remove-block">Remove Block</button>
    </div>
</div>`;

        $('#blocks-container').append(blockHtml);
        var newBlock = $('#blocks-container .block-item').last();
        initializeBlockEvents(newBlock);

        // Initialize click handler for the new block
        newBlock.find('.block-header').on('click', function(e) {
            e.preventDefault();
            var $content = $(this).closest('.block-item').find('.block-content');
            $('.block-content').not($content).slideUp();
            $content.slideToggle();
        });

        // After adding a new block, update the preview
        updatePreview();
    };

    $(document).ready(function() {
        // Initialize existing blocks
        $('#blocks-container .block-item').each(function() {
            initializeBlockEvents($(this));
        });

        // Remove block triggers a preview update
        $(document).on('click', '.remove-block', function() {
            var block = $(this).closest('.block-item');
            block.remove();
            updatePreview();
        });

        // Handle story count change
        $(document).on('change', '.story-count', function(e) {
            e.preventDefault();
            var storyCount = $(this).val();
            var blockContainer = $(this).closest('.block-container');
            var checkboxes = blockContainer.find('.sortable-posts input[type="checkbox"]');
            
            // Uncheck all first
            checkboxes.prop('checked', false);
            
            // Then check based on story count
            if (storyCount === 'all') {
                checkboxes.prop('checked', true);
            } else {
                checkboxes.slice(0, parseInt(storyCount)).prop('checked', true);
            }
            
            // Update order values
            updatePostOrder(blockContainer);
        });

        // Update post order
        function updatePostOrder(blockContainer) {
            blockContainer.find('.sortable-posts li').each(function(index) {
                $(this).find('.post-order').val(index);
            });
        }
    });

    // Update preview function
    window.updatePreview = function() {
        // Store current story count values
        var storyCountValues = {};
        $('.block-story-count').each(function() {
            var $block = $(this).closest('.block-item');
            var blockIndex = $block.data('index');
            storyCountValues[blockIndex] = $(this).val();
        });
        
        console.log('Stored story count values:', storyCountValues);
        
        // Call the actual preview generation
        generatePreview();
        
        // Restore story count values
        setTimeout(function() {
            $('.block-story-count').each(function() {
                var $block = $(this).closest('.block-item');
                var blockIndex = $block.data('index');
                if (storyCountValues[blockIndex]) {
                    $(this).val(storyCountValues[blockIndex]);
                }
            });
            console.log('Restored story count values:', storyCountValues);
        }, 100);
    };
})(jQuery);
