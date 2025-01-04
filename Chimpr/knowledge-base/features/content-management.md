# Content Management System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #content #posts #categories #filtering #story-count

## Overview
The Content Management System provides sophisticated functionality for selecting, organizing, and managing content within newsletter blocks. It supports both automatic and manual content selection, with features for category filtering, date range selection, story count limits, and post ordering.

## System Architecture

### 1. Core Components
```php
// Main component structure
Content_Management_System
├── Post_Selection
├── Category_Filter
├── Date_Range_Filter
├── Story_Count_Manager
└── Post_Order_Manager
```

### 2. File Structure
```
includes/
├── ajax/
│   ├── ajax-load-block-posts.php   // Post loading
│   └── ajax-save-blocks.php        // Block saving
├── post-selection.php             // Selection logic
└── helpers.php                    // Utility functions
assets/
├── js/
│   ├── block-manager.js          // Block management
│   └── events.js                 // Event handlers
└── css/
    └── content.css               // Content styling
```

## Content Selection

### 1. Post Query Parameters
```php
$args = array(
    'category__in' => [$category_id],
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'date_query' => array(
        array(
            'after' => date('Y-m-d', strtotime("-{$date_range} days")),
            'inclusive' => true
        )
    )
);
```

### 2. Selection Modes
```php
// Manual selection mode
if ($block['manual_override']) {
    // Use explicitly checked posts
    if (isset($selected_posts[$post_id]['checked']) && 
        $selected_posts[$post_id]['checked'] === '1') {
        $block['posts'][$post_id] = [
            'checked' => '1',
            'order' => $order_value
        ];
    }
} else {
    // Automatic selection based on story count
    if ($story_count === 'disable' || $index < intval($story_count)) {
        $block['posts'][$post_id] = [
            'checked' => '1',
            'order' => $index
        ];
    }
}
```

## Post Management

### 1. Post Loading
```javascript
window.loadBlockPosts = function(block, categoryId, currentIndex, dateRange, storyCount) {
    var data = {
        action: 'load_block_posts',
        category_id: categoryId,
        block_index: currentIndex,
        date_range: dateRange,
        story_count: storyCount,
        manual_override: block.find('.manual-override-toggle').prop('checked')
    };
    
    return $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        data: data
    });
};
```

### 2. Post Ordering
```javascript
function updatePostOrder($block) {
    var $items = $block.find('.sortable-posts li');
    $items.each(function(index) {
        var $item = $(this);
        if ($item.find('input[type="checkbox"]').prop('checked')) {
            $item.find('.post-order').val(index);
        }
    });
}
```

## Story Count Management

### 1. Configuration
```php
// Story count options
$story_count_options = [
    'disable' => 'All',
    '1' => '1 Story',
    '2' => '2 Stories',
    '3' => '3 Stories',
    '4' => '4 Stories',
    '5' => '5 Stories',
    '6' => '6 Stories',
    '7' => '7 Stories',
    '8' => '8 Stories',
    '9' => '9 Stories',
    '10' => '10 Stories'
];
```

### 2. Implementation
```javascript
function handleStoryCountChange($block, storyCountVal) {
    const $postsList = $block.find('.sortable-posts');
    const $checkboxInputs = $postsList.find('input[type="checkbox"]');
    
    // Reset checkboxes
    $checkboxInputs.prop('checked', false);
    
    if (storyCountVal !== 'disable' && parseInt(storyCountVal) > 0) {
        const maxStories = parseInt(storyCountVal);
        const $items = $postsList.find('li').sort((a, b) => {
            return new Date($(b).data('post-date')) - 
                   new Date($(a).data('post-date'));
        });
        
        $items.each(function(index) {
            if (index < maxStories) {
                $(this).find('input[type="checkbox"]')
                    .prop('checked', true);
                $(this).find('.post-order').val(index);
            }
        });
    }
}
```

## Date Range Filtering

### 1. Options
```php
$date_range_options = [
    '1' => 'Previous 1 Day',
    '2' => 'Previous 2 Days',
    '3' => 'Previous 3 Days',
    '5' => 'Previous 5 Days',
    '7' => 'Previous 7 Days',
    '14' => 'Previous 14 Days',
    '30' => 'Previous 30 Days',
    '60' => 'Previous 60 Days',
    '90' => 'Previous 90 Days',
    '0' => 'All'
];
```

### 2. Implementation
```php
if ($date_range > 0) {
    $args['date_query'] = array(
        array(
            'after' => date('Y-m-d', strtotime("-{$date_range} days")),
            'inclusive' => true
        )
    );
}
```

## Manual Override System

### 1. Toggle Implementation
```javascript
function handleManualOverrideToggle($block, isManual) {
    const $postsList = $block.find('.sortable-posts');
    const $storyCount = $block.find('.block-story-count');
    
    // Update UI state
    $postsList.css({
        'pointer-events': isManual ? 'auto' : 'none',
        'opacity': isManual ? '1' : '0.7'
    });
    
    // Update functionality
    $storyCount.prop('disabled', isManual);
    $postsList.find('input[type="checkbox"]')
        .prop('disabled', !isManual);
}
```

### 2. State Management
```javascript
function saveManualOverrideState($block, isManual) {
    return saveBlockState($block, isManual, function() {
        if (!isManual && $block.find('.block-story-count').val() !== 'disable') {
            handleStoryCountChange($block, 
                $block.find('.block-story-count').val());
        }
    });
}
```

## Event System

### 1. Story Count Events
```javascript
$(document).on('change.newsletter', '.block-story-count', function() {
    var $block = $(this).closest('.block-item');
    var storyCount = $(this).val();
    handleStoryCountChange($block, storyCount);
});
```

### 2. Category/Date Range Events
```javascript
$(document).on('change.newsletter', 
    '.block-category, .block-date-range', function() {
    var $block = $(this).closest('.block-item');
    var categoryId = $block.find('.block-category').val();
    var dateRange = $block.find('.block-date-range').val();
    var storyCount = $block.find('.block-story-count').val();
    
    if (categoryId) {
        loadBlockPosts($block, categoryId, dateRange, storyCount);
    }
});
```

## Best Practices

### 1. Content Selection
- Validate category existence
- Check date range validity
- Verify story count limits
- Handle empty results
- Maintain post order
- Respect manual override

### 2. Performance
- Implement pagination
- Cache query results
- Optimize AJAX calls
- Batch post updates
- Minimize DOM operations
- Handle large datasets

### 3. User Experience
- Provide clear feedback
- Maintain visual hierarchy
- Enable easy reordering
- Show loading states
- Handle errors gracefully
- Preserve user selections

## Common Issues and Solutions

### 1. Story Count
- **Issue**: Count not updating
- **Solution**: Event binding
- **Prevention**: State tracking
- **Monitoring**: Count validation

### 2. Post Order
- **Issue**: Order not preserved
- **Solution**: Order tracking
- **Prevention**: Save validation
- **Monitoring**: Order checks

### 3. Manual Override
- **Issue**: State conflicts
- **Solution**: Mode validation
- **Prevention**: State checks
- **Monitoring**: Toggle tracking

## Dependencies
- WordPress Query API
- jQuery UI Sortable
- WordPress AJAX
- WordPress Options API
- Block Management System

## Related Documentation
- [Newsletter Configuration](newsletter-configuration.md)
- [Block Management](block-management.md)
- [Template System](template-system.md)
- [Preview System](preview-system.md) 