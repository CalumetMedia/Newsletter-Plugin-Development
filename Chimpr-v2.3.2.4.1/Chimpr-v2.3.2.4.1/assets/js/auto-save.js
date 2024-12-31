(function($) {
    let autoSaveInitialized = false;

    // Debounce function implementation
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Save all TinyMCE editors
    function saveAllEditors() {
        if (window.tinyMCE) {
            window.tinyMCE.editors.forEach(function(editor) {
                if (editor && !editor.isHidden() && editor.initialized) {
                    try {
                        editor.save();
                        console.log('Saved editor:', editor.id);
                    } catch (e) {
                        console.error('Error saving editor:', editor.id, e);
                    }
                }
            });
        }
    }

    // Collect block data for saving
    function collectBlockData() {
        const blocks = [];
        
        $('#blocks-container .block-item').each(function(index) {
            var $block = $(this);
            var blockType = $block.find('.block-type').val();
            
            var blockData = {
                type: blockType,
                title: $block.find('.block-title-input').val() || '',
                show_title: $block.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $block.find('.block-template').val() || 'default',
                category: $block.find('.block-category').val() || '',
                date_range: $block.find('.block-date-range').val() || '',
                story_count: $block.find('.block-story-count').val() || 'disable',
                manual_override: $block.find('input[name*="[manual_override]"]').prop('checked') ? 1 : 0,
                posts: {}
            };

            // Handle content blocks
            if (blockType === 'content') {
                $block.find('.block-posts li').each(function() {
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
            }

            // Handle HTML blocks
            if (blockType === 'html') {
                blockData.html = $block.find('.html-block textarea').val() || '';
            }

            // Handle WYSIWYG blocks
            if (blockType === 'wysiwyg') {
                var editorId = $block.find('.wysiwyg-editor-content').attr('id');
                if (window.tinyMCE && window.tinyMCE.get(editorId)) {
                    window.tinyMCE.get(editorId).save();
                    blockData.wysiwyg = window.tinyMCE.get(editorId).getContent();
                    if (!blockData.wysiwyg || blockData.wysiwyg === '<p></p>' || blockData.wysiwyg.trim() === '') {
                        console.log('Skipping empty WYSIWYG block');
                        return;
                    }
                } else {
                    blockData.wysiwyg = $block.find('.wysiwyg-editor-content').val() || '';
                    if (!blockData.wysiwyg.trim()) {
                        console.log('Skipping empty WYSIWYG block (fallback)');
                        return;
                    }
                }
            }

            blocks.push(blockData);
        });

        return blocks;
    }

    // Auto-save function
    function autoSave() {
        console.log('Auto-save triggered');
        
        // Save editor content first
        saveAllEditors();
        
        // Collect block data
        var blocks = collectBlockData();
        if (!blocks || !blocks.length) {
            console.error('No valid blocks data collected for auto-save');
            return;
        }

        // Log the blocks being sent
        console.log('Auto-saving blocks:', JSON.stringify(blocks, null, 2));

        // Convert blocks to JSON string
        var blocksJson = JSON.stringify(blocks);
        console.log('Blocks JSON to send:', blocksJson);

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'save_newsletter_blocks');
        formData.append('security', newsletterData.nonceSaveBlocks);
        formData.append('newsletter_slug', newsletterData.newsletterSlug);
        formData.append('blocks', blocksJson);
        formData.append('is_auto_save', '1');

        // Send AJAX request
        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    console.log('Auto-save successful');
                    // Trigger preview update after successful save
                    if (typeof window.updatePreview === 'function') {
                        window.updatePreview(true);  // Skip auto-save trigger
                    }
                } else {
                    console.error('Auto-save failed:', response);
                    console.error('Response data:', response.data);
                    console.error('Blocks JSON sent:', blocksJson);
                }
            },
            error: function(xhr, status, error) {
                console.error('Auto-save error:', error);
                console.error('XHR:', xhr.responseText);
                console.error('Blocks JSON sent:', blocksJson);
            }
        });
    }

    // Manual save function (for save button)
    window.saveBlocks = function() {
        console.log('Manual save triggered');
        
        // Save editor content first
        saveAllEditors();
        
        // Collect block data
        var blocks = collectBlockData();
        
        if (!blocks || !blocks.length) {
            console.error('No valid blocks data collected');
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
            }
        });
    };

    // Create debounced version of autoSave
    const debouncedAutoSave = debounce(autoSave, 1000);

    // Set up event listeners
    function initializeAutoSave() {
        if (autoSaveInitialized) return;
        autoSaveInitialized = true;

        // Listen for changes on block container
        $('#blocks-container').on('change', function(event) {
            const target = event.target;
            if ($(target).is('.block-type, .block-title-input, .show-title-toggle, .block-template, .block-category, .block-date-range, .block-story-count, input[name*="[manual_override]"], input[name*="[checked]"], .post-order, .html-block textarea, .wysiwyg-editor-content')) {
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