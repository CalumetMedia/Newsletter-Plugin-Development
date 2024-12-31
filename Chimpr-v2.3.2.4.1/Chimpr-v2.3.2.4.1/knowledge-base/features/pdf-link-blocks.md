# PDF Link Block Feature Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Block Content Type
**Tags**: #pdf #template #block #preview

## Overview
The PDF Link block type provides a specialized block that pulls content from templates while maintaining a simplified interface with only the template selection active. This block type is designed to be used for consistent, template-based content that doesn't require additional configuration.

## Key Components

### Data Structure
```javascript
{
    type: 'pdf_link',
    block_title: string,
    show_title: boolean,
    template_id: string,
    html: string  // Content pulled from template
}
```

### File Responsibilities

1. **`admin/partials/block-item.php`**
   - Block type option in dropdown
   - Field state management
   - Template selection interface
   - Disabled state for other fields

2. **`assets/js/block-manager.js`**
   - Block type change handling
   - Field state management
   - Template selection handling
   - Preview update triggers

3. **`includes/helpers.php`**
   - Preview content generation
   - Template content retrieval
   - Block data processing
   - HTML content handling

4. **`includes/ajax/ajax-save-blocks.php`**
   - Block data saving
   - Template content preservation
   - Field state persistence

5. **`includes/ajax/ajax-generate-preview.php`**
   - Preview generation
   - Template content processing
   - Block type handling

## Key Variables and Functions

### JavaScript
- `blockType`: 'pdf_link'
- `handleBlockTypeChange(block)`: Block type change handler
- `initializeBlock(block)`: Block initialization
- `updatePreview()`: Preview update trigger
- `templateSelect`: Template selection element

### PHP
- `$block['type']`: 'pdf_link'
- `$block['template_id']`: Selected template ID
- `$block['html']`: Template content
- `$available_templates`: Available templates array
- `newsletter_generate_preview_content()`: Preview generation

## Field States
1. **Active Fields**
   - Template selection

2. **Disabled Fields**
   - Category selection
   - Date range
   - Story count
   - Manual override

## Template Integration
1. **Content Source**
   - Content pulled from selected template
   - Template content stored in 'html' field
   - Content treated like HTML in preview

2. **Preview Generation**
   - Template content processed as HTML
   - Block spacing maintained
   - Title display respected
   - Content sanitization applied

## Best Practices
1. Always maintain template field as only active field
2. Properly handle template content retrieval
3. Ensure field states persist through save/load
4. Handle preview generation consistently
5. Maintain proper block spacing
6. Implement proper error handling
7. Log template content processing

## Related Documentation
- [Block Manager Documentation](block-manager.md)
- [Newsletter Templates](templates.md)
- [Preview Generation](preview-handling.md) 