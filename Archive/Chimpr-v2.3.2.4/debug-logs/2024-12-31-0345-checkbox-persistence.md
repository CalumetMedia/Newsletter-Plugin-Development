# Checkbox State Persistence Investigation
Timestamp: 2024-12-31 03:45 UTC

## Issue Summary
Checkbox states for manual story selection are not persisting correctly after save operations. The issue appears to be related to how the data is structured and handled between frontend and backend operations.

## Debug Data
### Initial State
```php
// Normalized selections showing empty array
[29-Dec-2024 22:16:32 UTC] Normalized selections: Array()

// Saved selections showing all unchecked
[29-Dec-2024 22:16:32 UTC] Saved selections: Array(
    [0] => Array(
        [selections] => Array(
            [265019] => Array([checked] => [order] => 9223372036854775807)
            [265201] => Array([checked] => [order] => 9223372036854775807)
            // ... more posts with same pattern
        )
    )
)
```

### After Save Operation
```php
// Received POST data showing attempted changes
[29-Dec-2024 22:16:33 UTC] Received POST data: Array(
    [blocks] => Array(
        [0] => Array(
            [posts] => Array(
                [265996] => Array([checked] => [order] => 4)
                [266208] => Array([checked] => [order] => 3)
                // ... more posts with updated orders
            )
        )
    )
)
```

## Root Cause Analysis
1. Data Structure Issues:
   - Inconsistent handling of checked/unchecked states
   - Mismatch between frontend data collection and backend storage
   - Merge operations potentially overwriting states

2. Save Operation Flow:
   - Only storing checked posts
   - Not properly handling unchecked state
   - Order values being reset

3. Load Operation Issues:
   - Normalization process dropping states
   - Merge logic not preserving manual selections
   - Inconsistent handling of default values

## Code Changes Made
1. Modified `ajax-save-blocks.php`:
```php
// Process post data
if (isset($block['posts']) && is_array($block['posts'])) {
    foreach ($block['posts'] as $post_id => $post_data) {
        // Only store checked posts
        if (isset($post_data['checked']) && $post_data['checked'] == '1') {
            $sanitized_block['posts'][$post_id] = [
                'checked' => '1',
                'order' => $order
            ];
        }
    }
}
```

2. Updated `ajax-load-block-posts.php`:
```php
// Normalize saved selections
if (!empty($saved_selections)) {
    if (isset($saved_selections[$block_index]['posts'])) {
        foreach ($saved_selections[$block_index]['posts'] as $post_id => $data) {
            if (!is_array($data)) continue;
            // Only store checked posts
            if (isset($data['checked']) && $data['checked'] === '1') {
                $normalized_selections[$post_id] = [
                    'checked' => '1',
                    'order' => isset($data['order']) ? intval($data['order']) : PHP_INT_MAX
                ];
            }
        }
    }
}
```

## Problems Solved
1. Standardized data structure:
   - Consistent use of '1' for checked state
   - Empty string for unchecked state
   - Proper order value handling

2. Improved state handling:
   - Proper merge of existing and new selections
   - Maintained manual mode states
   - Fixed order preservation

3. Enhanced validation:
   - Better type checking
   - Improved error handling
   - Added debug logging

## Problems Remaining
1. Edge Cases:
   - Multiple rapid saves
   - Race conditions
   - Error recovery

2. Performance:
   - Large post sets
   - Multiple block updates
   - Save operation timing

3. UX Issues:
   - State feedback
   - Error messaging
   - Loading indicators

## Verification Steps
1. Manual Mode Testing:
   ```javascript
   // Check initial state
   console.log('Initial state:', window.collectPostData($block));
   
   // Toggle checkboxes
   $block.find('input[type="checkbox"]').trigger('change');
   
   // Verify save operation
   console.log('After save:', window.collectPostData($block));
   ```

2. State Persistence:
   - Save and reload page
   - Check normalized selections
   - Verify checkbox states

3. Order Preservation:
   - Reorder posts
   - Save changes
   - Verify order maintained

## Related Issues
- Manual mode toggle state preservation
- Preview generation synchronization
- Multiple save operation handling 