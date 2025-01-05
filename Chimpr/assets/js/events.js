(function($) {

    /**
     * Accordion initialization on document ready
     */
    $(document).ready(function() {
        // Initialize jQuery UI Accordion on the blocks container
        $("#blocks-container").accordion({
            header: ".block-header",
            collapsible: true,
            active: false,
            heightStyle: "content"
        });
    });

    /**
     * Sortable for the entire #blocks-container (each .block-item)
     * This is a higher-level container sort, not the internal post sorting.
     */
    $(function() {
        $("#blocks-container").sortable({
            handle: '.block-drag-handle',
            items: '> .block-item',
            axis: 'y',
            opacity: 0.7,
            start: function(event, ui) {
                // Temporarily remove any WYSIWYG editors before sorting
                var $editor = ui.item.find('.wysiwyg-editor-content');
                if ($editor.length) {
                    var editorId = $editor.attr('id');
                    if (tinymce.get(editorId)) {
                        ui.item.data('editor-content', tinymce.get(editorId).getContent());
                        tinymce.execCommand('mceRemoveEditor', true, editorId);
                    }
                }
            },
            update: function(event, ui) {
                // Reindex blocks after sorting
                updateBlockIndices();
                // Optionally refresh preview
                updatePreview();
            }
        }).disableSelection();
    });

    /**
     * Add a new block (global button).
     * The actual addBlock() function is typically in block-manager.js.
     */
    $(document).off('click', '#add-block').on('click', '#add-block', function() {
        addBlock();
    });

    /**
     * Remove a block (global event).
     * Make sure your blocks have a `.remove-block` or `.remove-button` class
     * if you want to handle it here, rather than in block-manager.js.
     */
    $(document).off('click', '.remove-block').on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        updateBlockIndices();
        updatePreview();
    });

    /**
     * Reset blocks event (clears WYSIWYG content, toggles off override, etc.)
     */
    $(document).off('click', '#reset-blocks').on('click', '#reset-blocks', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $form = $('#blocks-form');
        var $submitButton = $(this);
        $submitButton.prop('disabled', true);

        $form.find('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var blockType = $block.find('.block-type').val();

            // Clear WYSIWYG content
            if (blockType === 'wysiwyg') {
                var editorId = 'wysiwyg-editor-' + blockIndex;
                if (tinymce.get(editorId)) {
                    tinymce.get(editorId).setContent('');
                    tinymce.get(editorId).save();
                }
                $block.find('textarea[name="blocks[' + blockIndex + '][wysiwyg]"]').val('');
                $block.find('input[name="blocks[' + blockIndex + '][title]"]').val('');
                $block.find('input[name="blocks[' + blockIndex + '][show_title]"]')
                    .prop('checked', false)
                    .trigger('change');
            }

            // Turn off manual override on content blocks
            if (blockType === 'content') {
                $block.find('input[name="blocks[' + blockIndex + '][manual_override]"]')
                    .prop('checked', false)
                    .trigger('change');
            }
        });

        // Build form data AFTER clearing
        var formData = new FormData($form[0]);
        formData.append('reset_blocks', '1');

        // Send via AJAX
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                alert('WYSIWYG content cleared, show-title off, content manual override off. Refresh to confirm.');
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });

    /**
     * (REMOVED block-story-count, manual-override-toggle, block-category, block-date-range, .block-type events)
     * because these events are already handled in block-manager.js (setupBlockEventHandlers).
     */

    /**
     * Test email dialog
     */
    $(document).off('click', '#send-test-email').on('click', '#send-test-email', function(e) {
        e.preventDefault();
        $('#email-input-step').show();
        $('#success-step').hide();
        $('#test-email').val('');
        $('#test-email-dialog').fadeIn(200);
    });

    // Close the test-email dialog
    $(document).off('click', '#cancel-test, #close-success, .dialog-overlay')
        .on('click', '#cancel-test, #close-success, .dialog-overlay', function(e) {
            if (e.target === this) {
                $('#test-email-dialog').fadeOut(200);
            }
        });

    // Actually send the test email
    $(document).off('click', '#send-test').on('click', '#send-test', function() {
        const testEmail = $('#test-email').val().trim();
        if (!testEmail) {
            alert('Please enter an email address');
            return;
        }
        sendTestEmail(testEmail);
    });

    /**
     * Save Blocks (global button) - triggers save logic
     */
    $(document).off('click', '#save-blocks').on('click', '#save-blocks', function(e) {
        e.preventDefault();
        saveBlocks();
    });

    /**
     * Tab switching (navigation)
     */
    $(document).off('click', '.nav-tab').on('click', '.nav-tab', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#' + $(this).data('tab')).show();
        localStorage.setItem('activeNewsletterTab', $(this).data('tab'));
    });

    // Update preview on certain form inputs
    $(document).on('input', '#custom_header, #custom_footer, .html-block textarea, .block-title-input', updatePreview);
    $(document).on('change', '.show-title-toggle, #start_date, #end_date, .block-template', updatePreview);

    /**
     * Mailchimp flow: create campaign, schedule, etc.
     */
    $(document).off('click', '#send-to-mailchimp').on('click', '#send-to-mailchimp', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to create a Mailchimp campaign with this content?')) {
            return;
        }
        createMailchimpCampaign($('#subject_line').val(), $('#campaign_name').val());
    });

    /**
     * Manual schedule: checkbox & date/time
     */
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

    // Save schedule when date/time changes
    $(document).off('change', '#manual_schedule_date, #manual_schedule_time')
        .on('change', '#manual_schedule_date, #manual_schedule_time', function() {
            if ($('#use_manual_schedule').is(':checked')) {
                saveScheduleSettings(true);
            }
        });

    // Schedule campaign
    $(document).off('click', '#schedule-campaign').on('click', '#schedule-campaign', function() {
        var now = new Date();
        var minutes = now.getMinutes();
        var roundedMinutes = Math.ceil((minutes + 10) / 15) * 15;
        now.setMinutes(roundedMinutes);
        now.setSeconds(0);

        var tzoffset = now.getTimezoneOffset() * 60000;
        var localISOTime = (new Date(now - tzoffset)).toISOString().slice(0, -8);

        if (!confirm("Are you sure you want to schedule the campaign?")) {
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

    /**
     * After certain AJAX calls (like load_block_posts), re-initialize sorting on the newly updated blocks
     */
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (
            settings.url === newsletterData.ajaxUrl &&
            (
                (typeof settings.data === 'string' && settings.data.indexOf('action=load_block_posts') !== -1) ||
                (settings.data instanceof FormData && settings.data.get('action') === 'load_block_posts')
            )
        ) {
            var $block = $('.block-item').filter(function() {
                return $(this).find('.block-posts.loading').length > 0;
            });

            if ($block.length) {
                setTimeout(function() {
                    initializeSortable($block);
                }, 100);
            }
        }
    });

})(jQuery);
