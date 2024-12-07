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
                var currentIndex = block.data('index');

                if (blockType === 'content' && categoryId) {
                    loadBlockPosts(block, categoryId, currentIndex);
                }
            });
        }
    });

    // Accordion functionality
    $(document).off('click', '.block-accordion-toggle').on('click', '.block-accordion-toggle', function(e) {
        e.stopPropagation();
        var $content = $(this).closest('.block-item').find('.block-content');
        $content.slideToggle();
        $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    // Sortable initialization
    $("#blocks-container").sortable({
        handle: '.block-drag-handle',
        items: '> .block-item',
        axis: 'y',
        opacity: 0.7,
        update: function() {
            updateBlockIndices();
            updatePreview();
        }
    }).disableSelection();

    function initializeSortable(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.story-drag-handle',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
                }
            });
        }
    }

function initializeBlockEvents(block) {
    initializeSortable(block);
    block.find('input[type="checkbox"]').on('change', updatePreview);
    block.find('.block-template').on('change', updatePreview);
    
    // Add new date range handlers
    block.find('.today-button').on('click', function() {
        block.find('.block-end-date').val(new Date().toISOString().split('T')[0]);
        updateBlockDates(block);
        var categoryId = block.find('.block-category').val();
        if (categoryId) {
            loadBlockPosts(block, categoryId, block.data('index'));
        }
    });

    block.find('.block-end-date, .block-date-range-select').on('change', function() {
        updateBlockDates(block);
        var categoryId = block.find('.block-category').val();
        if (categoryId) {
            loadBlockPosts(block, categoryId, block.data('index'));
        }
    });

    // Initialize dates for this block
    updateBlockDates(block);
}

    // Add Block
    $('#add-block').off('click').on('click', function() {
        var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
        blockHtml += '<h3 class="block-header">';
        blockHtml += '<span class="dashicons dashicons-sort block-drag-handle"></span>';
        blockHtml += '<span class="block-title">' + newsletterData.blockLabel + ' ' + (blockIndex + 1) + '</span>';
        blockHtml += '<span class="dashicons dashicons-arrow-down-alt2 block-accordion-toggle"></span>';
        blockHtml += '</h3>';
        blockHtml += '<div class="block-content">';
        blockHtml += '<div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">';
        blockHtml += '<div style="width: 25%;"><label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" class="block-title-input" /></div>';
        blockHtml += '<div style="margin-left: 15px;"><label><input type="checkbox" name="blocks[' + blockIndex + '][show_title]" class="show-title-toggle" value="1" checked>Show Title in Preview</label></div>';
        blockHtml += '</div>';

        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="html">HTML</option>';
        blockHtml += '</select>';

        blockHtml += '<div class="template-select">';
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
        blockHtml += '</select></div>';

        blockHtml += '<div class="content-block">';
        blockHtml += '<label>' + newsletterData.selectCategoryLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][category]" class="block-category">';
        blockHtml += '<option value="">' + newsletterData.selectCategoryOption + '</option>';
        $.each(newsletterData.categories, function(index, category) {
            blockHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });
        blockHtml += '</select>';

blockHtml += '<div class="block-date-range">';
blockHtml += '<div style="display: flex; align-items: center; gap: 15px; margin: 10px 0;">';
blockHtml += '<button type="button" class="button today-button" style="min-width: 80px;">TODAY</button>';
blockHtml += '<div><label>End Date:</label>';
blockHtml += '<input type="date" class="block-end-date" name="blocks[' + blockIndex + '][end_date]" ';
blockHtml += 'value="' + new Date().toISOString().split('T')[0] + '" style="width: 150px;" /></div>';
blockHtml += '<div><label>Range:</label>';
blockHtml += '<select class="block-date-range-select" name="blocks[' + blockIndex + '][date_range]" style="width: 150px;">';
blockHtml += '<option value="1">1 Day Prior</option>';
blockHtml += '<option value="3">3 Days Prior</option>';
blockHtml += '<option value="5">5 Days Prior</option>';
blockHtml += '<option value="7" selected>7 Days Prior</option>';
blockHtml += '<option value="14">14 Days Prior</option>';
blockHtml += '<option value="30">30 Days Prior</option>';
blockHtml += '<option value="90">90 Days Prior</option>';
blockHtml += '</select></div>';
blockHtml += '<span class="block-start-date-display" style="color: #666;">[<span class="date"></span>]</span>';
blockHtml += '<input type="hidden" class="block-start-date" name="blocks[' + blockIndex + '][start_date]">';
blockHtml += '</div></div>';




        blockHtml += '<div class="block-posts"></div></div>';

        blockHtml += '<div class="html-block" style="display:none;">';
        blockHtml += '<label>HTML</label>';
        blockHtml += '<textarea name="blocks[' + blockIndex + '][html]" rows="5" style="width:100%;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<button type="button" class="button remove-block">' + newsletterData.removeBlockLabel + '</button>';
        blockHtml += '</div></div>';

        $('#blocks-container').append(blockHtml);
        blockIndex++;

        var newBlock = $('#blocks-container .block-item').last();
        initializeBlockEvents(newBlock);
        updatePreview();
    });

    // Remove Block
    $(document).off('click', '.remove-block').on('click', '.remove-block', function() {
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
        // Add date range field updates
        $(this).find('.block-end-date').attr('name', 'blocks[' + index + '][end_date]');
        $(this).find('.block-date-range-select').attr('name', 'blocks[' + index + '][date_range]');
        $(this).find('.block-start-date').attr('name', 'blocks[' + index + '][start_date]');
        // Rest of updates
        $(this).find('.block-posts input[type="checkbox"]').each(function() {
            var postId = $(this).closest('li').data('post-id');
            $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][selected]');
        });
        $(this).find('.block-posts .post-order').each(function() {
            var postId = $(this).closest('li').data('post-id');
            $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][order]');
        });
        $(this).find('.html-block textarea').attr('name', 'blocks[' + index + '][html]');
    });
    blockIndex = $('#blocks-container .block-item').length;
}

