// auto-save.js
(function($) {
    let autoSaveInitialized = false;
    let isSaving = false;
    let pendingChanges = false;

    window.editorState = {
        contentVersions: {},
        pendingSaves: new Set(),
        lastSavedContent: {},
        initialized: false
    };

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function saveAllEditors() {
        if (typeof tinymce !== 'undefined') {
            tinymce.editors.forEach(function(editor) {
                if (editor && !editor.isHidden()) {
                    editor.save();
                    const editorId = editor.id;
                    const content = editor.getContent();
                    const key = `wysiwyg_${editorId}`;
                    
                    if (window.editorState.lastSavedContent[key] !== content) {
                        window.editorState.pendingSaves.add(key);
                    }
                }
            });
        }
    }

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
                    window.editorState.contentVersions[key] = (window.editorState.contentVersions[key] || 0) + 1;
                    blockData.content_version = window.editorState.contentVersions[key];
                } else {
                    blockData.wysiwyg = $('#' + editorId).val() || '';
                }
            }
            else if (blockType === 'html') {
                const key = `html_${blockIndex}`;
                blockData.html = block.find('textarea[name*="[html]"]').val() || '';
                window.editorState.contentVersions[key] = (window.editorState.contentVersions[key] || 0) + 1;
                blockData.content_version = window.editorState.contentVersions[key];
            }

            blocks.push(blockData);
        });
        return blocks;
    }

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

    window.saveBlocks = function() {
        if (isSaving) {
            return;
        }
        
        isSaving = true;
        saveAllEditors();
        
        var blocks = collectBlockData();
        if (!blocks || !blocks.length) {
            isSaving = false;
            return;
        }

        var blocksJson = JSON.stringify(blocks);

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
                    alert(response.data && response.data.message ? response.data.message : 'An error occurred while saving blocks.');
                }
            },
            error: function() {
                alert('An error occurred while saving blocks. Please try again.');
            },
            complete: function() {
                isSaving = false;
            }
        });
    };

    const debouncedAutoSave = debounce(autoSave, 1000);

    function initializeAutoSave() {
        if (autoSaveInitialized) return;
        autoSaveInitialized = true;

        window.editorState.initialized = true;

        $('#blocks-container').on('change input', 'input, select, textarea', function(event) {
            const $target = $(event.target);

            if ($target.is('input[type="checkbox"][name*="[posts]"]')) {
                return;
            }
            
            debouncedAutoSave();
        });

        if (window.tinyMCE) {
            window.tinyMCE.on('AddEditor', function(e) {
                e.editor.on('change keyup paste', function() {
                    e.editor.save();
                    debouncedAutoSave();
                });
            });
        }

        $(window).on('beforeunload', function() {
            if (window.editorState.pendingSaves.size > 0) {
                return 'Changes you made may not be saved.';
            }
        });

        setTimeout(autoSave, 1000);
    }

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
