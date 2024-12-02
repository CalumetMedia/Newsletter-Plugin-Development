jQuery(document).ready(function($) {
    var blockIndex = $('#blocks-container .block-item').length || 0;

    // Initialize Datepicker
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown",
        onSelect: function() {
            // Reload posts in blocks when date range changes
            $('#blocks-container .block-item').each(function() {
                var block = $(this);
                var blockType = block.find('.block-type').val();
                var categoryId = block.find('.block-category').val();
                var blockIndex = block.data('index');

                if (blockType === 'content' && categoryId) {
                    // Load posts for existing blocks
                    loadBlockPosts(block, categoryId, blockIndex);
                }
            });
        }
    });

    // Initialize Accordions
    $("#blocks-container").accordion({
        header: ".block-header",
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    // Initialize Sortable for selected posts
    function initializeSortable(block) {
        block.find('.sortable-posts').sortable({
            handle: '.dashicons-menu',
            update: function(event, ui) {
                // Update Preview
                updatePreview();
            }
        });
    }

    // Add Block
    $('#add-block').click(function() {
        var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
        blockHtml += '<h3 class="block-header">' + newsletterData.blockLabel + ' ' + (blockIndex + 1) + '</h3>';
        blockHtml += '<div class="block-content">';
        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="advertising">' + newsletterData.advertisingLabel + '</option>';
        blockHtml += '</select>';

        blockHtml += '<div class="content-block">';
        blockHtml += '<label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" value="" />';

        blockHtml += '<label>' + newsletterData.selectCategoryLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][category]" class="block-category">';
        blockHtml += '<option value="">' + newsletterData.selectCategoryOption + '</option>';
        $.each(newsletterData.categories, function(index, category) {
            blockHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });
        blockHtml += '</select>';

        blockHtml += '<div class="block-posts">';
        blockHtml += '<p>' + newsletterData.selectCategoryPrompt + '</p>';
        blockHtml += '</div>';
        blockHtml += '</div>';

        blockHtml += '<div class="advertising-block" style="display:none;">';
        blockHtml += '<label>' + newsletterData.advertisingHtmlLabel + '</label>';
        blockHtml += '<textarea name="blocks[' + blockIndex + '][html]" rows="5" style="width:100%;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<button type="button" class="button remove-block">' + newsletterData.removeBlockLabel + '</button>';
        blockHtml += '</div>';
        blockHtml += '<hr>';
        blockHtml += '</div>';

        $('#blocks-container').append(blockHtml);

        // Refresh Accordion
        $("#blocks-container").accordion("refresh");

        blockIndex++;
    });

    // Remove Block
    $(document).on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();

        // Refresh Accordion
        $("#blocks-container").accordion("refresh");

        // Update Preview
        updatePreview();
    });

    // Update Block Indices
    function updateBlockIndices() {
        $('#blocks-container .block-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.block-header').text(newsletterData.blockLabel + ' ' + (index + 1));
            $(this).find('input[type="text"]').attr('name', 'blocks[' + index + '][title]');
            $(this).find('.block-type').attr('name', 'blocks[' + index + '][type]');
            $(this).find('.block-category').attr('name', 'blocks[' + index + '][category]');
            $(this).find('.block-posts input[type="hidden"]').each(function() {
                $(this).attr('name', 'blocks[' + index + '][posts][]');
            });
            $(this).find('.advertising-block textarea').attr('name', 'blocks[' + index + '][html]');
        });
        blockIndex = $('#blocks-container .block-item').length;
    }

    // Handle Block Type Change
    $(document).on('change', '.block-type', function() {
        var block = $(this).closest('.block-item');
        var blockType = $(this).val();

        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.advertising-block').hide();
        } else if (blockType === 'advertising') {
            block.find('.content-block').hide();
            block.find('.advertising-block').show();
        }

        // Update Preview
        updatePreview();
    });

    // Load Posts when Category Changes or Date Range Changes
    $(document).on('change', '.block-category', function() {
        var block = $(this).closest('.block-item');
        var categoryId = $(this).val();
        var blockIndex = block.data('index');

        if (categoryId) {
            // Load posts for existing blocks
            loadBlockPosts(block, categoryId, blockIndex);
        } else {
            block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
        }
    });

    // Function to Load Block Posts
    function loadBlockPosts(block, categoryId, blockIndex) {
        // Fetch posts via AJAX
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_block_posts',
                category_id: categoryId,
                block_index: blockIndex,
                start_date: startDate,
                end_date: endDate,
                security: newsletterData.nonce_load_posts
            },
            success: function(response) {
                if (response.success) {
                    block.find('.block-posts').html(response.data);

                    // Initialize Sortable for the new list
                    initializeSortable(block);

                    // Update Preview
                    updatePreview();
                } else {
                    block.find('.block-posts').html('<p>' + response.data + '</p>');
                }
            }
        });
    }

    // Add Post from Available Posts to Selected Posts
    $(document).on('click', '.available-posts li', function() {
        var postId = $(this).data('post-id');
        var postTitle = $(this).text();
        var block = $(this).closest('.block-item');
        var blockIndex = block.data('index');

        var listItem = '<li data-post-id="' + postId + '"><span class="dashicons dashicons-menu"></span> ' + postTitle + ' <input type="hidden" name="blocks[' + blockIndex + '][posts][]" value="' + postId + '"></li>';
        block.find('.sortable-posts').append(listItem);

        // Remove from available posts
        $(this).remove();

        // Initialize Sortable
        initializeSortable(block);

        // Update Preview
        updatePreview();
    });

    // Update Preview on Form Changes
    $('#blocks-form').on('change', 'input, select, textarea', function() {
        updatePreview();
    });

    $('#selected_template_id').change(function() {
        // Update the template selection
        var selectedTemplateId = $(this).val();
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'update_template_selection',
                newsletter_id: newsletterData.newsletterId,
                template_id: selectedTemplateId,
                security: newsletterData.nonce_update_template_selection
            },
            success: function(response) {
                if (response.success) {
                    updatePreview();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Function to Update Preview
    function updatePreview() {
        // Gather form data
        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_id', value: newsletterData.newsletterId });
        formData.push({ name: 'template_id', value: $('#selected_template_id').val() });
        formData.push({ name: 'security', value: newsletterData.nonce_generate_preview });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#preview-content').html(response.data);
                } else {
                    $('#preview-content').html('<p>' + response.data + '</p>');
                }
            }
        });
    }

    // Initial Posts Load and Preview Update
    $('#blocks-container .block-item').each(function() {
        var block = $(this);
        var blockType = block.find('.block-type').val();
        var categoryId = block.find('.block-category').val();
        var blockIndex = block.data('index');

        if (blockType === 'content' && categoryId) {
            // Load posts for existing blocks
            loadBlockPosts(block, categoryId, blockIndex);
        }
    });

    // Update Preview on Page Load
    updatePreview();
});
