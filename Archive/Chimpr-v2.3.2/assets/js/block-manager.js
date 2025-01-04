(function($) {
    // Initialize sortable for posts
    window.initializeSortable = function(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.story-drag-handle',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    // After reordering posts, update the preview
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

        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.category-select').show();
            block.find('.template-select').show();
            block.find('.date-range-row').show();
        } else if (blockType === 'html') {
            block.find('.html-block').show();
        } else if (blockType === 'wysiwyg') {
            block.find('.wysiwyg-block').show();
        }
    };

    // Initialize events for a block
    window.initializeBlockEvents = function(block) {
        initializeSortable(block);

        // When a post is selected/unselected, update the preview
    block.on('change', 'input[type="checkbox"][name*="[selected]"]', function() {
        var $this = $(this);
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

        // When WYSIWYG or HTML content changes, update preview
        block.find('.html-block textarea, .wysiwyg-block textarea').on('input', function() {
            updatePreview();
        });

        // Category change triggers AJAX load and preview update
        block.find('.block-category').off('change').on('change', function() {
            var categoryId = $(this).val();
            var dateRange = block.find('.block-date-range').val();
            var blockIndex = block.data('index');
            if (categoryId) {
                loadBlockPosts(block, categoryId, blockIndex, dateRange);
            } else {
                block.find('.block-posts').html('<p>Please select a category to display posts.</p>');
                // Update preview to reflect no posts selected
                updatePreview();
            }
        });

        // Date range change triggers AJAX load if category selected
        block.find('.block-date-range').off('change').on('change', function() {
            var categoryId = block.find('.block-category').val();
            var dateRange = $(this).val();
            var blockIndex = block.data('index');
            if (categoryId) {
                loadBlockPosts(block, categoryId, blockIndex, dateRange);
            } else {
                // Update preview even if no category
                updatePreview();
            }
        });

        // Block type change
        block.find('.block-type').off('change').on('change', function() {
            var newBlockType = $(this).val();
            handleBlockTypeChange(block, newBlockType);
            updatePreview();
        });

        // Initial setup
        var blockType = block.find('.block-type').val();
        handleBlockTypeChange(block, blockType);

        // Initial preview update
        updatePreview();
    };

    // Load block posts via AJAX
    window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: currentIndex,
                date_range: dateRange,
                newsletter_slug: newsletterData.newsletterSlug
            },
            success: function(response) {
                if (response.success) {
                    block.find('.block-posts').html(response.data);
                    initializeSortable(block);
                    // After loading posts, update the preview to reflect new lineup
                    updatePreview();
                }
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
            initializeBlockEvents($(this));
        });


        // Remove block triggers a preview update
        $(document).on('click', '.remove-block', function() {
            var block = $(this).closest('.block-item');
            block.remove();
            updatePreview();
        });
    });
})(jQuery);
