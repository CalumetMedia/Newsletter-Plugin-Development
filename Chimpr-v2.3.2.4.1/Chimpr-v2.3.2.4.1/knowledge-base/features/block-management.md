# Block Management System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #blocks #content-management #templates #wysiwyg #pdf-link

## Overview
The Block Management System provides a flexible and extensible framework for managing newsletter content blocks. It supports multiple block types, content organization, template management, and dynamic content loading with both manual and automated content selection.

## System Architecture

### 1. Core Components
```php
// Main component structure
Block_Management_System
├── Block_Manager
├── Block_Types
│   ├── Content_Block
│   ├── HTML_Block
│   ├── WYSIWYG_Block
│   └── PDF_Link_Block
├── Template_Manager
└── State_Manager
```

### 2. File Structure
```
includes/
├── ajax/
│   ├── ajax-save-blocks.php      // Block saving
│   └── ajax-generate-preview.php  // Preview generation
├── form-handlers.php             // Form processing
└── block-types/                  // Block type handlers
assets/
├── js/
│   ├── block-manager.js         // Core management
│   └── auto-save.js            // Auto-save functionality
└── css/
    └── blocks.css              // Block styling
```

## Block Types

### 1. Content Block
```php
// Content block structure
$content_block = [
    'type' => 'content',
    'title' => string,
    'show_title' => boolean,
    'category' => string,
    'date_range' => string,
    'story_count' => string,
    'manual_override' => boolean,
    'posts' => array
];
```

### 2. HTML Block
```php
// HTML block structure
$html_block = [
    'type' => 'html',
    'title' => string,
    'show_title' => boolean,
    'html' => string,
    'template_id' => string
];
```

### 3. WYSIWYG Block
```php
// WYSIWYG block structure
$wysiwyg_block = [
    'type' => 'wysiwyg',
    'title' => string,
    'show_title' => boolean,
    'content' => string,
    'template_id' => string
];
```

### 4. PDF Link Block
```php
// PDF link block structure
$pdf_link_block = [
    'type' => 'pdf_link',
    'block_title': string,
    'show_title': boolean,
    'template_id': string,
    'html': string  // Content pulled from template
];

// Field States
// Active Fields:
// - Template selection
//
// Disabled Fields:
// - Category selection
// - Date range
// - Story count
// - Manual override
```

### 5. PDF Link Block Implementation
```javascript
// Template content handling
function handlePdfLinkBlock($block) {
    // Disable irrelevant fields
    $block.find('.category-select, .date-range, .story-count, .manual-override')
        .prop('disabled', true);
        
    // Enable template selection
    $block.find('.template-select').prop('disabled', false);
    
    // Pull content from template
    const templateId = $block.find('.template-select').val();
    if (templateId) {
        loadTemplateContent(templateId, function(content) {
            $block.find('.html-content').val(content);
            updatePreview();
        });
    }
}

// Template selection handling
function handleTemplateChange($block) {
    if ($block.find('.block-type').val() === 'pdf_link') {
        handlePdfLinkBlock($block);
    }
}
```

### 3. WYSIWYG Block Implementation
```javascript
// Editor initialization
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
                    // Critical settings for proper quote and character handling
                    entity_encoding: 'raw',
                    encoding: 'xml',
                    verify_html: false,
                    entities: '160,nbsp',
                    fix_list_elements: true,
                    // Force paragraph formatting
                    forced_root_block: 'p',
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    remove_redundant_brs: false,
                    // Add these settings
                    valid_elements: '*[*]',
                    extended_valid_elements: '*[*]',
                    keep_styles: true,
                    setup: function(editor) {
                        // Initialize editor content
                        editor.on('init', function() {
                            console.log('Editor initialized:', editorId);
                            // Restore content after initialization
                            if (currentContent) {
                                currentContent = currentContent.replace(/\\/g, '');
                                if (currentContent.indexOf('<p>') === -1) {
                                    currentContent = switchEditors.wpautop(currentContent);
                                }
                                editor.setContent(currentContent);
                                editor.save();
                            }
                            console.log('Initial content set:', currentContent);
                            updatePreview('editor_init');
                        });
                    }
                }
            });
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
```

