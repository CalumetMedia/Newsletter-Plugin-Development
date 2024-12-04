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
                // Update order values
                block.find('.sortable-posts li').each(function(index) {
                    $(this).find('.post-order').val(index);
                });
                updatePreview();
            }
        });
    }

    // Add Block
    $('#add-block').click(function() {
        var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
        blockHtml += '<h3 class="block-header">' + newsletterData.blockLabel + ' ' + (blockIndex + 1) + '</h3>';
        blockHtml += '<div class="block-content">';
        blockHtml += '<label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" value="" />';

        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="advertising">' + newsletterData.advertisingLabel + '</option>';
        blockHtml += '</select>';

        blockHtml += '<div class="content-block">';
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
        blockHtml += '</div>';

        $('#blocks-container').append(blockHtml);
        $("#blocks-container").accordion("refresh");
        blockIndex++;
    });

    // Remove Block
    $(document).on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();
        $("#blocks-container").accordion("refresh");
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
            $(this).find('.block-posts input[type="checkbox"]').each(function() {
                var postId = $(this).closest('li').data('post-id');
                $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][selected]');
            });
            $(this).find('.block-posts .post-order').each(function() {
                var postId = $(this).closest('li').data('post-id');
                $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][order]');
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
        updatePreview();
    });

    // Load Posts when Category Changes
    $(document).on('change', '.block-category', function() {
        var block = $(this).closest('.block-item');
        var categoryId = $(this).val();
        var blockIndex = block.data('index');

        if (categoryId) {
            loadBlockPosts(block, categoryId, blockIndex);
        } else {
            block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
        }
    });

    // Function to Load Block Posts
    function loadBlockPosts(block, categoryId, blockIndex) {
        // Get newsletter slug from URL parameters
        var urlParams = new URLSearchParams(window.location.search);
        var newsletter_slug = '';
        
        if (urlParams.has('tab')) {
            newsletter_slug = urlParams.get('tab');
        } else if (urlParams.has('page')) {
            var page = urlParams.get('page');
            if (page.startsWith('newsletter-stories-')) {
                newsletter_slug = page.replace('newsletter-stories-', '');
            }
        }

        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();


        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: blockIndex,
                newsletter_slug: newsletter_slug,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    block.find('.block-posts').html(response.data);
                    initializeSortable(block);
                    updatePreview();
                } else {
                    block.find('.block-posts').html('<p>' + response.data + '</p>');
                }
            }
        });
    }

// Add this near your other event handlers
$('#send-to-mailchimp').click(function(e) {
    e.preventDefault();
    
    if (!confirm('Are you sure you want to send this newsletter to Mailchimp?')) {
        return;
    }

    var urlParams = new URLSearchParams(window.location.search);
    var newsletter_slug = '';
    
    if (urlParams.has('tab')) {
        newsletter_slug = urlParams.get('tab');
    } else if (urlParams.has('page')) {
        var page = urlParams.get('page');
        if (page.startsWith('newsletter-stories-')) {
            newsletter_slug = page.replace('newsletter-stories-', '');
        }
    }

    console.log('AJAX URL:', newsletterData.ajaxUrl);
    console.log('Newsletter Slug:', newsletter_slug);
    console.log('Security Nonce:', newsletterData.nonceMailchimp);



    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        data: {
            action: 'create_mailchimp_campaign',
            security: newsletterData.nonceMailchimp,
            newsletter_slug: newsletter_slug
        },
        success: function(response) {
            if (response.success) {
                alert('Newsletter sent to Mailchimp successfully!');
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('Error connecting to server');
        }
    });
});




    // Save Blocks
    $('#save-blocks').click(function(e) {
        e.preventDefault();
        
        // Get newsletter slug from URL parameters
        var urlParams = new URLSearchParams(window.location.search);
        var newsletter_slug = '';
        
        if (urlParams.has('tab')) {
            newsletter_slug = urlParams.get('tab');
        } else if (urlParams.has('page')) {
            var page = urlParams.get('page');
            if (page.startsWith('newsletter-stories-')) {
                newsletter_slug = page.replace('newsletter-stories-', '');
            }
        }

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'save_newsletter_blocks' });
        formData.push({ name: 'security', value: newsletterData.nonceSaveBlocks });
        formData.push({ name: 'newsletter_slug', value: newsletter_slug });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: formData,
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
        // Get newsletter slug from URL parameters
        var urlParams = new URLSearchParams(window.location.search);
        var newsletter_slug = '';
        
        if (urlParams.has('tab')) {
            newsletter_slug = urlParams.get('tab');
        } else if (urlParams.has('page')) {
            var page = urlParams.get('page');
            if (page.startsWith('newsletter-stories-')) {
                newsletter_slug = page.replace('newsletter-stories-', '');
            }
        }

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletter_slug });
        formData.push({ name: 'template_id', value: $('#selected_template_id').val() || 'default' });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

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

    // Initial Preview Update
    updatePreview();

    // Initialize Sortable for Existing Blocks
    $('.block-item').each(function() {
        initializeSortable($(this));
    });
});