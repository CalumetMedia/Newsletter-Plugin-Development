# Story Count Selection Issue - December 30, 2024

## Issue Description
Story count selection changes in block content were not immediately reflected in the UI. Changes only became visible after saving and reloading the page.

## Root Cause Analysis
1. Event handlers for story count changes were not properly bound:
   - No namespaced events leading to potential handler conflicts
   - Inconsistent event binding across block initialization
   - Missing state management for story count changes

2. Manual override mode interaction issues:
   - Story count changes not properly respecting manual override state
   - Visual state updates not synchronized with actual state

3. State management inconsistencies:
   - Missing save operations after story count changes
   - Preview updates not triggered correctly
   - Order values not properly maintained

## Implementation Details

### Event Handler Implementation
```javascript
block.find('.block-story-count').off('change.storyCount').on('change.storyCount', function(e) {
    e.preventDefault();
    if (isUpdateInProgress()) return;
    
    const $block = $(this).closest('.block-item');
    const manualOverride = $block.find('input[name*="[manual_override]"]').prop('checked');
    
    // Only proceed if not in manual override mode
    if (!manualOverride) {
        setUpdateInProgress(true);
        const storyCountVal = $(this).val();
        const storyItems = $block.find('.story-item');
        
        // Update checkbox states based on story count
        storyItems.find('input[type="checkbox"]').prop('checked', false);
        if (storyCountVal !== 'disable' && parseInt(storyCountVal) > 0) {
            const maxStories = parseInt(storyCountVal);
            storyItems.each(function(index) {
                if (index < maxStories) {
                    $(this).find('input[type="checkbox"]').prop('checked', true);
                    $(this).find('.post-order').val(index + 1);
                }
            });
        }
        // Save state and update preview
        saveBlockState($block, false);
        updatePreview('story_count_change');
    }
});
```

### State Management
1. Block Initialization:
```javascript
// Set initial state of story count dropdown
var isManual = block.find('input[name*="[manual_override]"]').prop('checked');
var $storyCount = block.find('.block-story-count');
$storyCount.prop('disabled', isManual);
$storyCount.css('opacity', isManual ? '0.7' : '1');

// Trigger initial story count handling if needed
if (!isManual && $storyCount.val() !== 'disable') {
    $storyCount.trigger('change.storyCount');
}
```

2. Manual Override Handling:
```javascript
function updateBlockVisuals($block, isManual) {
    // Update story count dropdown state
    var $storyCount = $block.find('.block-story-count');
    $storyCount.prop('disabled', isManual);
    $storyCount.css('opacity', isManual ? '0.7' : '1');
}
```

### Server-Side Integration
1. AJAX Data Format:
```javascript
var data = {
    story_count: storyCount,
    manual_override: manualOverride ? 'true' : 'false'
};
```

2. PHP Handler:
```php
$story_count = isset($_POST['story_count']) ? $_POST['story_count'] : 'disable';
$manual_override = isset($_POST['manual_override']) && $_POST['manual_override'] === 'true';
```

## Critical Implementation Notes

### Event Handling Rules
1. Always use namespaced events to prevent conflicts
2. Check manual override state before processing changes
3. Maintain proper state management
4. Update visual state consistently

### State Management Requirements
1. Save state after story count changes
2. Update preview after state changes
3. Maintain order values
4. Respect manual override mode

### Manual Override Integration
1. Disable story count dropdown in manual mode
2. Preserve selections when toggling manual mode
3. Update visual state appropriately
4. Maintain proper order values

## Testing Points
1. Story count changes in non-manual mode
2. Manual override mode interaction
3. State persistence after save
4. Preview updates
5. Order preservation
6. Visual state consistency

## Prevention Measures
1. Use namespaced events for all handlers
2. Implement proper state management
3. Add detailed logging
4. Handle all edge cases
5. Maintain proper order values
6. Verify state changes

## Related Files
- `block-manager.js`
- `block-item.php`
- `ajax-load-block-posts.php`
- `ajax-generate-preview.php`
- `helpers.php`
- `form-handlers.php` 