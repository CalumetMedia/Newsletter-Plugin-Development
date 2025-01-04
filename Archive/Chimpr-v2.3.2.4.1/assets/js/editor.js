(function($) {
    window.initWysiwygEditor = function(block) {
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            
            if (typeof tinymce !== 'undefined') {
                // Store current content and editor state
                var currentContent = '';
                var hadEditor = false;
                
                if (tinymce.get(editorId)) {
                    hadEditor = true;
                    currentContent = tinymce.get(editorId).getContent();
                    console.log('[Editor Debug] Retrieved content from existing editor:', editorId);
                    tinymce.execCommand('mceRemoveEditor', true, editorId);
                } else {
                    currentContent = $(this).val();
                    console.log('[Editor Debug] Retrieved content from textarea:', editorId);
                }
                
                // Initialize editor with proper settings
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                        // Critical settings for proper character handling
                        entity_encoding: 'raw',
                        encoding: 'xml',
                        verify_html: false,
                        entities: '160,nbsp',
                        fix_list_elements: true,
                        forced_root_block: 'p',
                        remove_linebreaks: false,
                        convert_newlines_to_brs: false,
                        remove_redundant_brs: false,
                        valid_elements: '*[*]',
                        extended_valid_elements: '*[*]',
                        keep_styles: true,
                        setup: function(editor) {
                            editor.on('init', function() {
                                console.log('[Editor Debug] Editor initialized:', editorId);
                                
                                // Properly restore content
                                if (currentContent) {
                                    // Handle content formatting
                                    if (currentContent.indexOf('<p>') === -1) {
                                        currentContent = switchEditors.wpautop(currentContent);
                                    }
                                    
                                    // Set content and ensure it's saved
                                    editor.setContent(currentContent);
                                    editor.save();
                                    console.log('[Editor Debug] Content restored for', editorId, 'length:', currentContent.length);
                                    
                                    // Verify content was properly set
                                    var verifyContent = editor.getContent();
                                    if (verifyContent !== currentContent) {
                                        console.warn('[Editor Debug] Content mismatch after restoration:', editorId);
                                        console.log('Expected:', currentContent);
                                        console.log('Got:', verifyContent);
                                    }
                                }
                            });

                            // Handle content changes with proper debouncing
                            editor.on('change keyup NodeChange SetContent', function() {
                                console.log('[Editor Debug] Content changed:', editorId);
                                clearTimeout(editor.contentChangeTimer);
                                editor.contentChangeTimer = setTimeout(function() {
                                    editor.save(); // Save content to textarea
                                    var content = editor.getContent();
                                    
                                    // Only trigger auto-save if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof window.debouncedAutoSave === 'function') {
                                            window.debouncedAutoSave('editor_content_change');
                                        }
                                    }
                                }, 250);
                            });

                            // Handle paste events with proper debouncing
                            editor.on('paste', function(e) {
                                clearTimeout(editor.pasteTimer);
                                editor.pasteTimer = setTimeout(function() {
                                    console.log('[Editor Debug] Paste event:', editorId);
                                    editor.save();
                                    var content = editor.getContent();
                                    
                                    // Only trigger change if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof window.debouncedAutoSave === 'function') {
                                            window.debouncedAutoSave('editor_paste');
                                        }
                                    }
                                }, 250);
                            });

                            // Force save before form submission
                            editor.on('submit', function() {
                                editor.save();
                            });

                            // Handle editor removal
                            editor.on('remove', function() {
                                editor.save();
                            });

                            // Track editor state changes
                            editor.on('dirty', function() {
                                if (typeof window.trackEditorChanges === 'function') {
                                    window.trackEditorChanges(editorId, editor.getContent());
                                }
                            });
                        },
                        // Formatting styles
                        content_style: `
                            body { 
                                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                                font-size: 14px;
                                line-height: 1.6;
                                margin: 10px;
                                padding: 0;
                            }
                            p { 
                                margin: 0 0 1em 0;
                                padding: 0;
                            }
                            ul, ol { 
                                margin: 0 0 1em 2em;
                                padding: 0;
                            }
                            li { 
                                margin-bottom: 0.5em;
                            }
                            h1, h2, h3, h4, h5, h6 {
                                margin: 1.5em 0 0.5em 0;
                                padding: 0;
                            }
                        `
                    },
                    quicktags: true,
                    mediaButtons: true
                });
            }
        });
    };

    // Handle block type changes
    $(document).on('change', '.block-type', function() {
        var block = $(this).closest('.block-item');
        if ($(this).val() === 'wysiwyg') {
            setTimeout(function() {
                initWysiwygEditor(block);
            }, 100);
        }
    });

    // Initialize editors on page load
    $(document).ready(function() {
        $('.block-type').each(function() {
            if ($(this).val() === 'wysiwyg') {
                initWysiwygEditor($(this).closest('.block-item'));
            }
        });
    });

    // Handle new block addition
    $(document).on('click', '#add-block', function() {
        setTimeout(function() {
            $('.block-type').each(function() {
                if ($(this).val() === 'wysiwyg') {
                    initWysiwygEditor($(this).closest('.block-item'));
                }
            });
        }, 100);
    });

    // Ensure TinyMCE saves content before preview updates
    var originalUpdatePreview = window.updatePreview;
    window.updatePreview = function(source) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        if (typeof originalUpdatePreview === 'function') {
            originalUpdatePreview(source);
        }
    };

    // Add helper function to verify editor content
    window.verifyEditorContent = function(editorId) {
        if (tinymce.get(editorId)) {
            var editor = tinymce.get(editorId);
            var content = editor.getContent();
            var textarea = editor.getElement();
            
            if (content !== textarea.value) {
                console.warn('[Editor Debug] Content mismatch detected for editor:', editorId);
                textarea.value = content;
                return false;
            }
            return true;
        }
        return false;
    };
})(jQuery);