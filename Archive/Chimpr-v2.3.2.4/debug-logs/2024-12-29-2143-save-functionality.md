# Debug Log: Save Functionality Issue
**Date**: 2024-12-29 21:43:16 UTC

## Initial Error
Save operation failing with error response:
```javascript
{
    success: false, 
    data: {
        message: 'Failed to save blocks',
        debug_info: {
            newsletter_slug: 'wire-report',
            block_count: 1
        }
    }
}
```

## Server-side Debug Log
```
[29-Dec-2024 21:43:16 UTC] Received POST data: Array
(
    [action] => save_newsletter_blocks
    [security] => 70b5c2616b
    [newsletter_slug] => wire-report
    [blocks] => Array
        (
            [0] => Array
                (
                    [type] => content
                    [title] => 
                    [show_title] => 1
                    [template_id] => 2
                    [category] => 56
                    [date_range] => 60
                    [story_count] => disable
                    [manual_override] => 1
                    [posts] => Array
                        (
                            [265996] => Array
                                (
                                    [selected] => 1
                                    [order] => 4
                                )
                            [266208] => Array
                                (
                                    [selected] => 1
                                    [order] => 3
                                )
                            [266382] => Array
                                (
                                    [selected] => 1
                                    [order] => 2
                                )
                            [266683] => Array
                                (
                                    [selected] => 1
                                    [order] => 1
                                )
                            [266868] => Array
                                (
                                    [selected] => 0
                                    [order] => 0
                                )
                        )
                )
        )
)
```

## Root Cause Analysis
1. Data Format Mismatch:
   - Frontend sending `selected` but backend expecting `checked`
   - Frontend using numeric order values, backend using strings
   - Inconsistent handling of unchecked posts

2. Option Saving Issues:
   - Direct `update_option` failing
   - No cleanup of old data before save
   - Potential data corruption from mixed formats

## Code Changes

### 1. Frontend Data Collection (`ajax-operations.js`)
```javascript
// Before
var formData = $('#blocks-form').serializeArray();

// After
var blockData = {
    type: $block.find('.block-type').val(),
    title: $block.find('.block-title-input').val(),
    show_title: $block.find('.show-title-toggle').prop('checked') ? 1 : 0,
    template_id: $block.find('.block-template').val(),
    category: $block.find('.block-category').val(),
    date_range: $block.find('.block-date-range').val(),
    story_count: $block.find('.block-story-count').val(),
    manual_override: $block.find('input[name*="[manual_override]"]').prop('checked') ? 1 : 0,
    posts: {}
};

$block.find('.block-posts li').each(function() {
    var $post = $(this);
    var postId = $post.data('post-id');
    var $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
    var $orderInput = $post.find('.post-order');
    var isChecked = $checkbox.prop('checked');
    
    blockData.posts[postId] = {
        checked: isChecked ? '1' : '',
        order: $orderInput.val() || '0'
    };
});
```

### 2. Backend Data Handling (`ajax-save-blocks.php`)
```php
// Before
$save_result = update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

// After
$option_name = "newsletter_blocks_$newsletter_slug";
delete_option($option_name);
$save_result = add_option($option_name, $sanitized_blocks, '', 'no');
if (!$save_result) {
    $save_result = update_option($option_name, $sanitized_blocks, 'no');
}
```

### 3. Debug Logging
```php
error_log('Newsletter Save - Received blocks data structure: ' . print_r($_POST['blocks'], true));
error_log('Newsletter Save - Final save result: ' . ($save_result ? 'Success' : 'Failed'));
error_log('Newsletter Save - Saved data structure: ' . print_r($sanitized_blocks, true));
```

## Testing Results
1. Manual Override:
   - ✅ State correctly preserved
   - ✅ Checkbox states maintained
   - ✅ Order values preserved

2. Post Selection:
   - ✅ Checked posts saved
   - ✅ Unchecked posts excluded
   - ✅ Order maintained

3. Error Handling:
   - ✅ Detailed error messages
   - ✅ Debug info in logs
   - ✅ Failed saves reported

## Related Issues
- Preview not updating after save
- Manual selection mode persistence
- Post order preservation

## Next Steps
1. Monitor error logs for save failures
2. Verify data consistency across saves
3. Test edge cases:
   - Large number of posts
   - Mixed checked/unchecked states
   - Order changes 