### 4. WYSIWYG Content Management
```php
// Content sanitization and formatting
function process_wysiwyg_content($content) {
    if (empty($content)) {
        return '';
    }
    
    // Handle paragraph formatting
    if (strpos($content, '<p>') === false) {
        $content = wpautop($content);
    }
    
    // Sanitize content
    return wp_kses_post(wp_unslash($content));
}

// Content preservation during save
function preserve_wysiwyg_content($block, $existing_block) {
    if ($block['type'] !== 'wysiwyg') {
        return $block;
    }
    
    if (empty($block['wysiwyg']) && !empty($existing_block['wysiwyg'])) {
        $block['wysiwyg'] = $existing_block['wysiwyg'];
    }
    
    return $block;
}
```

## Block Management

### 1. Block Initialization
```javascript
function initializeBlocks() {
    $('#blocks-container').sortable({
        handle: '.block-drag-handle',
        update: function(event, ui) {
            updateBlockIndices();
        }
    });

    // Initialize existing blocks
    $('.block-item').each(function() {
        initializeBlock($(this));
    });
}
```

### 2. Block Creation
```javascript
window.addBlock = function() {
    var blockIndex = $('#blocks-container .block-item').length;
    var blockHtml = `
        <div class="block-item" data-index="${blockIndex}">
            <!-- Block structure -->
        </div>
    `;
    $('#blocks-container').append(blockHtml);
    initializeBlock($('#blocks-container .block-item').last());
};
```

## State Management

### 1. Block State
```javascript
window.saveBlockState = function($block, isManual, callback) {
    var blocks = [];
    $('#blocks-container .block-item').each(function(index) {
        var blockData = {
            type: $(this).find('.block-type').val(),
            title: $(this).find('.block-title-input').val(),
            show_title: $(this).find('.show-title-toggle').prop('checked'),
            template_id: $(this).find('.block-template').val(),
            category: $(this).find('.block-category').val(),
            date_range: $(this).find('.block-date-range').val(),
            story_count: $(this).find('.block-story-count').val(),
            manual_override: $(this).find('.manual-override-toggle').prop('checked'),
            posts: collectPostData($(this))
        };
        blocks.push(blockData);
    });
    return saveBlocks(blocks, callback);
};
```

### 2. Data Type Handling
```php
// Server-side type checking
if (is_string($_POST['blocks'])) {
    $blocks_json = stripslashes($_POST['blocks']);
    $blocks = json_decode($blocks_json, true);
} else if (is_array($_POST['blocks'])) {
    $blocks = $_POST['blocks'];
} else {
    wp_send_json_error(['message' => 'Blocks data must be an array']);
}
```

### 3. Content Preservation
```php
// Preserve existing content during auto-save
if ($is_auto_save && !empty($existing_content)) {
    if (empty($content) || trim($content) === '<p></p>') {
        $sanitized_block['wysiwyg'] = $existing_content;
    }
}
```

### 4. Save Verification
```php
// Verify save operation success
$current_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
$save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
$verify_save = get_option("newsletter_blocks_$newsletter_slug");
$actually_saved = !empty($verify_save) && 
                 serialize($verify_save) === serialize($sanitized_blocks);
```

### 5. WYSIWYG Content Handling
```php
// Ensure proper WYSIWYG content preservation
function handle_wysiwyg_content($block, $existing_block = null) {
    if ($block['type'] !== 'wysiwyg') {
        return $block;
    }
    
    // Handle empty content cases
    if (empty($block['content']) && !empty($existing_block['content'])) {
        error_log('Preserving existing WYSIWYG content');
        $block['content'] = $existing_block['content'];
    }
    
    // Ensure proper content format
    if (!empty($block['content'])) {
        $block['content'] = wp_kses_post($block['content']);
    }
    
    return $block;
}
```

### 6. Content Management
```javascript
window.collectPostData = function($block) {
    var posts = {};
    $block.find('.block-posts li').each(function(index) {
        var $post = $(this);
        var postId = $post.data('post-id');
        if ($post.find('input[type="checkbox"]').prop('checked')) {
            posts[postId] = {
                checked: '1',
                order: $post.find('.post-order').val() || index.toString()
            };
        }
    });
    return posts;
};
```

