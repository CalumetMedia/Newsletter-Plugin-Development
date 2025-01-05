/**
 * block-sort.js
 * Handles sorting and index reassignments for blocks and stories.
 * Depends on jQuery, state.js (for isUpdateInProgress, setUpdateInProgress), and utilities.js if needed.
 */
(function($, window) {
    // Reindex blocks after any reorder event
    function updateBlockIndices() {
        // Store current TinyMCE editor contents before reindexing
        var editorContents = {};
        $('#blocks-container .block-item').each(function() {
            var oldEditorId = $(this).find('.wysiwyg-editor-content').attr('id');
            if (oldEditorId && tinymce.get(oldEditorId)) {
                editorContents[oldEditorId] = tinymce.get(oldEditorId).getContent();
                tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
            }
        });
        
        // Update each block’s data-index and name attributes
        $('#blocks-container .block-item').each(function(index) {
            $(this).data('index', index);
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/blocks\[\d+\]/, 'blocks[' + index + ']');
                    $(this).attr('name', newName);

                    // If it’s a WYSIWYG editor, update its ID and re-init
                    if ($(this).hasClass('wysiwyg-editor-content')) {
                        var oldId = $(this).attr('id');
                        var newId = 'wysiwyg-editor-' + index;
                        $(this).attr('id', newId);
                        
                        if (oldId && editorContents[oldId]) {
                            var content = editorContents[oldId];
                            setTimeout(function() {
                                wp.editor.initialize(newId, {
                                    tinymce: {
                                        wpautop: true,
                                        plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                                        toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                                        setup: function(editor) {
                                            editor.on('init', function() {
                                                editor.setContent(content);
                                            });
                                            editor.on('change keyup paste input', function() {
                                                if (isUpdateInProgress()) return;
                                                editor.save();
                                                setUpdateInProgress(true);
                                                setTimeout(() => {
                                                    updatePreview('wysiwyg_content_change');
                                                    setUpdateInProgress(false);
                                                }, 250);
                                            });
                                        }
                                    },
                                    quicktags: true,
                                    mediaButtons: true
                                });
                            }, 100);
                        }
                    }
                }
            });
        });

        // Trigger a preview update after reordering
        if (!isUpdateInProgress()) {
            setUpdateInProgress(true);
            setTimeout(() => {
                updatePreview('block_reorder');
                setUpdateInProgress(false);
            }, 250);
        }
    }

    /**
     * Initialize sortable for post elements within a block.
     * 
     * @param {jQuery} block - The `.block-item` element.
     */
function initializeSortable(block) {
    // Ensure we have jQuery UI loaded
    if (typeof jQuery.ui === 'undefined') {
        console.error('jQuery UI not loaded');
        return;
    }

    var $list = block.find('.sortable-posts');
    var manualOverride = block.find('input[name*="[manual_override]"]').prop('checked');

    // Debug logs
    console.log('Initialize sortable:', {
        listFound: $list.length > 0,
        manualOverride: manualOverride,
        itemCount: $list.find('.story-item').length
    });

    // Destroy if exists
    if ($list.hasClass('ui-sortable')) {
        $list.sortable('destroy');
    }

    // Basic sortable config
    $list.sortable({
        handle: '.story-drag-handle',
        items: '.story-item',
        axis: 'y',
        cursor: 'grabbing',
        opacity: 0.7,
        disabled: !manualOverride,
        stop: function(event, ui) {
            console.log('Sort stopped');
            $list.find('.story-item').each(function(index) {
                $(this).find('.post-order').val(index);
            });
        }
    }).disableSelection();

    // Add explicit handle styling
    $list.find('.story-drag-handle').css({
        'cursor': manualOverride ? 'grab' : 'default',
        'opacity': manualOverride ? 1 : 0.5
    });
}

// Ensure initialization on document ready and after manual override changes
$(document).ready(function() {
    $('.block-item').each(function() {
        initializeSortable($(this));
    });

    $(document).on('change', '.manual-override-toggle', function() {
        initializeSortable($(this).closest('.block-item'));
    });
});

    // Expose functions to the global scope
    window.updateBlockIndices = updateBlockIndices;
    window.initializeSortable = initializeSortable;

})(jQuery, window);
