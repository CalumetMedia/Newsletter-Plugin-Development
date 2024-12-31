(function($) {
    window.initWysiwygEditor = function(block) {
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            
            if (typeof tinymce !== 'undefined') {
                // Remove existing editor if present
                if (tinymce.get(editorId)) {
                    tinymce.execCommand('mceRemoveEditor', true, editorId);
                }
                
                // Store current content before initialization
                var currentContent = $(this).val();
                
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                        // Critical settings for proper quote handling
                        entity_encoding: 'raw',
                        verify_html: false,
                        entities: '160,nbsp',
                        fix_list_elements: true,
                        // Force paragraph formatting
                        forced_root_block: 'p',
                        remove_linebreaks: false,
                        convert_newlines_to_brs: false,
                        remove_redundant_brs: false,
                        setup: function(editor) {
                            // Initialize editor content
                            editor.on('init', function() {
                                console.log('Editor initialized:', editorId);
                                // Restore content after initialization
                                if (currentContent) {
                                    if (currentContent.indexOf('<p>') === -1) {
                                        currentContent = switchEditors.wpautop(currentContent);
                                    }
                                    editor.setContent(currentContent);
                                    editor.save();
                                }
                                console.log('Initial content set:', currentContent);
                                updatePreview('editor_init');
                            });

                            // Handle content changes
                            editor.on('change keyup NodeChange SetContent', function() {
                                console.log('Editor content changed:', editorId);
                                clearTimeout(editor.contentChangeTimer);
                                editor.contentChangeTimer = setTimeout(function() {
                                    editor.save(); // Save content to textarea
                                    var content = editor.getContent();
                                    console.log('New content:', content);
                                    
                                    // Only trigger auto-save if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof debouncedAutoSave === 'function') {
                                            debouncedAutoSave('editor_content_change');
                                        }
                                    }
                                }, 250); // Restore original timing
                            });

                            // Additional keyup handler for immediate feedback
                            editor.on('keyup', function() {
                                clearTimeout(editor.updateTimer);
                                editor.updateTimer = setTimeout(function() {
                                    console.log('Editor keyup:', editorId);
                                    editor.save();
                                    var content = editor.getContent();
                                    console.log('Keyup content:', content);
                                    
                                    // Only trigger change if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                    }
                                }, 250); // Restore original timing
                            });

                            // Handle paste events with proper debouncing
                            editor.on('paste', function(e) {
                                clearTimeout(editor.pasteTimer);
                                editor.pasteTimer = setTimeout(function() {
                                    console.log('Editor paste:', editorId);
                                    editor.save();
                                    var content = editor.getContent();
                                    console.log('Paste content:', content);
                                    
                                    // Only trigger change if content has actually changed
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof debouncedAutoSave === 'function') {
                                            debouncedAutoSave('editor_paste');
                                        }
                                    }
                                }, 250); // Restore original timing
                            });

                            // Handle form submission
                            editor.on('submit', function() {
                                editor.save();
                            });

                            // Handle editor removal
                            editor.on('remove', function() {
                                editor.save();
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

                // Add preview styles if not already present
                if (!$('#wysiwyg-preview-styles').length) {
                    $('head').append(`
                        <style id="wysiwyg-preview-styles">
                            .wysiwyg-content p { 
                                margin: 0 0 1em 0;
                                padding: 0;
                            }
                            .wysiwyg-content ul,
                            .wysiwyg-content ol { 
                                margin: 0 0 1em 2em;
                                padding: 0;
                            }
                            .wysiwyg-content li { 
                                margin-bottom: 0.5em;
                            }
                            .wysiwyg-content h1,
                            .wysiwyg-content h2,
                            .wysiwyg-content h3,
                            .wysiwyg-content h4,
                            .wysiwyg-content h5,
                            .wysiwyg-content h6 {
                                margin: 1.5em 0 0.5em 0;
                                padding: 0;
                            }
                        </style>
                    `);
                }
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

})(jQuery);