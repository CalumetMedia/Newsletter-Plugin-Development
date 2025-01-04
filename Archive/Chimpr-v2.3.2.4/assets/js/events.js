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
                window.updateBlockIndices();
                window.updatePreview('block_reorder');
            }
        }).disableSelection();
    });
    
    // Add Block event
    $(document).off('click', '#add-block').on('click', '#add-block', function() {
        window.addBlock();
    });
    
    // Remove Block event
    $(document).off('click', '.remove-block').on('click', '.remove-block', function() {
        $(this).closest('.block-item').remove();
        window.updateBlockIndices();
        window.updatePreview('block_removed');
    });
    
    // Reset blocks event
    $(document).off('click', '#reset-blocks').on('click', '#reset-blocks', function() {
        $('.block-item').each(function() {
            var block = $(this);
            var categoryId = block.find('.block-category').val();
            var storyCount = block.find('.block-story-count').val();
            if (categoryId) {
                window.loadBlockPosts(block, categoryId, block.data('index'), 7, storyCount);
            }
        });
    });
    
    // Category/Date range and Story count changes
    $(document).off('change.newsletter', '.block-category, .block-date-range, .block-story-count')
        .on('change.newsletter', '.block-category, .block-date-range, .block-story-count', function() {
            if (window.isUpdateInProgress()) return;
            
            window.setUpdateInProgress(true);
            const $block = $(this).closest('.block-item');
            const isManual = $block.find('input[name*="[manual_override]"]').prop('checked');
            const categoryId = $block.find('.block-category').val();
            const dateRange = $block.find('.block-date-range').val() || 7;
            const blockIndex = $block.data('index');
            const storyCount = $block.find('.block-story-count').val();
    
            console.log('[Story Count] Field changed:', $(this).attr('class'), '- Block:', blockIndex, 'Category:', categoryId, 'Story Count:', storyCount);
            
            if (categoryId) {
                if (!isManual && $(this).hasClass('block-story-count')) {
                    // For story count changes in automatic mode, just update checkboxes and save
                    console.log('[Story Count] Updating checkboxes in automatic mode');
                    const $checkboxes = $block.find('.block-posts input[type="checkbox"]');
                    const numToCheck = storyCount === 'disable' ? $checkboxes.length : parseInt(storyCount);
                    
                    $checkboxes.each(function(index) {
                        const shouldCheck = index < numToCheck;
                        console.log('[Story Count] Setting checkbox', index, shouldCheck ? 'checked' : 'unchecked');
                        $(this).prop('checked', shouldCheck);
                    });
    
                    // Save state first, then update preview
                    window.saveBlockState($block)
                        .then(() => {
                            console.log('[Story Count] Block state saved, updating preview');
                            window.updatePreview('story_count_change');
                        })
                        .catch((error) => {
                            console.error('[Story Count] Error saving block state:', error);
                        })
                        .finally(() => {
                            window.setUpdateInProgress(false);
                        });
                } else {
                    // For other changes or manual mode, reload posts
                    window.loadBlockPosts($block, categoryId, blockIndex, dateRange, storyCount)
                        .then(() => {
                            setTimeout(() => {
                                window.updatePreview($(this).hasClass('block-story-count') ? 'story_count_change' : 'category_date_change');
                                window.setUpdateInProgress(false);
                            }, 250);
                        })
                        .catch((error) => {
                            console.error('[Story Count] Error loading posts:', error);
                            window.setUpdateInProgress(false);
                        });
                }
            } else if ($(this).hasClass('block-category')) {
                $block.find('.block-posts').html('<p>' + newsletterData.selectCategoryPrompt + '</p>');
                window.updatePreview('category_cleared');
                window.setUpdateInProgress(false);
            } else {
                window.setUpdateInProgress(false);
            }
        });
    
    // Post checkbox changes
    $(document).off('change.newsletter', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]')
        .on('change.newsletter', 'input[type="checkbox"][name*="[posts]"][name*="[checked]"]', function() {
            console.log('Checkbox changed!');
            if (window.isUpdateInProgress()) {
                console.log('Update in progress, skipping...');
                return;
            }
            
            console.log('Triggering preview update for checkbox change');
            window.setUpdateInProgress(true);
            setTimeout(() => {
                window.updatePreview('post_selection_change');
                window.setUpdateInProgress(false);
            }, 250);
        });
    
    // Block Type Change
    $(document).off('change.newsletter', '.block-type')
        .on('change.newsletter', '.block-type', function() {
            if (window.isUpdateInProgress()) return;
            
            var block = $(this).closest('.block-item');
            var blockType = $(this).val();
            handleBlockTypeChange(block, blockType);
            
            window.setUpdateInProgress(true);
            setTimeout(() => {
                window.updatePreview('block_type_change');
                window.setUpdateInProgress(false);
            }, 250);
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
    $(document).on('input', '#custom_header, #custom_footer, .html-block textarea, .block-title-input', function() {
        window.updatePreview('input_change');
    });
    
    $(document).on('change', '.show-title-toggle, #start_date, #end_date, .block-template', function() {
        window.updatePreview('field_change');
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
    
    // Reinitialize sortable after AJAX content updates
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url === newsletterData.ajaxUrl && 
            settings.data && 
            settings.data.indexOf('action=load_block_posts') !== -1) {
            
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
    
    // Add global AJAX event handler for preview updates
    $(document).ajaxSend(function(event, jqXHR, settings) {
        // Only handle preview-related requests
        const data = typeof settings.data === 'string' ? settings.data : JSON.stringify(settings.data);
        if (data && data.includes('generate_preview')) {
            console.log('[Preview] AJAX request started:', settings.url);
        }
    });
    
    // Set up event handlers for a block
    window.setupBlockEventHandlers = function(index) {
        var $block = $('#block-' + index);
        if (!$block.length) {
            console.error('Block not found for event handlers:', index);
            return;
        }
    
        // Story count change handler
        $block.find('.block-story-count').on('change', function() {
            var $select = $(this);
            var newCount = $select.val();
            console.log('Story count changed to:', newCount);
            
            // Save block state and update preview
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('story_count_change');
                })
                .catch((error) => {
                    console.error('Error saving story count state:', error);
                });
        });
    
        // Manual override toggle handler
        $block.find('input[name*="[manual_override]"]').on('change', function() {
            var $checkbox = $(this);
            var isManual = $checkbox.prop('checked');
            console.log('Manual override changed:', isManual);
            
            // Update story count dropdown state
            var $storyCount = $block.find('.block-story-count');
            $storyCount.prop('disabled', isManual);
            $storyCount.css('opacity', isManual ? '0.7' : '1');
            
            // Save block state and update preview
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('manual_override_change');
                })
                .catch((error) => {
                    console.error('Error saving block state:', error);
                });
        });
    
        // Post checkbox change handler
        $block.find('.block-posts').on('change', 'input[type="checkbox"]', function() {
            console.log('Checkbox changed!');
            var $checkbox = $(this);
            
            // Save block state and update preview
            window.saveBlockState(index)
                .then(() => {
                    console.log('Triggering preview update for checkbox change');
                    window.updatePreview('post_selection_change');
                })
                .catch((error) => {
                    console.error('Error saving checkbox state:', error);
                });
        });
    
        // Save button click handler
        $('#save-newsletter').on('click', function(e) {
            e.preventDefault();
            console.log('Save button clicked');
            window.saveBlocks();
        });
    
        // Category change handler
        $block.find('.block-category').on('change', function() {
            var $select = $(this);
            var category = $select.val();
            var dateRange = $block.find('.block-date-range').val();
            var storyCount = $block.find('.block-story-count').val();
            
            console.log('Category changed:', {
                category: category,
                dateRange: dateRange,
                storyCount: storyCount
            });
            
            window.loadBlockPosts($block, category, index, dateRange, storyCount)
                .then(() => {
                    return window.saveBlockState(index);
                })
                .then(() => {
                    window.updatePreview('category_change');
                })
                .catch((error) => {
                    console.error('Error handling category change:', error);
                });
        });
    
        // Date range change handler
        $block.find('.block-date-range').on('change', function() {
            var $select = $(this);
            var dateRange = $select.val();
            var category = $block.find('.block-category').val();
            var storyCount = $block.find('.block-story-count').val();
            
            console.log('Date range changed:', {
                category: category,
                dateRange: dateRange,
                storyCount: storyCount
            });
            
            window.loadBlockPosts($block, category, index, dateRange, storyCount)
                .then(() => {
                    return window.saveBlockState(index);
                })
                .then(() => {
                    window.updatePreview('date_range_change');
                })
                .catch((error) => {
                    console.error('Error handling date range change:', error);
                });
        });
    
        // Post order change handler
        $block.find('.block-posts').on('change', '.post-order', function() {
            console.log('Post order changed');
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('post_order_change');
                })
                .catch((error) => {
                    console.error('Error saving post order:', error);
                });
        });
    
        // Block title change handler
        $block.find('.block-title-input').on('change', function() {
            console.log('Block title changed');
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('block_title_change');
                })
                .catch((error) => {
                    console.error('Error saving block title:', error);
                });
        });
    
        // Show title toggle handler
        $block.find('.show-title-toggle').on('change', function() {
            console.log('Show title toggle changed');
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('show_title_change');
                })
                .catch((error) => {
                    console.error('Error saving show title state:', error);
                });
        });
    
        // Template change handler
        $block.find('.block-template').on('change', function() {
            console.log('Template changed');
            window.saveBlockState(index)
                .then(() => {
                    window.updatePreview('template_change');
                })
                .catch((error) => {
                    console.error('Error saving template change:', error);
                });
        });
    };
    
    })(jQuery);
    