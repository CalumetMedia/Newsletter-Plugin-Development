# Blocks Save Operation Issue (2024-12-30)

## Issue Description
Newsletter blocks were failing to save when containing special characters (apostrophes, quotes) or WYSIWYG content. The save operation would return a failure response despite having proper write permissions to the WordPress options table.

## Symptoms
- Save operations failing with "Failed to save blocks" error
- Content with apostrophes or special characters not persisting
- WYSIWYG content being corrupted or lost after save
- WordPress debug logs showing successful test writes but failed option updates

## Root Cause
The issue was caused by double unslashing of content in the save operation:
1. WordPress automatically unslashes POST data
2. The code was manually unslashing the entire blocks array
3. WYSIWYG content was being unslashed again during sanitization

This multiple unslashing caused:
- Corruption of special characters
- Loss of proper HTML formatting
- Inconsistent data structure

## Solution
1. Removed redundant `wp_unslash()` call in WYSIWYG content handling:
```php
// Before
$sanitized_block['wysiwyg'] = wp_kses_post(wp_unslash($block['wysiwyg']));

// After
$sanitized_block['wysiwyg'] = wp_kses_post($block['wysiwyg']);
```

2. Added enhanced error logging to track save operations:
- Data structure before save
- Serialization details
- MySQL errors and queries
- Save verification checks

3. Added verification of saved data to ensure consistency:
```php
$verify_save = get_option("newsletter_blocks_$newsletter_slug");
$actually_saved = !empty($verify_save) && 
    serialize($verify_save) === serialize($sanitized_blocks);
```

## Testing
The fix was verified with:
- WYSIWYG content containing apostrophes and quotes
- Block titles with special characters
- Multiple blocks with mixed content types
- Large content blocks with formatted HTML

## Related Changes
- Enhanced error logging in save operations
- Added save verification checks
- Updated documentation for content handling
- Added known issue documentation

## Prevention
To prevent similar issues:
1. Be aware of WordPress's automatic unslashing
2. Use appropriate sanitization functions
3. Implement proper error logging
4. Add verification checks for critical operations

## See Also
- [Saving Blocks Feature](/knowledge-base/features/saving-blocks.md)
- [WYSIWYG Save Issue](/knowledge-base/2024/12/wysiwyg-save-issue-20241230-1511.md) 