# Manual Mode Checkbox Persistence Issue

## Timestamp
- 2024-12-30 23:15 UTC

## Issue Summary
Unchecked posts in manual mode were reappearing as checked after page reload, indicating a state persistence issue between frontend and backend.

## Debug Data

### Frontend State (ajax-operations.js)
```javascript
Post 266868 data: {checked: false, order: '0'}
Post 266683 data: {checked: false, order: '1'}
Post 266382 data: {checked: true, order: '2'}
```

### Backend State (ajax-save-blocks.php)
```php
[266868] => Array (
    [checked] => 1
    [order] => 0
)
[266683] => Array (
    [checked] => 1
    [order] => 1
)
```

## Root Cause
1. Legacy code in `ajax-save-blocks.php` was preserving old selections in manual mode:
```php
// In manual mode, preserve existing checked selections that weren't in the new data
if ($sanitized_block['manual_override'] && isset($existing_blocks[$index]['posts'])) {
    foreach ($existing_blocks[$index]['posts'] as $post_id => $post_data) {
        if (!isset($sanitized_block['posts'][$post_id]) && isset($post_data['checked']) && $post_data['checked'] === '1') {
            $sanitized_block['posts'][$post_id] = [
                'checked' => '1',
                'order' => isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX
            ];
        }
    }
}
```

2. Frontend was sending all post states (checked and unchecked) in `ajax-operations.js`:
```javascript
blockData.posts[postId] = {
    checked: isChecked ? '1' : '',
    order: $orderInput.val() || '0'
};
```

## Code Changes

### 1. ajax-operations.js
Modified to only store checked posts:
```javascript
if (isChecked) {
    blockData.posts[postId] = {
        checked: '1',
        order: $orderInput.val() || '0'
    };
}
```

### 2. ajax-save-blocks.php
Removed legacy code that preserved old selections:
```php
// Process post data
if (isset($block['posts']) && is_array($block['posts'])) {
    foreach ($block['posts'] as $post_id => $post_data) {
        // Only store checked posts
        if (isset($post_data['checked']) && $post_data['checked'] == '1') {
            $sanitized_block['posts'][$post_id] = [
                'checked' => '1',
                'order' => intval($post_data['order'])
            ];
        }
    }
}
```

## Verification
- Unchecked posts now remain unchecked after page reload
- Manual mode selections are properly preserved
- No unexpected state restoration of previously checked posts

## Related Issues
- Previous save functionality issues (2024-12-29 21:43 UTC)
- Manual override state management
- Post order preservation in manual mode 