jQuery(document).ready(function($) {
    var blockIndex = $('#blocks-container .block-item').length || 0;

    // Initialize Datepicker
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown",
        onSelect: function() {
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



    // Accordion-like functionality
$(document).off('click', '.block-header');

// Add specific click handler for toggle
$(document).on('click', '.block-accordion-toggle', function(e) {
    e.stopPropagation();
    var $content = $(this).closest('.block-item').find('.block-content');
    $content.slideToggle();
    $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
});

// Update sortable initialization
$("#blocks-container").sortable({
    handle: '.drag-handle',
    items: '> .block-item',
    axis: 'y',
    opacity: 0.7,
    update: function() {
        updateBlockIndices();
        updatePreview();
    }
}).disableSelection();



    // Initialize sortable for posts
    function initializeSortable(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.dashicons-menu',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
                }
            });
        }
    }

    // Initialize Post Checkboxes
    function initializePostCheckboxes(block) {
        var checkboxes = block.find('input[type="checkbox"]');
        checkboxes.on('change', function() {
            updatePreview();
        });
    }

    // Add Block
    $('#add-block').click(function() {
var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
blockHtml += '<h3 class="block-header">';
blockHtml += '<span class="dashicons dashicons-sort drag-handle"></span>'; // Drag handle icon
blockHtml += '<span class="block-title">' + newsletterData.blockLabel + ' ' + (blockIndex + 1) + '</span>';
blockHtml += '<span class="dashicons dashicons-arrow-down-alt2 block-accordion-toggle"></span>'; // Toggle arrow
blockHtml += '</h3>';
        blockHtml += '<div class="block-content">';
        
        blockHtml += '<label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" class="block-title-input" />';
        
        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="advertising">' + newsletterData.advertisingLabel + '</option>';
        blockHtml += '</select>';

        blockHtml += '<label>' + newsletterData.templateLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][template_id]" class="block-template">';
        blockHtml += '<option value="default">Default Template</option>';
        if (newsletterData.availableTemplates) {
            $.each(newsletterData.availableTemplates, function(index, template) {
                if (template.id !== 'default') {
                    blockHtml += '<option value="' + template.id + '">' + template.name + '</option>';
                }
            });
        }
        blockHtml += '</select>';

        blockHtml += '<div class="content-block">';
        blockHtml += '<label>' + newsletterData.selectCategoryLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][category]" class="block-category">';
        blockHtml += '<option value="">' + newsletterData.selectCategoryOption + '</option>';
        $.each(newsletterData.categories, function(index, category) {
            blockHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });
        blockHtml += '</select>';
        blockHtml += '<div class="block-posts"></div>';
        blockHtml += '</div>';

        blockHtml += '<div class="advertising-block" style="display:none;">';
        blockHtml += '<label>' + newsletterData.advertisingHtmlLabel + '</label>';
        blockHtml += '<textarea name="blocks[' + blockIndex + '][html]" rows="5" style="width:100%;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<button type="button" class="button remove-block">' + newsletterData.removeBlockLabel + '</button>';
        blockHtml += '</div>'; // Close block-content
        blockHtml += '</div>'; // Close block-item

        $('#blocks-container').append(blockHtml);
        blockIndex++;

        var newBlock = $('#blocks-container .block-item').last();
        initializeBlockEvents(newBlock);
    });


    // Function to find a template by ID
    function findTemplateById(id, templates) {
        for (var i = 0; i < templates.length; i++) {
            if (templates[i].id === id) {
                return templates[i];
            }
        }
        return null;
    }

    // Remove Block
    $(document).on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();
        updatePreview();
    });

    // Update Block Indices
    function updateBlockIndices() {
        $('#blocks-container .block-item').each(function(index) {
            $(this).attr('data-index', index);
        $(this).find('.block-title').text(newsletterData.blockLabel + ' ' + (index + 1));
        $(this).find('.block-title-input').attr('name', 'blocks[' + index + '][title]');
        $(this).find('.block-type').attr('name', 'blocks[' + index + '][type]');
        $(this).find('.block-template').attr('name', 'blocks[' + index + '][template_id]');
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
            updatePreview();
        }
    });

    // Function to Load Block Posts
    function loadBlockPosts(block, categoryId, blockIndex) {
        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json', // Ensure the response is parsed as JSON
            data: {
                action: 'load_block_posts',
                security: newsletterData.nonceLoadPosts,
                category_id: categoryId,
                block_index: blockIndex,
                newsletter_slug: newsletterData.newsletterSlug
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    block.find('.block-posts').html(response.data);
                    initializeBlockUI(block);
                } else {
                    console.error('Error loading block posts:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    function initializeBlockUI(block) {
        const sortableList = block.find('.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.dashicons-menu',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    triggerPreviewUpdate();
                }
            });
        }

        block.find('input[type="checkbox"]').on('change', triggerPreviewUpdate);
    }

    function triggerPreviewUpdate() {
        updatePreview();
    }

    // Save Blocks
    $('#save-blocks').click(function(e) {
        e.preventDefault();

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'save_newsletter_blocks' });
        formData.push({ name: 'security', value: newsletterData.nonceSaveBlocks });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });

        // Add manual schedule data
        formData.push({
            name: 'use_manual_schedule',
            value: $('#use_manual_schedule').is(':checked') ? '1' : '0'
        });
        formData.push({
            name: 'manual_schedule_date',
            value: $('#manual_schedule_date').val()
        });
        formData.push({
            name: 'manual_schedule_time',
            value: $('#manual_schedule_time').val()
        });

        // Add custom header/footer
        formData.push({
            name: 'custom_header',
            value: $('#custom_header').val()
        });
        formData.push({
            name: 'custom_footer',
            value: $('#custom_footer').val()
        });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json', // Add dataType
            data: formData,
            success: function(response) {
                if (response.success) {
                    updatePreview();
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                } else {
                    alert(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert(newsletterData.ajaxErrorMessage || 'An error occurred while saving blocks.');
            }
        });
    });

    // Tab switching functionality
    $('.nav-tab').click(function(e) {
        e.preventDefault();

        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');

        // Hide all tab content
        $('.tab-content').hide();
        // Show the selected tab content
        $('#' + $(this).data('tab')).show();

        // Store active tab in localStorage
        localStorage.setItem('activeNewsletterTab', $(this).data('tab'));
    });

    // Check for stored active tab on page load
    var activeTab = localStorage.getItem('activeNewsletterTab');
    if (activeTab) {
        $('.nav-tab[data-tab="' + activeTab + '"]').click();
    }

    // Custom Header/Footer Live Preview
    $(document).on('input', '#custom_header, #custom_footer', function() {
        updatePreview();
    });

    // Function to Update Preview
    function updatePreview() {
        var formData = $('#blocks-form').serializeArray();
        console.log('Preview formData:', formData);

        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json', // Ensure the response is parsed as JSON
            data: formData,
            success: function(response) {
                console.log('Preview response:', response);
                if (response.success) {
                    $('#preview-content').html(response.data);
                } else {
                    $('#preview-content').html('<p>' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Preview Error:', error);
            }
        });
    }

    // Function to Initialize Event Listeners for a Block
    function initializeBlockEvents(block) {
        initializeSortable(block);
        initializePostCheckboxes(block);

        // Initialize event listener for template selection
        block.find('.block-template').on('change', function() {
            updatePreview();
        });
    }

    // Initial Preview Update
    updatePreview();

    // Initialize Sortable and Event Listeners for Existing Blocks
    $('.block-item').each(function() {
        initializeBlockEvents($(this));
    });

    // Event Listener for Post Checkboxes (for dynamically added checkboxes)
    $(document).on('change', '.block-posts input[type="checkbox"]', function() {
        updatePreview();
    });

    // Event Listener for Block Title Input Changes
    $(document).on('input', '.block-title-input', function() {
        var block = $(this).closest('.block-item');
        var newTitle = $(this).val() || newsletterData.blockLabel + ' ' + (block.data('index') + 1);
        block.find('.block-header').text(newTitle);
        updatePreview();
    });

    // Event Listener for Advertising HTML Changes
    $(document).on('input', '.advertising-block textarea', function() {
        updatePreview();
    });

    // Event Listener for Date Range Changes
    $(document).on('change', '#start_date, #end_date', function() {
        updatePreview();
    });

    // Event Listener for Template Selection Changes
    $(document).on('change', '.block-template', function() {
        updatePreview();
    });

    // Mailchimp Campaign Creation
    $('#send-to-mailchimp').click(function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to create a Mailchimp campaign with this content?')) {
            return;
        }

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json', // Ensure response is parsed as JSON
            data: {
                action: 'create_mailchimp_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                subject_line: $('#subject_line').val(),
                campaign_name: $('#campaign_name').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('Campaign created successfully! Campaign ID: ' + response.data.campaign_id);
                } else {
                    alert('Error creating campaign: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error creating campaign: ' + error);
            }
        });
    });

});
