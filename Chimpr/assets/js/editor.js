(function($) {
    window.initWysiwygEditor = function(block) {
        console.log('[Debug Race] Editor initialization started', new Date().getTime());
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            console.log('[Debug Race] Processing editor:', editorId);
            
            if (typeof tinymce !== 'undefined') {
                // Store current content and editor state
                var currentContent = '';
                var hadEditor = false;
                
                if (tinymce.get(editorId)) {
                    hadEditor = true;
                    currentContent = tinymce.get(editorId).getContent();
                    console.log('[Debug Race] Retrieved content from existing editor:', editorId, 'Content length:', currentContent.length, 'Time:', new Date().getTime());
                    tinymce.execCommand('mceRemoveEditor', true, editorId);
                    console.log('[Debug Race] Removed existing editor:', editorId, 'Time:', new Date().getTime());
                } else {
                    currentContent = $(this).val();
                    console.log('[Debug Race] Retrieved content from textarea:', editorId, 'Content length:', currentContent.length, 'Time:', new Date().getTime());
                }
                
                // Initialize editor with proper settings
                console.log('[Debug Race] Starting editor initialization:', editorId, 'Time:', new Date().getTime());
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
                            console.log('[Debug Race] Editor setup started:', editorId, 'Time:', new Date().getTime());
                            editor.on('init', function() {
                                console.log('[Debug Structure] Editor init event fired:', editorId, 'Time:', new Date().getTime());
                                
                                // Properly restore content
                                if (currentContent) {
                                    console.log('[Debug Structure] About to restore content:', editorId, {
                                        contentType: typeof currentContent,
                                        contentLength: currentContent.length,
                                        hasWysiwygKey: currentContent.hasOwnProperty('wysiwyg'),
                                        rawContent: currentContent.substring(0, 100) + '...' // First 100 chars
                                    });
                                    
                                    // Handle content formatting
                                    if (currentContent.indexOf('<p>') === -1) {
                                        currentContent = switchEditors.wpautop(currentContent);
                                        console.log('[Debug Structure] Content formatted with wpautop:', editorId, {
                                            formattedLength: currentContent.length,
                                            firstPTag: currentContent.indexOf('<p>'),
                                            formattedPreview: currentContent.substring(0, 100) + '...'
                                        });
                                    }
                                    
                                    // Set content and ensure it's saved
                                    editor.setContent(currentContent);
                                    editor.save();
                                    console.log('[Debug Structure] Content state after set:', editorId, {
                                        editorContent: editor.getContent().substring(0, 100) + '...',
                                        textareaContent: $('#' + editorId).val().substring(0, 100) + '...',
                                        editorContentLength: editor.getContent().length,
                                        textareaContentLength: $('#' + editorId).val().length
                                    });
                                    
                                    // Verify content was properly set
                                    var verifyContent = editor.getContent();
                                    if (verifyContent !== currentContent) {
                                        console.warn('[Debug Structure] Content mismatch after restoration:', editorId, {
                                            expectedLength: currentContent.length,
                                            gotLength: verifyContent.length,
                                            expectedStart: currentContent.substring(0, 100) + '...',
                                            gotStart: verifyContent.substring(0, 100) + '...'
                                        });
                                    }
                                } else {
                                    console.log('[Debug Structure] No content to restore for:', editorId);
                                }
                            });

                            // Handle content changes with proper debouncing
                            editor.on('change keyup NodeChange SetContent', function() {
                                console.log('[Debug Structure] Content change detected:', editorId, {
                                    eventType: event.type,
                                    currentLength: editor.getContent().length,
                                    textareaLength: $('#' + editorId).val().length,
                                    time: new Date().getTime()
                                });
                                
                                clearTimeout(editor.contentChangeTimer);
                                editor.contentChangeTimer = setTimeout(function() {
                                    editor.save();
                                    var content = editor.getContent();
                                    
                                    // Only trigger auto-save if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    console.log('[Debug Structure] Checking content change:', editorId, {
                                        editorLength: content.length,
                                        previousLength: previousContent.length,
                                        isDifferent: content !== previousContent,
                                        time: new Date().getTime()
                                    });
                                    
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof window.debouncedAutoSave === 'function') {
                                            console.log('[Debug Structure] Triggering auto-save:', editorId, {
                                                contentLength: content.length,
                                                time: new Date().getTime()
                                            });
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