# WYSIWYG Content Preservation During Preview Generation
**Version**: 2.3.2.4.2
**Component**: Block Management System
**Status**: Fixed

## Issue Description
During newsletter preview generation, WYSIWYG block content was being lost due to the preview generation request not properly preserving existing content. This resulted in empty WYSIWYG blocks in the preview, even though the content was properly saved in the database.

## Root Cause Analysis
The issue stemmed from three main problems:
1. The preview generation AJAX request was receiving empty WYSIWYG content in the saved selections
2. The code didn't have a fallback mechanism to retrieve existing content from the database
3. When WYSIWYG content was missing from the request, it was being set to an empty string without checking for existing content

## Technical Details

### Data Flow
1. Initial block content is stored in `wp_options` table with key `newsletter_blocks_{$slug}`
2. Preview generation is triggered via AJAX POST to `ajax-generate-preview.php`
3. POST data includes `saved_selections` parameter with current block state
4. Preview generation merges saved selections with existing blocks

### Code Changes
Location: `includes/ajax/ajax-generate-preview.php`

```php
// Handle WYSIWYG content
if (isset($block_data['type']) && $block_data['type'] === 'wysiwyg') {
    $saved_blocks[$block_index]['type'] = 'wysiwyg';
    
    // Check if WYSIWYG content exists in new data
    if (!empty($block_data['wysiwyg'])) {
        $saved_blocks[$block_index]['wysiwyg'] = wp_kses_post($block_data['wysiwyg']);
    } else {
        // Preserve existing WYSIWYG content if present
        $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        if (isset($existing_blocks[$block_index]['wysiwyg'])) {
            $saved_blocks[$block_index]['wysiwyg'] = $existing_blocks[$block_index]['wysiwyg'];
        } else {
            $saved_blocks[$block_index]['wysiwyg'] = '';
        }
    }
}
```

### Key Changes
1. Added check for new content using `!empty()` instead of `isset()`
2. Implemented fallback to existing content in database
3. Added detailed logging for content tracking
4. Enhanced validation of incoming data structure

## Testing
### Test Cases
1. Generate preview with new WYSIWYG content
2. Generate preview without modifying existing WYSIWYG content
3. Generate preview after clearing WYSIWYG content
4. Generate preview with multiple WYSIWYG blocks

### Expected Behavior
- New content should appear in preview when provided
- Existing content should be preserved when no new content is provided
- Empty content should only appear when explicitly cleared
- All WYSIWYG blocks should maintain their content independently

## Monitoring
### Log Points
The following log messages indicate proper functioning:
- "Using new WYSIWYG content for block {index}"
- "Preserving existing WYSIWYG content for block {index}"
- "No WYSIWYG content found for block {index}"

### Error Conditions
Watch for:
- JSON decode errors in saved selections
- Invalid block data structures
- Missing WYSIWYG content in both request and database

## Related Components
- Block Management System
- Preview Generation System
- WYSIWYG Editor Integration
- Auto-save System

## Future Considerations
1. Consider implementing content validation before preview generation
2. Add automated testing for preview generation
3. Implement content comparison to avoid unnecessary updates
4. Consider caching preview data for performance optimization 