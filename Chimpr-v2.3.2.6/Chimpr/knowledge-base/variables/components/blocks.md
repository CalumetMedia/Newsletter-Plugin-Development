# Block System Variables
**Component**: Newsletter Block Management
**Last Updated**: January 2, 2025

## Block Data Structures
[Content moved from main catalog - Block System Variables section]

## WordPress Options
[Content moved from main catalog - relevant WordPress Options]

## State Management
```
Variable: $block_state
Type: array
Scope: global (JavaScript)
Files: assets/js/block-editor.js
Purpose: Manages block editor state
Structure: {
    activeBlock: number,
    isDirty: boolean,
    history: array
}
```

## Known Issues
- Block types defined as strings instead of constants
- Potential state management conflicts in editor
- Block order preservation challenges

## Dependencies
- WordPress post data handling
- WYSIWYG editor integration
- Template system 