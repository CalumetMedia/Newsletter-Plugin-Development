(function($) {
    let autoSaveInitialized = false;
    let isSaving = false;
    let pendingChanges = false;

    // Debounce function implementation
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Save all TinyMCE editors before collecting block data
    function saveAllEditors() {
        if (typeof tinymce !== 'undefined') {
            tinymce.editors.forEach(function(editor) {
                if (editor && !editor.isHidden()) {
                    editor.save();
                    console.log('Saved editor content for:', editor.id);
                    console.log('Content length:', editor.getContent().length);
                }
            });
        }
    }

    // Collect block data for saving
    function collectBlockData() {
        var blocks = [];
        $('.block-item').each(function() {
            var block = $(this);
            var blockIndex = block.data('index');
            var blockType = block.find('.block-type').val();
            
            // Common fields for all block types
            var blockData = {
                type: blockType,
                title: block.find('.block-title-input').val(),
                show_title: block.find('.block-show-title').prop('checked') ? 1 : 0,
                template_id: block.find('.block-template').val() || '0'
            };

            // Content block specific fields
            if (blockType === 'content') {
                blockData.category = block.find('.block-category').val() || '';
                blockData.date_range = block.find('.block-date-range').val() || '';
                blockData.story_count = block.find('.block-story-count').val() || 'disable';
                blockData.manual_override = block.find('input[name*="[manual_override]"]').prop('checked') ? 1 : 0;
                blockData.posts = {};

                block.find('.block-posts li').each(function() {
                    var $post = $(this);
                    var postId = $post.data('post-id');
                    var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
                    var $orderInput = $post.find('.post-order');
                    
                    if ($checkbox.prop('checked')) {
                        blockData.posts[postId] = {
                            checked: '1',
                            order: $orderInput.val() || '0'
                        };
                    }
                });
                console.log('Content block data collected:', blockData);
            }

            // WYSIWYG block specific fields
            else if (blockType === 'wysiwyg') {
                var editorId = 'wysiwyg-editor-' + blockIndex;
                if (tinymce.get(editorId)) {
                    blockData.wysiwyg = tinymce.get(editorId).getContent();
                    console.log('Auto-save: WYSIWYG content length for', editorId, ':', blockData.wysiwyg.length);
                } else {
                    blockData.wysiwyg = $('#' + editorId).val() || '';
                    console.log('Auto-save: Textarea content length for', editorId, ':', blockData.wysiwyg.length);
                }
            }

            // HTML block specific fields
            else if (blockType === 'html') {
                blockData.html = block.find('textarea[name="blocks[' + blockIndex + '][html]"]').val() || '';
                console.log('Auto-save: HTML content length:', blockData.html.length);
            }

            blocks.push(blockData);
        });
        return blocks;
    }

    // Auto-save function
    function autoSave() {
        console.log('Auto-save triggered');
        saveAllEditors();
        
        var blocks = collectBlockData();
        console.log('Collected blocks for auto-save:', blocks);
        
        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_newsletter_blocks',
                security: newsletterData.nonceSaveBlocks,
                newsletter_slug: newsletterData.newsletterSlug,
                blocks: blocks,
                is_auto_save: true
            },
            success: function(response) {
                if (response.success) {
                    console.log('Auto-save successful');
                } else {
                    console.error('Auto-save failed:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Auto-save error:', error);
            }
        });
    }

    // Manual save function (for save button)
    window.saveBlocks = function() {
        console.log('Manual save triggered');
        
        if (isSaving) {
            console.log('Save in progress, please wait...');
            return;
        }
        
        isSaving = true;
        
        // Save editor content first
        saveAllEditors();
        
        // Collect block data
        var blocks = collectBlockData();
        
        if (!blocks || !blocks.length) {
            console.error('No valid blocks data collected');
            isSaving = false;
            return;
        }

        // Log the blocks being sent
        console.log('Final blocks array:', JSON.stringify(blocks, null, 2));

        // Convert blocks to JSON string
        var blocksJson = JSON.stringify(blocks);
        console.log('Blocks JSON to send:', blocksJson);

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'save_newsletter_blocks');
        formData.append('security', newsletterData.nonceSaveBlocks);
        formData.append('newsletter_slug', newsletterData.newsletterSlug);
        formData.append('blocks', blocksJson);
        formData.append('is_auto_save', '0');
        formData.append('header_template', $('#header_template').val());
        formData.append('footer_template', $('#footer_template').val());
        formData.append('subject_line', $('#subject_line').val());

        // Send AJAX request
        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Raw server response:', response);
                if (response.success) {
                    console.log('Save successful');
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                    // Update preview after successful save
                    if (typeof window.updatePreview === 'function') {
                        window.updatePreview(true);  // Skip auto-save trigger
                    }
                } else {
                    console.error('Save failed:', response);
                    console.error('Response data:', response.data);
                    console.error('Blocks JSON sent:', blocksJson);
                    alert(response.data && response.data.message ? response.data.message : 'An error occurred while saving blocks.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                console.error('XHR:', xhr.responseText);
                console.error('Blocks JSON sent:', blocksJson);
                alert('An error occurred while saving blocks. Please try again.');
            },
            complete: function() {
                isSaving = false;
            }
        });
    };

    // Create debounced version of autoSave
    const debouncedAutoSave = debounce(autoSave, 1000);

    // Set up event listeners
    function initializeAutoSave() {
        if (autoSaveInitialized) return;
        autoSaveInitialized = true;

        // Listen for changes on block container and form fields
        $('#blocks-container, #header_template, #footer_template, #subject_line').on('change', function(event) {
            const target = event.target;
            if ($(target).is('.block-type, .block-title-input, .show-title-toggle, .block-template, .block-category, .block-date-range, .block-story-count, input[name*="[manual_override]"], input[name*="[checked]"], .post-order, .html-block textarea, .wysiwyg-editor-content, #header_template, #footer_template, #subject_line')) {
                debouncedAutoSave();
            }
        });

        // Set up TinyMCE change handlers
        if (window.tinyMCE) {
            window.tinyMCE.on('AddEditor', function(e) {
                e.editor.on('change keyup paste', function() {
                    e.editor.save();
                    debouncedAutoSave();
                });
            });
        }

        // Initial auto-save
        setTimeout(() => {
            autoSave();
        }, 1000);
    }

    // Initialize when document and TinyMCE are ready
    $(document).ready(function() {
        if (window.tinyMCE) {
            window.tinyMCE.on('init', function() {
                initializeAutoSave();
            });
        } else {
            initializeAutoSave();
        }
    });

})(jQuery); 