## Auto-Save System

### 1. Configuration
```javascript
const AUTO_SAVE_DELAY = 2000; // 2 seconds
let autoSaveTimeout = null;
let lastSavedState = null;
```

### 2. Implementation
```javascript
function autoSave() {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    
    autoSaveTimeout = setTimeout(function() {
        var currentState = collectBlockData();
        if (JSON.stringify(currentState) !== JSON.stringify(lastSavedState)) {
            saveBlocks(currentState, function() {
                lastSavedState = currentState;
            });
        }
    }, AUTO_SAVE_DELAY);
}
```

## Template System

### 1. Template Structure
```php
$available_templates = [
    'default' => [
        'name' => 'Default Template',
        'content' => string
    ],
    'custom' => [
        'name' => 'Custom Template',
        'content' => string
    ]
];
```

### 2. Template Variables
- `{post_title}` - Story title
- `{post_content}` - Story content
- `{post_excerpt}` - Story excerpt
- `{post_date}` - Publication date
- `{category_name}` - Category name
- `{block_title}` - Block title

## Error Handling

### 1. Client-Side Errors
```javascript
function handleSaveError(error) {
    console.error("Save failed:", error);
    notifyUser("Save operation failed");
}
```

### 2. Server-Side Errors
```php
function handleError($message, $debug_info = []) {
    wp_send_json_error([
        'message' => $message,
        'debug_info' => $debug_info
    ]);
}
```

### 3. Debug Logging
```php
// Server-side logging
error_log("Blocks data type: " . gettype($blocks_data));
error_log("Save operation result: " . var_export($save_result, true));
error_log("Verification result: " . var_export($actually_saved, true));
```

## Best Practices

### 1. Block Management
- Limit maximum blocks (10)
- Validate block data
- Maintain block order
- Handle template changes
- Implement auto-save

### 2. Content Loading
- Cache category posts
- Implement pagination
- Handle empty states
- Provide fallbacks
- Validate content

### 3. State Management
- Track changes properly
- Handle race conditions
- Implement debouncing
- Validate state updates
- Maintain consistency

### 4. PDF Link Block Management
- Maintain template field as only active field
- Handle template content retrieval properly
- Ensure field states persist through save/load
- Handle preview generation consistently
- Maintain proper block spacing
- Implement proper error handling
- Log template content processing

### 5. Data Persistence
1. Always verify data types before processing
2. Implement thorough error handling
3. Use proper sanitization methods
4. Verify save operations explicitly
5. Maintain consistent data structure
6. Handle special cases appropriately
7. Implement comprehensive logging
8. Validate WYSIWYG content format
9. Preserve existing content when appropriate
10. Track all save operations

### 6. WYSIWYG Editor Management
1. Always use `wp_kses_post()` for content sanitization
2. Properly destroy editor instances before DOM manipulation
3. Store content before editor instance removal
4. Use unique editor IDs based on block index
5. Handle content formatting with `wpautop` when needed
6. Ensure proper FormData handling in AJAX operations
7. Implement proper error handling for preview generation

## Common Issues and Solutions

### 1. Block Ordering
- **Issue**: Order not maintained
- **Solution**: Check sortable
- **Prevention**: Order validation
- **Monitoring**: Order tracking

### 2. Content Loading
- **Issue**: Load failures
- **Solution**: Verify AJAX
- **Prevention**: Error checks
- **Monitoring**: Load status

### 3. Auto-Save
- **Issue**: Save conflicts
- **Solution**: State tracking
- **Prevention**: Debouncing
- **Monitoring**: Save status

## Dependencies
- jQuery UI Sortable
- TinyMCE Editor
- WordPress AJAX
- WordPress Options API
- Template System

## Related Documentation
- [Newsletter Configuration](newsletter-configuration.md)
- [Template System](template-system.md)
- [Error Handling](error-handling.md)
- [Content Management](content-management.md) 