function updateBlockDates(block) {
    var days = parseInt(block.find('.block-date-range-select').val());
    var endDate = new Date(block.find('.block-end-date').val());
    
    // Calculate start date by subtracting days from end date
    var startDate = new Date(endDate);
    startDate.setDate(startDate.getDate() - days);
    
    var formattedStart = startDate.toISOString().split('T')[0];
    var formattedEnd = endDate.toISOString().split('T')[0];
    
    console.log('Date Range:', {
        days: days,
        startDate: formattedStart,
        endDate: formattedEnd
    });

    block.find('.block-start-date').val(formattedStart);
    block.find('.block-start-date-display .date').text(formattedStart);
    
    return {
        startDate: formattedStart,
        endDate: formattedEnd
    };
}

    // Handle Block Type Change
    $(document).off('change', '.block-type').on('change', '.block-type', function() {
        var block = $(this).closest('.block-item');
        var blockType = $(this).val();

        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.html-block').hide();
            block.find('.template-select').show();
        } else if (blockType === 'html') {
            block.find('.content-block').hide();
            block.find('.html-block').show();
            block.find('.template-select').hide();
        }
        updatePreview();
    });

    // Test email dialog handling
    $('#send-test-email').off('click').on('click', function(e) {
        e.preventDefault();
        $('#email-input-step').show();
        $('#success-step').hide();
        $('#test-email').val('');
        $('#test-email-dialog').fadeIn(200);
    });

    $('#cancel-test, #close-success, .dialog-overlay').off('click').on('click', function(e) {
        if (e.target === this) {
            $('#test-email-dialog').fadeOut(200);
        }
    });

    // Send Test Email
    $('#send-test').off('click').on('click', function() {
        const testEmail = $('#test-email').val().trim();
        if (!testEmail) {
            alert('Please enter an email address');
            return;
        }

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'send_test_email',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Test email sent successfully!');
                    $('#test-email').val('');
                } else {
                    $('#email-input-step').hide();
                    $('#success-step').show().find('p').text('Error sending test email: ' + response.data);
                }
            },
            error: function() {
                $('#email-input-step').hide();
                $('#success-step').show().find('p').text('Error connecting to server');
            }
        });
    });

    // Load Posts when Category Changes
    $(document).off('change', '.block-category').on('change', '.block-category', function() {
        var block = $(this).closest('.block-item');
        var categoryId = $(this).val();
        var currentIndex = block.data('index');

        if (categoryId) {
            loadBlockPosts(block, categoryId, currentIndex);
        } else {
            block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
            updatePreview();
        }
    });

