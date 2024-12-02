jQuery(document).ready(function($) {
    // Cache selectors for better performance
    const $blocksContainer = $('#blocks-container');
    const $startDate = $("#start_date");
    const $endDate = $("#end_date");
    const $blocksForm = $('#blocks-form');
    const $previewContent = $('#preview-content');
    const $saveBlocks = $('#save-blocks');
    const $selectedTemplate = $('#selected_template_id');

    // Initialize blockIndex based on existing blocks
    let blockIndex = $blocksContainer.find('.block-item').length || 0;

    // Initialize Datepicker
    function initializeDatepicker() {
        $startDate.add($endDate).datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showAnim: "slideDown",
            onSelect: reloadAllBlockPosts
        });
    }

    // Reload posts in all blocks when date range changes
    function reloadAllBlockPosts() {
        $blocksContainer.find('.block-item').each(function() {
            const $block = $(this);
            const blockType = $block.find('.block-type').val();
            const categoryId = $block.find('.block-category').val();
            const currentBlockIndex = $block.data('index');

            if (blockType === 'content' && categoryId) {
                loadBlockPosts($block, categoryId, currentBlockIndex);
            }
        });
    }

    // Initialize Accordions
    function initializeAccordions() {
        $blocksContainer.accordion({
            header: ".block-header",
            collapsible: true,
            active: false,
            heightStyle: "content"
        });
    }

    // Initialize Sortable for selected posts
    function initializeSortable($block) {
        $block.find('.sortable-posts').sortable({
            handle: '.drag-handle',
            placeholder: 'sortable-placeholder',
            update: function(event, ui) {
                // Update the order values in hidden inputs
                $block.find('.sortable-posts li').each(function(index) {
                    $(this).find('.post-order').val(index);
                });
                updatePreview();
            }
        }).disableSelection();

        // Initial setting of order values
        $block.find('.sortable-posts li').each(function(index) {
            $(this).find('.post-order').val(index);
        });
    }

    // Add Block
    function addBlock() {
        const newBlockHtml = `
            <div class="block-item" data-index="${blockIndex}">
                <h3 class="block-header">${newsletterData.blockLabel} ${blockIndex + 1}</h3>
                <div class="block-content">
                    <label>${newsletterData.blockTitleLabel}</label>
                    <input type="text" name="blocks[${blockIndex}][title]" class="block-title-input" value="" />

                    <label>${newsletterData.blockTypeLabel}</label>
                    <select name="blocks[${blockIndex}][type]" class="block-type">
                        <option value="content">${newsletterData.contentLabel}</option>
                        <option value="advertising">${newsletterData.advertisingLabel}</option>
                    </select>

                    <div class="content-block">
                        <label>${newsletterData.selectCategoryLabel}</label>
                        <select name="blocks[${blockIndex}][category]" class="block-category">
                            <option value="">${newsletterData.selectCategoryOption}</option>
                            ${newsletterData.categories.map(category => `
                                <option value="${category.term_id}">${category.name}</option>
                            `).join('')}
                        </select>

                        <div class="block-posts">
                            <p>${newsletterData.selectCategoryPrompt}</p>
                        </div>
                    </div>

                    <div class="advertising-block" style="display:none;">
                        <label>${newsletterData.advertisingHtmlLabel}</label>
                        <textarea name="blocks[${blockIndex}][html]" rows="5" style="width:100%;"></textarea>
                    </div>

                    <button type="button" class="button remove-block">${newsletterData.removeBlockLabel}</button>
                </div>
            </div>
        `;

        $blocksContainer.append(newBlockHtml);

        // Initialize Sortable for the new block
        const $newBlock = $blocksContainer.find('.block-item').last();
        initializeSortable($newBlock);

        // Refresh Accordion to recognize new block
        $blocksContainer.accordion("refresh");

        blockIndex++;
    }

    // Remove Block
    function removeBlock() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();

        // Refresh Accordion and Update Preview
        $blocksContainer.accordion("refresh");
        updatePreview();
    }

    // Update Block Indices after removal or reordering
    function updateBlockIndices() {
        $blocksContainer.find('.block-item').each(function(index) {
            const $block = $(this);
            $block.attr('data-index', index);

            const blockTitle = $block.find('.block-title-input').val() || `${newsletterData.blockLabel} ${index + 1}`;
            $block.find('.block-header').text(blockTitle);

            $block.find('.block-title-input').attr('name', `blocks[${index}][title]`);
            $block.find('.block-type').attr('name', `blocks[${index}][type]`);
            $block.find('.block-category').attr('name', `blocks[${index}][category]`);

            // Update post checkboxes and order inputs
            $block.find('.block-posts input[type="checkbox"]').each(function() {
                const postId = $(this).val();
                $(this).attr('name', `blocks[${index}][posts][${postId}][selected]`);
            });
            $block.find('.block-posts .post-order').each(function() {
                const postId = $(this).closest('li').data('post-id');
                $(this).attr('name', `blocks[${index}][posts][${postId}][order]`);
            });

            $block.find('.advertising-block textarea').attr('name', `blocks[${index}][html]`);
        });

        blockIndex = $blocksContainer.find('.block-item').length;
    }

    // Handle Block Title Change
    function handleBlockTitleChange() {
        const $block = $(this).closest('.block-item');
        const blockTitle = $(this).val() || `${newsletterData.blockLabel} ${$block.data('index') + 1}`;
        $block.find('.block-header').text(blockTitle);
    }

    // Handle Block Type Change
    function handleBlockTypeChange() {
        const $block = $(this).closest('.block-item');
        const blockType = $(this).val();

        if (blockType === 'content') {
            $block.find('.content-block').show();
            $block.find('.advertising-block').hide();
        } else if (blockType === 'advertising') {
            $block.find('.content-block').hide();
            $block.find('.advertising-block').show();
        }

        updatePreview();
    }

    // Handle Category Change
    function handleCategoryChange() {
        const $block = $(this).closest('.block-item');
        const categoryId = $(this).val();
        const currentBlockIndex = $block.data('index');

        if (categoryId) {
            loadBlockPosts($block, categoryId, currentBlockIndex);
        } else {
            $block.find('.block-posts').html(`<p>${newsletterData.selectCategoryPrompt}</p>`);
            updatePreview();
        }
    }

    // Load Posts for a Block via AJAX
    function loadBlockPosts($block, categoryId, currentBlockIndex) {
        const startDate = $startDate.val();
        const endDate = $endDate.val();

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_block_posts',
                category_id: categoryId,
                block_index: currentBlockIndex,
                start_date: startDate,
                end_date: endDate,
                newsletter_slug: newsletterData.newsletterSlug,
                security: newsletterData.nonceLoadPosts
            }
        })
        .done(function(response) {
            if (response.success) {
                $block.find('.block-posts').html(response.data);
                initializeSortable($block);
                updatePreview();
            } else {
                $block.find('.block-posts').html(`<p>${response.data}</p>`);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $block.find('.block-posts').html('<p>An error occurred while loading posts.</p>');
        });
    }

    // Update Preview on Form Changes
    function handleFormChange() {
        updatePreview();
    }

    // Update Preview on Template Selection Change
    function handleTemplateChange() {
        const selectedTemplateId = $(this).val();

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'update_template_selection',
                newsletter_slug: newsletterData.newsletterSlug,
                template_id: selectedTemplateId,
                security: newsletterData.nonceUpdateTemplateSelection
            }
        })
        .done(function(response) {
            if (response.success) {
                updatePreview();
            } else {
                alert(response.data);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('An error occurred while updating the template selection.');
        });
    }

    // Function to Update Preview via AJAX
    function updatePreview() {
        const formData = $blocksForm.serializeArray();
        formData.push(
            { name: 'action', value: 'generate_preview' },
            { name: 'newsletter_slug', value: newsletterData.newsletterSlug },
            { name: 'template_id', value: $selectedTemplate.val() },
            { name: 'security', value: newsletterData.nonceGeneratePreview }
        );

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: formData
        })
        .done(function(response) {
            if (response.success) {
                $previewContent.html(response.data);
            } else {
                $previewContent.html(`<p>${response.data}</p>`);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $previewContent.html('<p>An error occurred while generating the preview.</p>');
        });
    }

    // Save Blocks via AJAX
    function saveBlocks(e) {
        e.preventDefault();

        const formData = $blocksForm.serializeArray();
        formData.push(
            { name: 'action', value: 'save_newsletter_blocks' },
            { name: 'newsletter_slug', value: newsletterData.newsletterSlug },
            { name: 'security', value: newsletterData.nonceSaveBlocks }
        );

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: formData
        })
        .done(function(response) {
            if (response.success) {
                alert(response.data); // Success message
            } else {
                alert(`Error: ${response.data}`); // Error message
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('An error occurred while saving the blocks.');
        });
    }

    // Initialize Existing Blocks
    function initializeExistingBlocks() {
        $blocksContainer.find('.block-item').each(function() {
            const $block = $(this);
            const blockType = $block.find('.block-type').val();
            const categoryId = $block.find('.block-category').val();
            const currentBlockIndex = $block.data('index');

            if (blockType === 'content') {
                // Initialize Sortable
                initializeSortable($block);

                if (categoryId) {
                    loadBlockPosts($block, categoryId, currentBlockIndex);
                }
            }
        });
    }

    // Event Bindings
    function bindEvents() {
        // Add Block Button
        $('#add-block').on('click', addBlock);

        // Remove Block Button (using event delegation)
        $blocksContainer.on('click', '.remove-block', removeBlock);

        // Block Title Input Change
        $blocksContainer.on('keyup', '.block-title-input', handleBlockTitleChange);

        // Block Type Change
        $blocksContainer.on('change', '.block-type', handleBlockTypeChange);

        // Category Change
        $blocksContainer.on('change', '.block-category', handleCategoryChange);

        // Form Input Changes
        $blocksForm.on('change', 'input, select, textarea', handleFormChange);

        // Template Selection Change
        $selectedTemplate.on('change', handleTemplateChange);

        // Save Blocks Button
        $saveBlocks.on('click', saveBlocks);
    }

    // Initialize All Components
    function initialize() {
        initializeDatepicker();
        initializeAccordions();
        bindEvents();
        initializeExistingBlocks();
        updatePreview();
    }

    // Start Initialization
    initialize();
});
