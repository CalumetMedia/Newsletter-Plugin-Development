(function($) {

window.generatePreview = function() {
    // Store the current state of all checkboxes and their order
    var savedState = {};
    $('.block-item').each(function() {
        var $block = $(this);
        var blockIndex = $block.data('index');
        savedState[blockIndex] = {
            storyCount: $block.find('.block-story-count').val(),
            selections: {}
        };
        
        // Store checkbox states
        $block.find('input[type="checkbox"][name*="[posts]"][name*="[selected]"]').each(function() {
            var $checkbox = $(this);
            var postId = $checkbox.closest('li').data('post-id');
            var $orderInput = $checkbox.closest('li').find('.post-order');
            savedState[blockIndex].selections[postId] = {
                checked: $checkbox.is(':checked'),
                order: $orderInput.length ? $orderInput.val() : '0'
            };
        });
    });

    console.log('Stored state before preview:', savedState);

    var formData = $('#blocks-form').serializeArray();
    formData.push({ name: 'action', value: 'generate_preview' });
    formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
    formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });
    formData.push({ name: 'saved_selections', value: JSON.stringify(savedState) });

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#preview-content').html(response.data);
                
                // Restore state after a short delay to ensure the preview has loaded
                setTimeout(function() {
                    $('.block-item').each(function() {
                        var $block = $(this);
                        var blockIndex = $block.data('index');
                        var state = savedState[blockIndex];
                        
                        if (state) {
                            // Restore story count
                            $block.find('.block-story-count').val(state.storyCount);
                            
                            // Restore checkbox states and orders
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
                    console.log('Restored state after preview:', savedState);
                }, 100);
            } else {
                $('#preview-content').html('<p>' + response.data + '</p>');
            }
        },
        error: function(xhr, status, error) {
            $('#preview-content').html('<p>Error generating preview: ' + error + '</p>');
        }
    });
};

// Single updatePreview function that all files will use
window.updatePreview = function() {
    // Prevent multiple rapid updates
    if (window.updatePreviewTimeout) {
        clearTimeout(window.updatePreviewTimeout);
    }
    
    window.updatePreviewTimeout = setTimeout(function() {
        generatePreview();
    }, 100);
};

})(jQuery);
