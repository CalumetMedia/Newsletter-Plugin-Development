(function($) {

$(document).ready(function() {
    // Initialize jQuery UI Accordion on the blocks container
    $("#blocks-container").accordion({
        header: ".block-header",
        collapsible: true,
        active: false,
        heightStyle: "content"
    });
});

// Remove the previous slideToggle handler for block-accordion-toggle
// because the accordion widget will handle expand/collapse.

// Sortable initialization for main container
$(function() {
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
});

// Add Block event
$(document).off('click', '#add-block').on('click', '#add-block', function() {
    addBlock();
});

// Remove Block event
$(document).off('click', '.remove-block').on('click', '.remove-block', function() {
    $(this).closest('.block-item').remove();
    updateBlockIndices();
    updatePreview();
});

// Reset blocks event
$(document).off('click', '#reset-blocks').on('click', '#reset-blocks', function() {
    $('.block-item').each(function() {
        var block = $(this);
        var categoryId = block.find('.block-category').val();
        if (categoryId) {
            loadBlockPosts(block, categoryId, block.data('index'));
        }
    });
});

// Category/Date range change
$(document).off('change.newsletter', '.block-category, .block-date-range-select, .block-end-date')
    .on('change.newsletter', '.block-category, .block-date-range-select, .block-end-date', function() {
        var block = $(this).closest('.block-item');
        var categoryId = block.find('.block-category').val();
        if (categoryId) {
            loadBlockPosts(block, categoryId, block.data('index'));
        } else if ($(this).hasClass('block-category')) {
            block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
            updatePreview();
        }
    });

// Test email dialog
$(document).off('click', '#send-test-email').on('click', '#send-test-email', function(e) {
    e.preventDefault();
    $('#email-input-step').show();
    $('#success-step').hide();
    $('#test-email').val('');
    $('#test-email-dialog').fadeIn(200);
});

// Close test dialog
$(document).off('click', '#cancel-test, #close-success, .dialog-overlay')
    .on('click', '#cancel-test, #close-success, .dialog-overlay', function(e) {
        if (e.target === this) {
            $('#test-email-dialog').fadeOut(200);
        }
    });

// Send Test Email
$(document).off('click', '#send-test').on('click', '#send-test', function() {
    const testEmail = $('#test-email').val().trim();
    if (!testEmail) {
        alert('Please enter an email address');
        return;
    }
    sendTestEmail(testEmail);
});

// Save Blocks - allow normal form submission to trigger the redirect
$(document).off('click', '#save-blocks').on('click', '#save-blocks', function(e) {
    console.log('Save button clicked');
    e.preventDefault();
    saveBlocks();
});

// Tab switching
$(document).off('click', '.nav-tab').on('click', '.nav-tab', function(e) {
    e.preventDefault();
    $('.nav-tab').removeClass('nav-tab-active');
    $(this).addClass('nav-tab-active');
    $('.tab-content').hide();
    $('#' + $(this).data('tab')).show();
    localStorage.setItem('activeNewsletterTab', $(this).data('tab'));
});

// Preview updates on input
$(document).on('input', '#custom_header, #custom_footer, .html-block textarea, .block-title-input', updatePreview);
$(document).on('change', '.show-title-toggle, #start_date, #end_date, .block-template', updatePreview);

// Block Type Change
$(document).off('change', '.block-type').on('change', '.block-type', function() {
    var block = $(this).closest('.block-item');
    var blockType = $(this).val();
    handleBlockTypeChange(block, blockType);
});

// Send to Mailchimp (create campaign)
$(document).off('click', '#send-to-mailchimp').on('click', '#send-to-mailchimp', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to create a Mailchimp campaign with this content?')) {
        return;
    }
    createMailchimpCampaign($('#subject_line').val(), $('#campaign_name').val());
});

// Manual schedule checkbox
$(document).off('change', '#use_manual_schedule').on('change', '#use_manual_schedule', function() {
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
$(document).off('change', '#manual_schedule_date, #manual_schedule_time')
    .on('change', '#manual_schedule_date, #manual_schedule_time', function() {
        if ($('#use_manual_schedule').is(':checked')) {
            saveScheduleSettings(true);
        }
    });

// Schedule Campaign
$(document).off('click', '#schedule-campaign').on('click', '#schedule-campaign', function() {
    var now = new Date();
    var minutes = now.getMinutes();
    var roundedMinutes = Math.ceil((minutes + 10) / 15) * 15;
    now.setMinutes(roundedMinutes);
    now.setSeconds(0);

    var tzoffset = now.getTimezoneOffset() * 60000;
    var localISOTime = (new Date(now - tzoffset)).toISOString().slice(0, -8);

    var confirmSchedule = confirm("Are you sure you want to schedule the campaign?");
    if (!confirmSchedule) {
        return;
    }

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        data: {
            action: 'schedule_mailchimp_campaign',
            security: newsletterData.nonceMailchimp,
            newsletter_slug: newsletterData.newsletterSlug,
            schedule_datetime: localISOTime
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert("Error scheduling campaign: " + (response.data ? response.data : 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert("Ajax error scheduling campaign: " + error);
        }
    });
});

})(jQuery);
