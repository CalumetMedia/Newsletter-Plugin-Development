(function($) {
    window.initWysiwygEditor = function(block) {
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            
            if (typeof tinymce !== 'undefined') {
                // Remove existing editor if present
                if (tinymce.get(editorId)) {
                    tinymce.execCommand('mceRemoveEditor', true, editorId);
                }
                
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
                        setup: function(editor) {
                            // Initialize editor content
                            editor.on('init', function() {
                                var content = editor.getContent();
                                content = content.replace(/\\'/g, "'").replace(/\\"/g, '"');
                                if (content && content.indexOf('<p>') === -1) {
                                    content = switchEditors.wpautop(content);
                                }
                                editor.setContent(content);
                                updatePreview();
                            });

                            // Handle content changes
                            editor.on('change keyup NodeChange SetContent', function() {
                                editor.save();
                                updatePreview();
                            });

                            // Additional keyup handler for immediate feedback
                            editor.on('keyup', function() {
                                clearTimeout(editor.updateTimer);
                                editor.updateTimer = setTimeout(function() {
                                    editor.save();
                                    updatePreview();
                                }, 300);
                            });

                            // Handle paste events
                            editor.on('paste', function(e) {
                                setTimeout(function() {
                                    editor.save();
                                    updatePreview();
                                }, 100);
                            });
                        },
                        // Force paragraph formatting
                        forced_root_block: 'p',
                        remove_linebreaks: false,
                        convert_newlines_to_brs: false,
                        remove_redundant_brs: false,
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
    window.updatePreview = function() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        if (typeof originalUpdatePreview === 'function') {
            originalUpdatePreview();
        }
    };

})(jQuery);