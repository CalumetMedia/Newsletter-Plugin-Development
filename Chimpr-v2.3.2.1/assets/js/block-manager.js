(function($) {
    // Global initialization flag
    window.blockManagerInitialized = false;
    
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
                    
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
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
        initializeSortable(block);

        // Initial load if category is already selected
        var categoryId = block.find('.block-category').val();
        var dateRange = block.find('.block-date-range').val();
        var blockIndex = block.data('index');
        var storyCount = block.find('.block-story-count').val();
        
        if (categoryId) {
            // Get initial saved selections from rendered checkboxes
            var savedSelections = {};
            block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
                var $this = $(this);
                var postId = $this.closest('li').data('post-id');
                var $orderInput = $this.closest('li').find('.post-order');
                var order = $orderInput.length ? $orderInput.val() : '0';
                
                // Include all posts in savedSelections, whether checked or not
                savedSelections[postId] = {
                    selected: $this.is(':checked'),
                    order: order
                };
            });

            console.log('Initial load for block:', {
                categoryId: categoryId,
                dateRange: dateRange,
                blockIndex: blockIndex,
                storyCount: storyCount,
                savedSelections: savedSelections
            });

            var data = {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: blockIndex,
                date_range: dateRange,
                story_count: storyCount,
                newsletter_slug: newsletterData.newsletterSlug,
                saved_selections: JSON.stringify(savedSelections)
            };
            
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
                        block.find('.block-posts').html(response.data);
                        initializeSortable(block);
                        // Don't call updatePreview here - it will be called once after all blocks are initialized
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    block.find('.block-posts').removeClass('loading');
                }
            });
        }

        // When a post is selected/unselected, update the preview
        block.on('change', 'input[type="checkbox"][name*="[selected]"]', function() {
            var $this = $(this);
            var $block = $this.closest('.block-item');
            var $storyCount = $block.find('.block-story-count');
            
            // Switch to manual mode if user manually changes checkbox
            if ($storyCount.val() !== 'disable') {
                $storyCount.val('disable');
            }
            
            var $orderInput = $this.closest('li').find('.post-order');
            if ($this.is(':checked')) {
                // Ensure order value is set when checked
                if (!$orderInput.val() || $orderInput.val() === '0') {
                    var maxOrder = 0;
                    block.find('.post-order').each(function() {
                        var val = parseInt($(this).val()) || 0;
                        maxOrder = Math.max(maxOrder, val);
                    });
                    $orderInput.val(maxOrder + 1);
                }
            }
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
                // Get current selections for this block
                var savedSelections = {};
                $block.find('input[type="checkbox"][name*="[selected]"]:checked').each(function() {
                    var postId = $(this).closest('li').data('post-id');
                    savedSelections[postId] = {
                        selected: true,
                        order: $(this).closest('li').find('.post-order').val()
                    };
                });

                var data = {
                    action: 'load_block_posts',
                    security: newsletterData.nonceLoadPosts,
                    category_id: categoryId,
                    block_index: blockIndex,
                    date_range: dateRange,
                    story_count: storyCount,
                    newsletter_slug: newsletterData.newsletterSlug,
                    saved_selections: JSON.stringify(savedSelections)
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
                // Get current selections for this block
                var savedSelections = {};
                $block.find('input[type="checkbox"][name*="[selected]"]:checked').each(function() {
                    var postId = $(this).closest('li').data('post-id');
                    savedSelections[postId] = {
                        selected: true,
                        order: $(this).closest('li').find('.post-order').val()
                    };
                });

                var data = {
                    action: 'load_block_posts',
                    security: newsletterData.nonceLoadPosts,
                    category_id: categoryId,
                    block_index: blockIndex,
                    date_range: dateRange,
                    story_count: storyCount,
                    newsletter_slug: newsletterData.newsletterSlug,
                    saved_selections: JSON.stringify(savedSelections)
                };
                
                console.log('Date range change sending request:', data);
                
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
            }
        });

        // Story count change handler
        block.find('.block-story-count').off('change').on('change', function(e) {
            var $block = $(this).closest('.block-item');
            var categoryId = $block.find('.block-category').val();
            var storyCount = $(this).val();
            
            console.log('Story count changed:', {
                value: storyCount,
                event: e,
                block: $block,
                categoryId: categoryId,
                $this: $(this),
                $thisVal: $(this).val(),
                $storyCount: $block.find('.block-story-count'),
                $storyCountVal: $block.find('.block-story-count').val()
            });
            
            if (!categoryId) {
                console.log('No category selected, skipping load');
                return;
            }
            
            var dateRange = $block.find('.block-date-range').val();
            var blockIndex = $block.data('index');
            
            // Get current selections for this block
            var savedSelections = {};
            $block.find('input[type="checkbox"][name*="[selected]"]:checked').each(function() {
                var postId = $(this).closest('li').data('post-id');
                savedSelections[postId] = {
                    selected: true,
                    order: $(this).closest('li').find('.post-order').val()
                };
            });
            
            var data = {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: blockIndex,
                date_range: dateRange,
                story_count: storyCount,
                newsletter_slug: newsletterData.newsletterSlug,
                saved_selections: JSON.stringify(savedSelections)
            };
            
            console.log('Story count change sending request:', data);
            
            $.ajax({
                url: newsletterData.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $block.find('.block-posts').html(response.data);
                        initializeSortable($block);
                        updatePreview();
                    }
                }
            });
        });

        // Initial setup
        var blockType = block.find('.block-type').val();
        handleBlockTypeChange(block, blockType);

        // When WYSIWYG or HTML content changes, update preview
        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            updatePreview();
        });

        // Block type change
        block.find('.block-type').off('change').on('change', function() {
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            updatePreview();
        });

        // Don't call updatePreview here - it will be called once after all blocks are initialized
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
        
        // Then get any hidden inputs that store saved selections
        block.find('input[type="hidden"][name*="[posts]"][name*="[saved_selections]"]').each(function() {
            try {
                var savedData = JSON.parse($(this).val());
                if (savedData && typeof savedData === 'object') {
                    Object.keys(savedData).forEach(function(postId) {
                        if (savedData[postId].selected) {
                            savedSelections[postId] = savedData[postId];
                        }
                    });
                }
            } catch (e) {
                console.error('Error parsing saved selections:', e);
            }
        });

        // Also check for any data-was-selected attributes on the list items
        block.find('li[data-post-id][data-was-selected="1"]').each(function() {
            var $li = $(this);
            var postId = $li.data('post-id');
            var $orderInput = $li.find('.post-order');
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
                    
                    // After loading new content, restore any saved selections
                    Object.keys(savedSelections).forEach(function(postId) {
                        var selection = savedSelections[postId];
                        if (selection.selected) {
                            var $li = block.find('li[data-post-id="' + postId + '"]');
                            if ($li.length) {
                                var $checkbox = $li.find('input[type="checkbox"][name*="[selected]"]');
                                var $orderInput = $li.find('.post-order');
                                
                                if ($checkbox.length) {
                                    $checkbox.prop('checked', true);
                                }
                                if ($orderInput.length && selection.order) {
                                    $orderInput.val(selection.order);
                                }
                            }
                        }
                    });
                    
                    initializeSortable(block);
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
<div class="block-item" data-index="${blockIndex}">
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
                <option value="disable" selected>Manual Selection</option>
                <option value="all">All</option>
                ${Array.from({length: 10}, (_, i) => i + 1).map(num => 
                    `<option value="${num}">${num}</option>`
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

        // Reinitialize accordion for all blocks
        $("#blocks-container").accordion('destroy').accordion({
            header: ".block-header",
            collapsible: true,
            active: blockIndex,  // Activate the new block
            heightStyle: "content"
        });

        // After adding a new block, update the preview
        updatePreview();
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
            
            // Set up event handlers
            initializeBlockEvents($block);
            
            // Only do the initial load for blocks that have a category selected
            var categoryId = $block.find('.block-category').val();
            if (categoryId) {
                var dateRange = $block.find('.block-date-range').val();
                var blockIndex = $block.data('index');
                var storyCount = $block.find('.block-story-count').val();
                
                // Get initial saved selections from rendered checkboxes
                var savedSelections = {};
                $block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
                    var $this = $(this);
                    var postId = $this.closest('li').data('post-id');
                    var $orderInput = $this.closest('li').find('.post-order');
                    var order = $orderInput.length ? $orderInput.val() : '0';
                    
                    savedSelections[postId] = {
                        selected: $this.is(':checked'),
                        order: order
                    };
                });

                var promise = loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount);
                loadPromises.push(promise);
            }
            initializedBlocks++;
        });

        // Wait for all blocks to load before updating preview
        $.when.apply($, loadPromises).always(function() {
            setTimeout(function() {
                updatePreview();
            }, 100);
        });

        // Remove block triggers a preview update
        $(document).on('click', '.remove-block', function() {
            var block = $(this).closest('.block-item');
            block.remove();
            updatePreview();
        });
    });
})(jQuery);