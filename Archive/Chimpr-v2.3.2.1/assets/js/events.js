(function($) {

// Initialize accordion
$(document).ready(function() {
    // First destroy any existing accordion
    if ($("#blocks-container").data('ui-accordion')) {
        $("#blocks-container").accordion('destroy');
    }

    // Initialize accordion
    $(".block-header").on('click', function(e) {
        e.preventDefault();
        var $content = $(this).closest('.block-item').find('.block-content');
        $('.block-content').not($content).slideUp();
        $content.slideToggle();
    });
});

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

// Add handlers for template, story count, and post selection changes
$(document).off('change.newsletter', '.block-template, .block-story-count, .post-checkbox')
    .on('change.newsletter', '.block-template, .block-story-count, .post-checkbox', function() {
        updatePreview();
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

// Save Blocks
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

})(jQuery);
    