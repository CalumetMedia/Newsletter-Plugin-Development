# Newsletter Blocks Feature Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Newsletter Content Management
**Tags**: #blocks #content #advertising #templates #stories

## Overview
The Newsletter Blocks system provides a flexible, modular approach to organizing newsletter content. It supports two primary block types: Content blocks for category-based stories and Advertising blocks for custom HTML content. Each block can be independently configured, reordered, and templated.

## Block Types

### 1. Content Blocks
- Category-based story selection
- Template assignment
- Story count configuration
- Post filtering and ordering
- Dynamic content updates

### 2. Advertising Blocks
- Custom HTML content
- Template support
- Position control
- Preview rendering

## Implementation Details

### Block Data Structure
```php
{
    type: string,        // Block type identifier
    category: string,    // WordPress category ID
    title: string,       // Block display title
    posts: array [       // Selected posts array
        {
            ID: int,
            post_title: string,
            post_content: string,
            post_date: string
        }
    ],
    html: string,        // Advertising HTML content
    template_id: string, // Template identifier
    story_count: string  // Number of stories to show
}
```

### Storage and Retrieval
```php
// Block data storage
update_option("newsletter_blocks_$newsletter_slug", $blocks);

// Block data retrieval
$blocks = get_option("newsletter_blocks_$newsletter_slug", []);
```

## UI Components

### 1. Block Container
```html
<div id="blocks-container">
    <!-- Individual block items -->
    <div class="block-item">
        <!-- Block content -->
    </div>
</div>
```

### 2. Block Controls
- Type selector (Content/Advertising)
- Category dropdown
- Template selector
- Story count input
- Remove block button
- Drag handle

## JavaScript Integration

### 1. Block Management
```javascript
// Block initialization
$('#blocks-container').sortable({
    handle: '.drag-handle',
    update: function(event, ui) {
        // Update block order
    }
});

// Add new block
$('#add-block').on('click', function() {
    // Create new block
});

// Remove block
$('.remove-block').on('click', function() {
    // Remove block
});
```

### 2. Content Loading
```javascript
// Load posts for category
function loadCategoryPosts(categoryId, blockElement) {
    $.ajax({
        url: newsletterData.ajaxUrl,
        data: {
            action: 'load_block_posts',
            category: categoryId,
            security: newsletterData.nonceLoadPosts
        },
        success: function(response) {
            // Update block content
        }
    });
}
```

### 3. Template Handling
```javascript
// Template selection
$('.template-selector').on('change', function() {
    // Update block template
});

// Preview update
function updateBlockPreview(blockElement) {
    // Generate preview content
}
```

## Block Templates

### 1. Template Structure
```php
// Template registration
$available_templates = [
    'default' => [
        'name' => 'Default Template',
        'content' => $default_template
    ],
    // Additional templates...
];
```

### 2. Template Variables
- `{post_title}` - Story title
- `{post_content}` - Story content
- `{post_excerpt}` - Story excerpt
- `{post_date}` - Publication date
- `{category_name}` - Category name
- `{block_title}` - Block title

## Event System

### 1. Block Events
```javascript
// Block type change
$('.block-type').on('change', function() {
    // Update block interface
});

// Category change
$('.category-selector').on('change', function() {
    // Load category posts
});

// Story count change
$('.story-count').on('change', function() {
    // Update preview
});
```

### 2. Save Events
```javascript
// Form submission
$('#blocks-form').on('submit', function(e) {
    // Validate and save blocks
});

// Auto-save
function autoSaveBlocks() {
    // Save current block state
}
```

## Error Handling

### 1. Validation
```php
function validateBlockData($block) {
    $errors = [];
    
    if (empty($block['type'])) {
        $errors[] = 'Block type is required';
    }
    
    if ($block['type'] === 'content' && empty($block['category'])) {
        $errors[] = 'Category is required for content blocks';
    }
    
    return $errors;
}
```

### 2. Error Messages
- Invalid block type
- Missing category
- Template not found
- Post loading failed
- Save operation failed

## Best Practices

### 1. Block Management
- Limit maximum blocks (recommended: 10)
- Validate block data before save
- Maintain block order integrity
- Handle template availability

### 2. Content Loading
- Cache category posts
- Implement pagination
- Handle empty categories
- Provide fallback content

### 3. Template Processing
- Validate template existence
- Sanitize template variables
- Handle missing content
- Maintain responsive design

## Common Issues and Solutions

### 1. Block Ordering
- Issue: Blocks not maintaining order
- Solution: Check sortable initialization and save order

### 2. Content Loading
- Issue: Posts not loading
- Solution: Verify AJAX nonce and category ID

### 3. Template Rendering
- Issue: Template variables not replacing
- Solution: Check variable format and content availability

## Performance Considerations

### 1. Content Loading
- Implement post caching
- Lazy load post content
- Optimize AJAX requests
- Batch post updates

### 2. Template Processing
- Cache processed templates
- Minimize DOM updates
- Optimize preview generation
- Handle large content blocks

## Security Measures

### 1. Data Validation
- Sanitize block data
- Validate template content
- Check user capabilities
- Verify nonces

### 2. Content Security
- Sanitize HTML content
- Validate post access
- Check category permissions
- Filter template variables

## Related Documentation
- [Newsletter Stories](newsletter-stories.md)
- [Preview System](newsletter-preview.md)
- [Template Management](newsletter-templates.md)
- [Content Security](content-security.md) 