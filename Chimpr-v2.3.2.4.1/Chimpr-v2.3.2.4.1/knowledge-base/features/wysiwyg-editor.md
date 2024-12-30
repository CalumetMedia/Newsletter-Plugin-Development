# WYSIWYG Editor Feature Documentation
**Last Updated**: 2024-12-30
**Feature Type**: Block Content Editor
**Tags**: #wysiwyg #editor #tinymce #content-editing

## Overview
The WYSIWYG editor provides rich text editing capabilities for newsletter blocks. It uses WordPress's TinyMCE implementation with custom configurations for newsletter content editing.

## Key Components

### Data Structure
```javascript
{
    type: 'wysiwyg',
    block_title: string,
    show_title: boolean,
    wysiwyg: string,  // HTML content
    template_id: string
}
```

### File Responsibilities

1. **`assets/js/editor.js`**
   - Editor initialization and configuration
   - Content change event handling
   - TinyMCE instance management
   - Preview update triggers

2. **`assets/js/block-manager.js`**
   - Block index management
   - Editor instance lifecycle during reordering
   - Content preservation during block operations
   - Integration with preview system

3. **`includes/helpers.php`**
   - Content sanitization (`wp_kses_post`)
   - Data structure transformation
   - Block data processing
   - HTML formatting (`wpautop`)

4. **`includes/form-handlers.php`**
   - Form submission processing
   - Content sanitization
   - Data persistence

5. **`includes/ajax/ajax-save-blocks.php`**
   - AJAX save operations
   - Content validation
   - Response handling

## Key Variables and Functions

### JavaScript
- `editorId`: `wysiwyg-editor-{blockIndex}`
- `editorContents`: Temporary storage during reordering
- `initWysiwygEditor(block)`: Editor initialization
- `updateBlockIndices()`: Block reordering handler
- `tinymce.get(editorId)`: Editor instance access
- `editor.getContent()`: Content retrieval
- `editor.setContent(content)`: Content setting

### PHP
- `$block['wysiwyg']`: Raw editor content
- `$sanitized_block['wysiwyg']`: Sanitized content
- `wp_kses_post()`: Content sanitization
- `wp_unslash()`: Data unslashing
- `wpautop()`: Paragraph formatting

## Recent Changes

### 2024-12-30
1. **Content Saving Fix**
   - Issue: Content not persisting after save
   - Details: [WYSIWYG Save Issue](2024/12/wysiwyg-save-issue-20241230-1511.md)

2. **Drag and Drop Fix**
   - Issue: Content lost during block reordering
   - Details: [WYSIWYG Drag Drop Issue](2024/12/wysiwyg-drag-drop-issue-20241230-1512.md)

## Best Practices
1. Always use `wp_kses_post()` for content sanitization
2. Properly destroy editor instances before DOM manipulation
3. Store content before editor instance removal
4. Use unique editor IDs based on block index
5. Handle content formatting with `wpautop` when needed

## Related Documentation
- [WordPress TinyMCE Documentation](https://codex.wordpress.org/TinyMCE)
- [Block Manager Documentation](block-manager.md)
- [Newsletter Templates](templates.md) 