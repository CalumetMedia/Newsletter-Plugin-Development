# Newsletter Preview System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #preview #blocks #rendering #templates #real-time #post-selection

## Overview
The Newsletter Preview System provides real-time visualization of newsletter content through a sophisticated rendering engine that processes blocks, templates, and custom HTML. It supports multiple block types, dynamic content updates, and integrated PDF preview capabilities.

## System Architecture

### 1. Core Components
```php
// Main preview generation flow
newsletter_generate_preview_content($newsletter_slug, $blocks)
├── get_newsletter_posts($blocks)      // Process block content
├── apply_template($content, $template) // Apply template structure
└── render_preview($html)              // Output final preview
```

### 2. File Structure
- `admin/partials/render-preview.php`: Main preview rendering
- `includes/ajax/ajax-generate-preview.php`: AJAX preview handling
- `includes/helpers.php`: Preview generation utilities
- `includes/pdf/views/pdf-preview.php`: PDF-specific preview
- `assets/js/preview.js`: Client-side preview management

## Preview Generation Process

### 1. Initialization
```php
// Set environment parameters
set_time_limit(120);                    // Extended processing time
ini_set('memory_limit', '256M');        // Increased memory allocation
ini_set('zlib.output_compression', 'On'); // Output compression
```

### 2. Block Processing
- Content Blocks
  - Post retrieval and ordering
  - Category-based filtering
  - Template application
  - Story count handling

- Advertising Blocks
  - HTML content sanitization
  - Template integration
  - Spacing management

- PDF Link Blocks
  - Template content retrieval
  - HTML transformation
  - Link generation

### 3. Template Integration
```php
$available_templates = get_option('newsletter_templates', []);
$template_id = $block['template_id'];
$template_content = $available_templates[$template_id]['html'];
```

## Real-Time Preview Updates

### 1. Post Selection Handling
- ALWAYS use 'checked' as the key for post selection status
- Never introduce alternative keys (like `selected`) as this causes preview failures
- Maintain key consistency throughout the entire data flow

### 2. Required Functions
```javascript
// Core functionality
collectBlockStates()    // Collects all block data
collectPostStates()     // Collects post selection state
generatePreview()       // Generates preview HTML
updatePreviewDisplay()  // Updates preview container
saveBlocks()           // Manual save functionality

// State management
resetPreviewState()     // Resets preview state
saveAllEditors()       // Saves TinyMCE editors
initializePreview()    // Initializes preview functionality
autoSaveAndUpdatePreview() // Handles auto-save
debouncedAutoSave()    // Debounced auto-save
```

### 3. State Management Variables
```javascript
previewUpdatePromise    // Tracks current preview update
globalUpdateInProgress  // Prevents concurrent updates
previewTimeout         // Handles update debouncing
activeRequests         // Tracks active AJAX requests
```

### 4. Data Flow
1. User selects posts in UI
2. Frontend collects post states with 'checked' key
3. AJAX sends data maintaining 'checked' key
4. Backend processes maintaining 'checked' key
5. Preview generation uses 'checked' key
6. Database stores with 'checked' key

## Performance Optimization

### 1. Resource Management
- Memory allocation control
- Processing time limits
- Output compression
- AJAX request throttling

### 2. Caching Strategy
```php
// Template caching
$cached_templates = wp_cache_get('newsletter_templates');
if (false === $cached_templates) {
    $cached_templates = get_option('newsletter_templates', []);
    wp_cache_set('newsletter_templates', $cached_templates);
}
```

## Error Handling

### 1. Validation Checks
```php
// Block validation
if (!is_array($blocks)) {
    return '<p>Error: Invalid blocks data</p>';
}

// Template validation
if (!isset($available_templates[$template_id])) {
    error_log("Template not found: $template_id");
    return '<p>Error: Template not found</p>';
}
```

### 2. Error Logging
- Invalid block data
- Missing templates
- Processing failures
- AJAX errors

## Security Measures

### 1. Input Validation
```php
// Sanitization
$newsletter_slug = sanitize_text_field($_POST['newsletter_slug']);
$block_content = wp_kses_post(wp_unslash($content));
```

### 2. Access Control
- Nonce verification
- Capability checking
- User authentication
- Data sanitization

## Best Practices

### 1. Content Processing
- Always sanitize HTML content
- Validate template existence
- Handle missing data gracefully
- Maintain proper error logging

### 2. Performance
- Implement request throttling
- Cache template data
- Optimize DOM updates
- Handle large content efficiently

### 3. Security
- Verify all user inputs
- Sanitize HTML content
- Check user capabilities
- Implement proper logging

## Common Issues and Solutions

### 1. Preview Not Updating
- **Issue**: Preview content not refreshing
- **Solution**: Clear template cache and regenerate
- **Prevention**: Implement proper cache invalidation
- **Monitoring**: Add update status logging

### 2. Memory Issues
- **Issue**: Memory limit exceeded
- **Solution**: Increase allocation and optimize
- **Prevention**: Implement content chunking
- **Monitoring**: Track memory usage

### 3. Template Problems
- **Issue**: Template not applying correctly
- **Solution**: Verify template structure
- **Prevention**: Add template validation
- **Monitoring**: Log template processing

## Integration Points

### 1. Block Manager
```javascript
// Block type change handling
function handleBlockTypeChange(block) {
    var blockType = block.find('.block-type select').val();
    updatePreview();
}
```

### 2. PDF Generation
```php
// PDF preview integration
function render_pdf_preview($newsletter_slug) {
    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    apply_pdf_template($content);
}
```

## Dependencies
- WordPress Core Functions
- jQuery for AJAX
- TinyMCE for WYSIWYG
- Newsletter Template System
- PDF Generation System

## Related Documentation
- [Newsletter Stories](newsletter-stories.md)
- [Block Management](newsletter-blocks.md)
- [Template System](newsletter-templates.md)
- [PDF Generation](pdf-generation.md)

## Testing Requirements

### Additional Testing Requirements
1. Verify post selection persistence
2. Confirm preview displays all selected posts
3. Test auto-save functionality
4. Ensure preview updates when posts are selected/deselected

## Warning
DO NOT:
1. Change key names without full impact analysis
2. Remove or modify critical functions
3. Alter state management variables
4. Change data structure without validation 