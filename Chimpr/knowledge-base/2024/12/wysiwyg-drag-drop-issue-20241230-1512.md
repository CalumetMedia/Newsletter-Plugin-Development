# WYSIWYG Content Lost During Drag and Drop
**Date**: 2024-12-30 15:12
**Tags**: #wysiwyg #drag-drop #tinymce #block-reordering #editor-initialization

## Issue Description
When reordering blocks using drag and drop functionality, WYSIWYG editor content was being lost and the editor became unusable until the page was refreshed.

## Diagnosis
1. TinyMCE editor instances were not being properly destroyed before reordering
2. Editor IDs and names were not being correctly updated during reordering
3. Editor content was not being preserved during the reordering process
4. Editor instances were not being properly reinitialized after reordering

## Solution
### Files Modified

1. `assets/js/block-manager.js`:
```javascript
// Updated updateBlockIndices function to handle editor content
window.updateBlockIndices = function() {
    // Store editor contents before reindexing
    var editorContents = {};
    $('#blocks-container .block-item').each(function() {
        var oldEditorId = $(this).find('.wysiwyg-editor-content').attr('id');
        if (oldEditorId && tinymce.get(oldEditorId)) {
            editorContents[oldEditorId] = tinymce.get(oldEditorId).getContent();
            tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
        }
    });
    
    // Update indices and reinitialize editors with content
    $('#blocks-container .block-item').each(function(index) {
        // ... update names and IDs ...
        if ($(this).hasClass('wysiwyg-editor-content')) {
            var oldId = $(this).attr('id');
            var newId = 'wysiwyg-editor-' + index;
            // ... reinitialize editor with stored content ...
        }
    });
}
```

2. `assets/js/events.js`:
```javascript
// Updated sortable initialization
$("#blocks-container").sortable({
    // ... existing options ...
    start: function(event, ui) {
        // Store editor content before sorting starts
        var $editor = ui.item.find('.wysiwyg-editor-content');
        if ($editor.length) {
            var editorId = $editor.attr('id');
            if (tinymce.get(editorId)) {
                ui.item.data('editor-content', tinymce.get(editorId).getContent());
                tinymce.execCommand('mceRemoveEditor', true, editorId);
            }
        }
    }
});
```

## Key Learnings
1. **Editor Instance Management**: Properly destroy and reinitialize TinyMCE instances during DOM manipulation
2. **Content Preservation**: Store editor content before any major DOM operations
3. **ID Management**: Maintain unique editor IDs when reordering elements
4. **Event Timing**: Use appropriate jQuery UI Sortable events for editor handling
5. **Error Prevention**: Clean up old editor instances to prevent memory leaks and conflicts

## Testing Verification
1. WYSIWYG content is preserved after drag and drop operations
2. Editor remains fully functional after reordering
3. Multiple editors in different blocks work correctly
4. Content formatting is maintained during reordering
5. No duplicate editor instances are created

## Related Issues
- [WYSIWYG Content Not Saving](wysiwyg-save-issue-20241230-1511.md)

## Search Keywords
wysiwyg, tinymce, drag and drop, sortable, block reordering, editor content, content preservation, jquery ui, wordpress editor, block manager 