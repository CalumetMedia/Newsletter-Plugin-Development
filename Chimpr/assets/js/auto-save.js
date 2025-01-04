// auto-save.js
(function($) {
    // Global initialization tracking
    let autoSaveInitialized = false;
    let isSaving = false;
    let pendingChanges = false;

    // Enhanced state management
    window.editorState = {
        contentVersions: {},
        pendingSaves: new Set(),
        lastSavedContent: {},
        initialized: false
    };

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
                    const editorId = editor.id;
                    const content = editor.getContent();
                    const key = `wysiwyg_${editorId}`;
                    
                    // Only mark as pending if content has changed
                    if (window.editorState.lastSavedContent[key] !== content) {
                        window.editorState.pendingSaves.add(key);
                    }
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
            
            var blockData = {
                type: blockType,
                title: block.find('.block-title-input').val(),
                show_title: block.find('.show-title-toggle').is(':checked') ? 1 : 0,
                template_id: block.find('.block-template').val() || '0'
            };

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
            }
            else if (blockType === 'wysiwyg') {
                var editorId = 'wysiwyg-editor-' + blockIndex;
                const key = `wysiwyg_${editorId}`;
                
                if (tinymce.get(editorId)) {
                    blockData.wysiwyg = tinymce.get(editorId).getContent();
                    // Track content version
                    window.editorState.contentVersions[key] = (window.editorState.contentVersions[key] || 0) + 1;
                    blockData.content_version = window.editorState.contentVersions[key];
                } else {
                    blockData.wysiwyg = $('#' + editorId).val() || '';
                }
            }
            else if (blockType === 'html') {
                const key = `html_${blockIndex}`;
                blockData.html = block.find('textarea[name*="[html]"]').val() || '';
                // Track content version
                window.editorState.contentVersions[key] = (window.editorState.contentVersions[key] || 0) + 1;
                blockData.content_version = window.editorState.contentVersions[key];
            }

            blocks.push(blockData);
        });
        return blocks;
    }

    // Enhanced auto-save function
    function autoSave() {
        if (isSaving) {
            pendingChanges = true;
            return;
        }

        isSaving = true;
        saveAllEditors();
        
        var blocks = collectBlockData();
        
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
                    // Update last saved content for each block
                    blocks.forEach(function(block, index) {
                        if (block.type === 'wysiwyg') {
                            const key = `wysiwyg_wysiwyg-editor-${index}`;
                            window.editorState.lastSavedContent[key] = block.wysiwyg;
                            window.editorState.pendingSaves.delete(key);
                        } else if (block.type === 'html') {
                            const key = `html_${index}`;
                            window.editorState.lastSavedContent[key] = block.html;
                            window.editorState.pendingSaves.delete(key);
                        }
                    });
                }
            },
            complete: function() {
                isSaving = false;
                if (pendingChanges) {
                    pendingChanges = false;
                    autoSave();
                }
            }
        });
    }

    // Manual save function
    window.saveBlocks = function() {
        if (isSaving) {
            console.log('Save in progress, please wait...');
            return;
        }
        
        isSaving = true;
        saveAllEditors();
        
        var blocks = collectBlockData();
        if (!blocks || !blocks.length) {
            console.error('No valid blocks data collected');
            isSaving = false;
            return;
        }

        // Convert blocks to JSON string
        var blocksJson = JSON.stringify(blocks);

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

        $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Update last saved content
                    blocks.forEach(function(block, index) {
                        if (block.type === 'wysiwyg') {
                            const key = `wysiwyg_wysiwyg-editor-${index}`;
                            window.editorState.lastSavedContent[key] = block.wysiwyg;
                            window.editorState.pendingSaves.delete(key);
                        } else if (block.type === 'html') {
                            const key = `html_${index}`;
                            window.editorState.lastSavedContent[key] = block.html;
                            window.editorState.pendingSaves.delete(key);
                        }
                    });
                    
                    alert(newsletterData.blocksSavedMessage || 'Blocks have been saved successfully.');
                    if (typeof window.updatePreview === 'function') {
                        window.updatePreview(true);
                    }
                } else {
                    console.error('Save failed:', response);
                    alert(response.data && response.data.message ? response.data.message : 'An error occurred while saving blocks.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
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
        console.log('[Debug Race] Auto-save initialization started', new Date().getTime());
        
        window.debouncedAutoSave = _.debounce(function(source) {
            console.log('[Debug Race] Auto-save triggered from:', source, 'Time:', new Date().getTime());
            
            // Ensure all editors are saved
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
                console.log('[Debug Race] TinyMCE triggerSave called', new Date().getTime());
            }
            
            // Collect form data
            var formData = new FormData($('#newsletter-form')[0]);
            console.log('[Debug Race] Form data collected, size:', formData.entries().length, 'Time:', new Date().getTime());
            
            // Log WYSIWYG content lengths before save
            $('.wysiwyg-editor-content').each(function() {
                var editorId = $(this).attr('id');
                console.log('[Debug Race] Pre-save content length for', editorId, ':', $(this).val().length, 'Time:', new Date().getTime());
            });
            
            $.ajax({
                url: window.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('[Debug Race] Auto-save completed', new Date().getTime(), 'Response:', response.success);
                    if (response.success) {
                        // Log content lengths after save
                        $('.wysiwyg-editor-content').each(function() {
                            var editorId = $(this).attr('id');
                            console.log('[Debug Race] Post-save content length for', editorId, ':', $(this).val().length, 'Time:', new Date().getTime());
                        });
                    }
                }
            });
        }, 1000);

        if (autoSaveInitialized) return;
        autoSaveInitialized = true;

        // Initialize content tracking
        window.editorState.initialized = true;

        // Listen for changes on block container and form fields
        $('#blocks-container').on('change input', 'input, select, textarea', function(event) {
            const $target = $(event.target);
            
            // Don't trigger auto-save for story selection changes
            if ($target.is('input[type="checkbox"][name*="[posts]"]')) {
                return;
            }
            
            debouncedAutoSave();
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

        // Handle page unload
        $(window).on('beforeunload', function() {
            if (window.editorState.pendingSaves.size > 0) {
                return 'Changes you made may not be saved.';
            }
        });

        // Initial auto-save
        setTimeout(autoSave, 1000);
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