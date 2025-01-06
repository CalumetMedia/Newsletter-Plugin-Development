(function($) {
    window.initWysiwygEditor = function(block) {
        block.find('.wysiwyg-editor-content').each(function() {
            var editorId = $(this).attr('id');
            
            if (typeof tinymce !== 'undefined') {
                var currentContent = '';
                
                // Log pre-initialization state
                debugLog('Pre-Editor Init', {
                    editorId: editorId,
                    hasExistingEditor: !!tinymce.get(editorId),
                    textareaContent: $(this).val()
                });
                
                if (tinymce.get(editorId)) {
                    currentContent = tinymce.get(editorId).getContent();
                    debugLog('Removing Existing Editor', {
                        editorId: editorId,
                        content: currentContent
                    });
                    tinymce.execCommand('mceRemoveEditor', true, editorId);
                } else {
                    currentContent = $(this).val();
                }
                
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
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
                                debugLog('Editor Initialized', {
                                    editorId: editorId,
                                    hasContent: !!currentContent,
                                    contentLength: currentContent.length
                                });
                                
                                if (currentContent) {
                                    if (currentContent.indexOf('<p>') === -1) {
                                        currentContent = switchEditors.wpautop(currentContent);
                                    }
                                    editor.setContent(currentContent);
                                    editor.save();
                                    
                                    debugLog('Content Restored', {
                                        editorId: editorId,
                                        restoredContent: editor.getContent(),
                                        textareaContent: editor.getElement().value
                                    });
                                }
                            });

                            editor.on('change keyup NodeChange SetContent', function() {
                                clearTimeout(editor.contentChangeTimer);
                                editor.contentChangeTimer = setTimeout(function() {
                                    editor.save();
                                    var content = editor.getContent();
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof window.debouncedAutoSave === 'function') {
                                            window.debouncedAutoSave('editor_content_change');
                                        }
                                    }
                                }, 250);
                            });

                            editor.on('paste', function() {
                                clearTimeout(editor.pasteTimer);
                                editor.pasteTimer = setTimeout(function() {
                                    editor.save();
                                    var content = editor.getContent();
                                    var previousContent = $('#' + editorId).val();
                                    if (content !== previousContent) {
                                        $('#' + editorId).val(content).trigger('change');
                                        if (typeof window.debouncedAutoSave === 'function') {
                                            window.debouncedAutoSave('editor_paste');
                                        }
                                    }
                                }, 250);
                            });

                            editor.on('submit', function() {
                                editor.save();
                            });

                            editor.on('remove', function() {
                                editor.save();
                            });

                            editor.on('dirty', function() {
                                if (typeof window.trackEditorChanges === 'function') {
                                    window.trackEditorChanges(editorId, editor.getContent());
                                }
                            });
                        },
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

    $(document).on('change', '.block-type', function() {
        var block = $(this).closest('.block-item');
        if ($(this).val() === 'wysiwyg') {
            setTimeout(function() {
                initWysiwygEditor(block);
            }, 100);
        }
    });

    $(document).ready(function() {
        $('.block-type').each(function() {
            if ($(this).val() === 'wysiwyg') {
                initWysiwygEditor($(this).closest('.block-item'));
            }
        });
    });

    $(document).on('click', '#add-block', function() {
        setTimeout(function() {
            $('.block-type').each(function() {
                if ($(this).val() === 'wysiwyg') {
                    initWysiwygEditor($(this).closest('.block-item'));
                }
            });
        }, 100);
    });

    var originalUpdatePreview = window.updatePreview;
    window.updatePreview = function(source) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        if (typeof originalUpdatePreview === 'function') {
            originalUpdatePreview(source);
        }
    };

    window.verifyEditorContent = function(editorId) {
        if (tinymce.get(editorId)) {
            var editor = tinymce.get(editorId);
            var content = editor.getContent();
            var textarea = editor.getElement();
            
            if (content !== textarea.value) {
                textarea.value = content;
                return false;
            }
            return true;
        }
        return false;
    };
})(jQuery);
