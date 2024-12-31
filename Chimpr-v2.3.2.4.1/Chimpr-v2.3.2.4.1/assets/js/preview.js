(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;
    let previewInitialized = false;

    // Initialize state if not already done
    if (!window.newsletterState) {
        window.newsletterState = {
            blocksLoaded: 0,
            totalBlocks: 0,
            postsData: {},
            isReady: false,
            isUpdateInProgress: false
        };
    }

    window.updatePreview = function(trigger = 'manual') {
        // Only update if all data is loaded or if it's a manual update
        if (window.newsletterState && !window.newsletterState.isReady && trigger !== 'manual') {
            console.log('Skipping preview update - not ready');
            return;
        }

        // Cancel any existing preview update
        if (previewUpdatePromise && previewUpdatePromise.abort) {
            previewUpdatePromise.abort();
        }

        const $previewContent = $('#preview-content');
        $previewContent.addClass('loading');

        // Get current blocks state
        const blocks = [];
        $('#blocks-container .block-item').each(function() {
            const $block = $(this);
            const blockData = {
                type: $block.find('.block-type').val(),
                title: $block.find('.block-title-input').val(),
                show_title: $block.find('.show-title-toggle').prop('checked'),
                template_id: $block.find('.block-template').val(),
                category: $block.find('.block-category').val(),
                date_range: $block.find('.block-date-range').val(),
                story_count: $block.find('.block-story-count').val(),
                manual_override: $block.find('input[name*="[manual_override]"]').prop('checked'),
                posts: {}
            };

            // Get selected posts
            $block.find('.sortable-posts input[type="checkbox"]:checked').each(function() {
                const $checkbox = $(this);
                const postId = $checkbox.closest('li').data('post-id');
                const order = $checkbox.closest('li').find('.post-order').val();
                blockData.posts[postId] = {
                    checked: '1',
                    order: order
                };
            });

            if (blockData.type === 'html') {
                blockData.html = $block.find('textarea[name*="[html]"]').val();
            } else if (blockData.type === 'wysiwyg') {
                const editorId = $block.find('.wysiwyg-editor-content').attr('id');
                if (tinymce.get(editorId)) {
                    tinymce.get(editorId).save(); // Save content to textarea
                    blockData.wysiwyg = tinymce.get(editorId).getContent();
                } else {
                    blockData.wysiwyg = $block.find('.wysiwyg-editor-content').val();
                }
            }

            blocks.push(blockData);
        });

        // Generate preview
        previewUpdatePromise = $.ajax({
            url: newsletterData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'generate_preview',
                security: newsletterData.nonceGeneratePreview,
                newsletter_slug: newsletterData.newsletterSlug,
                saved_selections: JSON.stringify(blocks)
            },
            success: function(response) {
                if (response.success) {
                    $previewContent.html(response.data);
                } else {
                    console.error('Preview generation failed:', response.data);
                    $previewContent.html('<p class="error">Error generating preview</p>');
                }
            },
            error: function(xhr, status, error) {
                if (status !== 'abort') {
                    console.error('Ajax error:', error);
                    $previewContent.html('<p class="error">Error generating preview</p>');
                }
            },
            complete: function() {
                $previewContent.removeClass('loading');
                previewUpdatePromise = null;
            }
        });
    };

    // Initialize preview functionality
    function initializePreview() {
        if (previewInitialized) return;
        previewInitialized = true;

        // Initial preview update
        updatePreview('initial_load');

        // Set up direct preview updates for certain changes
        $('#blocks-container').on('change', '.block-type, .block-template', function() {
            if (window.newsletterState.isReady) {
                updatePreview('type_template_change');
            }
        });

        // Handle WYSIWYG editor changes
        $('#blocks-container').on('change keyup paste input', '.wysiwyg-editor-content', function() {
            if (window.newsletterState.isReady) {
                const editorId = $(this).attr('id');
                if (tinymce.get(editorId)) {
                    tinymce.get(editorId).save();
                }
                updatePreview('wysiwyg_content_change');
            }
        });
    }

    // Initialize when document and TinyMCE are ready
    $(document).ready(function() {
        if (window.tinyMCE) {
            window.tinyMCE.on('init', function() {
                initializePreview();
            });
        } else {
            initializePreview();
        }
    });

})(jQuery);