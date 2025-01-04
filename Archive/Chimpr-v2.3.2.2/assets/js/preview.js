(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;

    window.generatePreview = function() {
        // If there's already a preview update in progress, wait for it
        if (previewUpdatePromise) {
            return previewUpdatePromise;
        }

        // Store the current state of all checkboxes and their order
        var savedState = {};
        $('.block-item').each(function() {
            var $block = $(this);
            var blockIndex = $block.data('index');
            var categoryId = $block.find('.block-category').val();
            var storyCount = $block.find('.block-story-count').val();
            
            savedState[blockIndex] = {
                storyCount: storyCount,
                category: categoryId,
                selections: {}
            };
            
            $block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
                var $checkbox = $(this);
                var postId = $checkbox.closest('li').data('post-id');
                var $orderInput = $checkbox.closest('li').find('.post-order');
                var isChecked = $checkbox.is(':checked');
                var order = $orderInput.length ? $orderInput.val() : '0';
                
                savedState[blockIndex].selections[postId] = {
                    checked: isChecked,
                    order: order
                };
            });
        });

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });
        formData.push({ name: 'saved_selections', value: JSON.stringify(savedState) });

        // Create and store the promise
        previewUpdatePromise = $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: formData
        }).done(function(response) {
            if (response.success) {
                $('#preview-content').html(response.data);
                
                // Restore state after preview loads
                $('.block-item').each(function() {
                    var $block = $(this);
                    var blockIndex = $block.data('index');
                    var state = savedState[blockIndex];
                    
                    if (state) {
                        $block.find('.block-story-count').val(state.storyCount);
                        Object.keys(state.selections).forEach(function(postId) {
                            var selection = state.selections[postId];
                            var $li = $block.find('li[data-post-id="' + postId + '"]');
                            if ($li.length) {
                                var $checkbox = $li.find('input[type="checkbox"][name*="[selected]"]');
                                var $orderInput = $li.find('.post-order');
                                
                                if ($checkbox.length) {
                                    $checkbox.prop('checked', selection.checked);
                                }
                                if ($orderInput.length) {
                                    $orderInput.val(selection.order);
                                }
                            }
                        });
                    }
                });
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Error updating preview:', textStatus, errorThrown);
        }).always(function() {
            previewUpdatePromise = null;
        });

        return previewUpdatePromise;
    };

    // Debounced updatePreview function
    window.updatePreview = function(source) {
        // If there's already a preview update in progress, queue this update
        if (previewUpdatePromise) {
            if (window.updatePreviewTimeout) {
                clearTimeout(window.updatePreviewTimeout);
            }
            
            window.updatePreviewTimeout = setTimeout(function() {
                generatePreview();
            }, 500); // Increased delay for queued updates
            return;
        }
        
        // Clear any existing timeout
        if (window.updatePreviewTimeout) {
            clearTimeout(window.updatePreviewTimeout);
        }
        
        // Set a new timeout
        window.updatePreviewTimeout = setTimeout(function() {
            generatePreview().then(function() {
                // Check if there's a queued update that needs to be processed
                if (window.updatePreviewTimeout) {
                    // Another update is queued
                }
            });
        }, 250);
    };

})(jQuery);