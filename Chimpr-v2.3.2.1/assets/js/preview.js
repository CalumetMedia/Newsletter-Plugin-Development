(function($) {
    // Move generatePreview to global scope
    window.generatePreview = function() {
        var blocks = [];
        $('.block-item').each(function() {
            var block = $(this);
            var blockData = {
                type: block.find('.block-type').val(),
                title: block.find('.block-title-input').val(),
                show_title: block.find('.show-title-toggle').is(':checked'),
                template_id: block.find('.block-template').val(),
                story_count: block.find('.block-story-count').val()
            };

            console.log('Collecting block data:', blockData);

            if (blockData.type === 'content') {
                blockData.category = block.find('.block-category').val();
                blockData.date_range = block.find('.block-date-range').val();
                blockData.posts = {};
                
                block.find('.post-checkbox:checked').each(function() {
                    var $post = $(this);
                    blockData.posts[$post.val()] = {
                        selected: true,
                        order: $post.closest('li').find('.post-order').val()
                    };
                });

                console.log('Content block data:', {
                    template_id: blockData.template_id,
                    category: blockData.category,
                    posts: Object.keys(blockData.posts).length + ' posts'
                });
            } else if (blockData.type === 'html' || blockData.type === 'wysiwyg') {
                blockData.content = block.find('.html-block textarea, .wysiwyg-block textarea').val();
            }
            
            blocks.push(blockData);
        });

        // Debug log
        console.log('Newsletter Data available:', {
            ajaxUrl: newsletterData.ajaxUrl,
            security: newsletterData.security,
            newsletterSlug: newsletterData.newsletterSlug
        });

        if (!newsletterData.security) {
            console.error('Security nonce is not available in newsletterData');
            return;
        }

        $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'generate_preview',
                security: newsletterData.security,
                newsletter_slug: newsletterData.newsletterSlug,
                blocks: blocks,
                custom_header: $('#custom_header').val(),
                custom_footer: $('#custom_footer').val(),
                custom_css: $('#custom_css').val()
            },
            success: function(response) {
                if (response.success) {
                    console.log('Received preview HTML:', response.data.html.substring(0, 500) + '...');
                    $('#preview-container').html(response.data.html);
                    console.log('Preview container updated');
                    // Trigger event for accordion reinitialization
                    $(document).trigger('previewUpdated');
                } else {
                    console.error('Preview generation failed:', response.data);
                    alert('Failed to generate preview: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error generating preview:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error generating preview. Check console for details.');
            }
        });
    };
})(jQuery);
    