function loadBlockPosts(block, categoryId, currentIndex) {
    var dates = updateBlockDates(block);
    console.log('Loading posts with:', {
        categoryId: categoryId,
        startDate: dates.startDate,
        endDate: dates.endDate
    });

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'load_block_posts',
            security: newsletterData.nonceLoadPosts,
            category_id: categoryId,
            block_index: currentIndex,
            newsletter_slug: newsletterData.newsletterSlug,
            start_date: dates.startDate,
            end_date: dates.endDate
        },
        success: function(response) {
            console.log('API Response:', response);
            if (response.success) {
                block.find('.block-posts').html(response.data);
                initializeBlockUI(block);
            }
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
                    updatePreview();
                }
            });
        }
        block.find('input[type="checkbox"]').on('change', updatePreview);
    }

    // Save Blocks
    $('#save-blocks').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'save_newsletter_blocks' });
        formData.push({ name: 'security', value: newsletterData.nonceSaveBlocks });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
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
            dataType: 'json',
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
                alert('Error saving blocks: ' + error);
            }
        });
    });

    // Tab switching functionality
    $('.nav-tab').off('click').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#' + $(this).data('tab')).show();
        localStorage.setItem('activeNewsletterTab', $(this).data('tab'));
    });

    var activeTab = localStorage.getItem('activeNewsletterTab');
    if (activeTab) {
        $('.nav-tab[data-tab="' + activeTab + '"]').click();
    }

    // Preview updates
    $(document).on('input', '#custom_header, #custom_footer, .html-block textarea, .block-title-input', updatePreview);
    $(document).on('change', '.show-title-toggle, #start_date, #end_date, .block-template', updatePreview);

    function updatePreview() {
        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#preview-content').html(response.data);
                } else {
                    $('#preview-content').html('<p>' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#preview-content').html('<p>Error generating preview: ' + error + '</p>');
            }
        });
    }

    // Mailchimp Campaign Creation
    $('#send-to-mailchimp').off('click').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to create a Mailchimp campaign with this content?')) {
            return;
        }

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
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
                alert('Error connecting to server: ' + error);
            }
        });
    });

    // Initialize existing blocks
    $('.block-item').each(function() {
        initializeBlockEvents($(this));
    });

    // Initial preview
    updatePreview();

    // Schedule Controls
    function toggleScheduleControls() {
        const isChecked = $('#use_manual_schedule').is(':checked');
        $('#manual_schedule_controls').toggle(isChecked);
        $('.schedule-button').toggle(isChecked);
    }

    // On page load
    toggleScheduleControls();

    // Checkbox changes
    $('#use_manual_schedule').off('change').on('change', function() {
        toggleScheduleControls();
        if (this.checked) {
            if (!$('#manual_schedule_date').val() || !$('#manual_schedule_time').val()) {
                const now = new Date();
                $('#manual_schedule_date').val(now.toISOString().split('T')[0]);
                $('#manual_schedule_time').val(now.toTimeString().slice(0,5));
            }
            saveScheduleSettings(true);
        } else {
            saveScheduleSettings(false);
        }
    });

    // Save schedule settings when date/time changes
    $('#manual_schedule_date, #manual_schedule_time').off('change').on('change', function() {
        if ($('#use_manual_schedule').is(':checked')) {
            saveScheduleSettings(true);
        }
    });

    function saveScheduleSettings(isChecked) {
        const data = {
            action: 'save_newsletter_schedule',
            security: newsletterData.nonceMailchimp,
            newsletter_slug: newsletterData.newsletterSlug,
            use_manual_schedule: isChecked ? '1' : '0',
            manual_schedule_date: $('#manual_schedule_date').val(),
            manual_schedule_time: $('#manual_schedule_time').val()
        };

        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    console.log('Schedule settings saved');
                } else {
                    console.error('Error saving schedule settings:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving schedule settings:', error);
            }
        });
    }

    // Schedule Campaign
    $('#schedule-campaign').off('click').on('click', function() {
        var dateVal = $('#manual_schedule_date').val();
        var timeVal = $('#manual_schedule_time').val();

        if (!dateVal || !timeVal) {
            alert('Please select a valid date and time for scheduling.');
            return;
        }

        var scheduleDateTime = dateVal + 'T' + timeVal + ':00';

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'create_and_schedule_campaign',
                security: newsletterData.nonceMailchimp,
                newsletter_slug: newsletterData.newsletterSlug,
                schedule_datetime: scheduleDateTime
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + (response.data ? response.data : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while attempting to create and schedule the campaign: ' + error);
            }
        });
    });

    // Send Now functionality
    var sendNowButtonClickedOnce = false;
    $('#send-now').off('click').on('click', function() {
        console.log('SEND NOW clicked.');
        if (!sendNowButtonClickedOnce) {
            console.log('Showing first confirmation prompt.');
            var firstConfirm = confirm("Are you sure you want to SEND NOW to your Mailchimp list?");
            if (firstConfirm) {
                console.log('First confirmation passed. Prompting user to click again.');
                sendNowButtonClickedOnce = true;
                alert("Click SEND NOW again to confirm sending.");
                $('#send-now').addClass('send-now-confirmed');
            } else {
                console.log('User canceled on first confirmation.');
            }
        } else {
            console.log('Second confirmation attempt.');
            var secondConfirm = confirm("Double Checking: Are you absolutely sure you want to SEND NOW?");
            if (secondConfirm) {
                console.log('Second confirmation passed. Sending AJAX request now.');
                $.ajax({
                    url: newsletterData.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'send_now_campaign',
                        security: newsletterData.nonceMailchimp,
                        newsletter_slug: newsletterData.newsletterSlug
                    },
                    success: function(response) {
                        console.log('AJAX success callback fired.', response);
                        if (response.success) {
                            alert("Campaign has been sent successfully.");
                            location.reload();
                        } else {
                            alert("Error sending campaign: " + (response.data ? response.data : 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error callback fired.', error);
                        alert("Error sending campaign: " + error);
                    }
                });
            } else {
                console.log('User canceled on second confirmation.');
                sendNowButtonClickedOnce = false;
                $('#send-now').removeClass('send-now-confirmed');
            }
        }
    });

}); // End of document.ready