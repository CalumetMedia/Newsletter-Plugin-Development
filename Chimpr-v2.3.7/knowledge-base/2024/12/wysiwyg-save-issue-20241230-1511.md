# WYSIWYG Content Not Saving
**Date**: 2024-12-30 15:11
**Tags**: #wysiwyg #content-saving #data-persistence #form-handling

## Issue Description
WYSIWYG editor content was not being preserved after save operations, resulting in empty content when the page was refreshed.

## Diagnosis
1. Debug logs showed WYSIWYG content was being sent in form data but lost during save process
2. Content was being stripped during sanitization and data structure transformation
3. Multiple points of failure in both AJAX and non-AJAX form submissions

## Solution
### Files Modified

1. `includes/ajax/ajax-save-blocks.php`:
```php
// Updated block sanitization to preserve WYSIWYG content
$sanitized_block = [
    'type' => isset($block['type']) ? sanitize_text_field($block['type']) : '',
    // ... other fields ...
];
if ($block['type'] === 'wysiwyg' && isset($block['wysiwyg'])) {
    $sanitized_block['wysiwyg'] = wp_kses_post(wp_unslash($block['wysiwyg']));
}
```

2. `includes/form-handlers.php`:
```php
// Added proper WYSIWYG content handling
if (isset($block['type']) && $block['type'] === 'wysiwyg' && isset($block['wysiwyg'])) {
    $content = wp_unslash($block['wysiwyg']);
    if (!empty($content) && strpos($content, '<p>') === false) {
        $content = wpautop($content);
    }
    $sanitized_block['wysiwyg'] = wp_kses_post($content);
}
```

3. `includes/helpers.php`:
```php
// Updated WYSIWYG block processing
case 'wysiwyg':
    $content = isset($block['wysiwyg']) ? wp_unslash($block['wysiwyg']) : '';
    $newsletter_data[] = [
        'type'        => 'wysiwyg',
        'block_title' => $block_title,
        'show_title'  => $show_title,
        'wysiwyg'     => wp_kses_post($content),
        'template_id' => $template_id
    ];
    error_log("WYSIWYG block processed with content length: " . strlen($content));
    break;
```

## Key Learnings
1. **Data Structure Consistency**: Maintain consistent block structure across all processing points
2. **Content Sanitization**: Use appropriate WordPress functions (`wp_kses_post`, `wp_unslash`) for HTML content
3. **Error Logging**: Added strategic logging points to track content flow
4. **HTML Formatting**: Handle paragraph formatting (`wpautop`) only when needed

## Testing Verification
1. WYSIWYG content successfully saves and persists after page refresh
2. HTML formatting is preserved
3. Empty WYSIWYG blocks are handled gracefully
4. Both AJAX and non-AJAX submissions work correctly

## Related Issues
- None currently documented

## Search Keywords
wysiwyg, tinymce, content saving, form submission, ajax save, block data, newsletter content, html content, data persistence, wordpress editor 