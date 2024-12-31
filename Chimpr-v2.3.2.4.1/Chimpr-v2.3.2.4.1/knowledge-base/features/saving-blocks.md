# Newsletter Block Saving Implementation

## Overview
This document outlines the implementation details for saving newsletter blocks, including both manual and auto-save functionality. It covers critical considerations for data handling, type checking, and content preservation.

## Data Flow
1. Client-side collection of block data
2. AJAX transmission to server
3. Server-side validation and processing
4. Database storage
5. Verification of saved state

## Critical Components

### 1. Data Type Handling
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

### 2. Content Preservation
```php
// Preserve existing content during auto-save
if ($is_auto_save && !empty($existing_content)) {
    if (empty($content) || trim($content) === '<p></p>') {
        $sanitized_block['wysiwyg'] = $existing_content;
    }
}
```

### 3. Save Verification
```php
// Verify save operation success
$current_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
$save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);
$verify_save = get_option("newsletter_blocks_$newsletter_slug");
$actually_saved = !empty($verify_save) && 
                 serialize($verify_save) === serialize($sanitized_blocks);
```

### 4. WYSIWYG Content Handling
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

## Implementation Guidelines

### Client-Side
1. Collect block data:
   - Save TinyMCE editor content
   - Gather block states
   - Maintain data structure

2. Handle auto-save:
   - Implement debouncing
   - Track pending requests
   - Handle race conditions

3. Data transmission:
   - Use proper AJAX endpoints
   - Include necessary metadata
   - Handle errors appropriately

### Server-Side
1. Data validation:
   - Check data types
   - Validate structure
   - Handle special cases

2. Content processing:
   - Sanitize appropriately
   - Preserve formatting
   - Handle special characters

3. Storage operations:
   - Use WordPress options API
   - Verify save success
   - Handle errors gracefully

## Common Issues and Solutions

### 1. Type Mismatch
**Issue**: WordPress may automatically unserialize POST data
**Solution**: Check data type before processing
```php
if (is_string($data)) {
    // Handle string input
} else if (is_array($data)) {
    // Handle array input
}
```

### 2. Content Loss
**Issue**: Content may be lost during auto-save
**Solution**: Preserve existing content when appropriate
```php
if ($is_auto_save && !empty($existing_content)) {
    // Preserve existing content
}
```

### 3. Race Conditions
**Issue**: Concurrent save operations may conflict
**Solution**: Implement proper request tracking
```javascript
// Client-side request tracking
let activeRequests = new Set();
function trackRequest(requestId) {
    activeRequests.add(requestId);
}
```

### 4. WYSIWYG Persistence
**Issue**: WYSIWYG content may not persist after page reload
**Solution**: Implement proper content preservation and validation
```php
// Server-side content validation
function validate_wysiwyg_block($block) {
    if ($block['type'] !== 'wysiwyg') {
        return true;
    }
    
    // Verify content structure
    if (!isset($block['content'])) {
        error_log('Invalid WYSIWYG block structure');
        return false;
    }
    
    // Check content format
    if (!empty($block['content']) && 
        strpos($block['content'], '<p>') === false) {
        error_log('Invalid WYSIWYG content format');
        return false;
    }
    
    return true;
}
```

## Testing Requirements

### 1. Data Persistence
- Verify content after page reload
- Check auto-save functionality
- Test manual save operations

### 2. Content Types
- Test WYSIWYG content
- Verify HTML blocks
- Check special characters

### 3. Concurrent Operations
- Test multiple save requests
- Verify content integrity
- Check error handling

### 4. WYSIWYG Specific Tests
- Verify content persistence after refresh
- Test empty content handling
- Validate format preservation
- Check special character handling
- Test concurrent edits
- Verify preview accuracy

## Debug Logging
```php
// Server-side logging
error_log("Blocks data type: " . gettype($blocks_data));
error_log("Save operation result: " . var_export($save_result, true));
error_log("Verification result: " . var_export($actually_saved, true));
```

## Error Handling

### Client-Side
```javascript
function handleSaveError(error) {
    console.error("Save failed:", error);
    notifyUser("Save operation failed");
}
```

### Server-Side
```php
function handleError($message, $debug_info = []) {
    wp_send_json_error([
        'message' => $message,
        'debug_info' => $debug_info
    ]);
}
```

## Best Practices
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

## Related Documentation
- KNOWN_ISSUES.md
- wysiwyg-persistence-issue-20241230-1515.md
- preview-handling.md 