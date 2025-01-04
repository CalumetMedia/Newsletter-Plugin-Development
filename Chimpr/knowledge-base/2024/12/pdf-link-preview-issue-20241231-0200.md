# PDF Link Block Preview and Field State Issues
**Date**: 2024-12-31 02:00
**Tags**: #pdf-link #preview #block-type #field-states #template-integration

## Issue Description
PDF Link blocks were not displaying in preview, and fields that should be disabled (story count, manual override) remained interactable when loading saved PDF Link blocks. The template field, which should be the only active field, was not properly maintained as active.

## Diagnosis
1. Preview generation was not handling PDF Link block type
2. Field states were not being properly initialized on block load
3. Template content was not being correctly pulled and processed
4. Block spacing in preview was inconsistent
5. Field states were not persisting through save/load cycles

## Solution
### Files Modified

1. `includes/helpers.php`:
```php
// Updated get_newsletter_posts function to handle PDF Link blocks
function get_newsletter_posts($blocks) {
    // ... existing code ...
    } elseif ($block['type'] === 'pdf_link') {
        $block_data['html'] = isset($block['html']) ? wp_kses_post($block['html']) : '';
    }
    // ... existing code ...
}

// Updated preview generation to handle PDF Link blocks
function newsletter_generate_preview_content($newsletter_slug, $blocks) {
    // ... existing code ...
    } elseif ($block_data['type'] === 'pdf_link' && isset($block_data['html'])) {
        $newsletter_html .= wp_kses_post(wp_unslash($block_data['html']));
    }
    $newsletter_html .= '</div>';
    // ... existing code ...
}
```

2. `assets/js/block-manager.js`:
```javascript
// Updated handleBlockTypeChange function
function handleBlockTypeChange(block) {
    var blockType = block.find('.block-type select').val();
    var isPdfLinkType = blockType === 'pdf_link';
    
    // Disable all fields by default
    block.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle')
        .prop('disabled', true)
        .closest('div')
        .css('opacity', '0.7');
    
    // Enable template for both content and pdf_link types
    block.find('.template-select select')
        .prop('disabled', !(isContentType || isPdfLinkType))
        .closest('div')
        .css('opacity', (isContentType || isPdfLinkType) ? '1' : '0.7');
}

// Updated initializeBlock function
function initializeBlock(block) {
    var blockType = block.find('.block-type select').val();
    if (blockType === 'pdf_link') {
        handleBlockTypeChange(block);
    }
}
```

3. `admin/partials/block-item.php`:
```php
// Updated disabled conditions for fields
<select class="story-count" 
    <?php echo ($block['type'] === 'html' || $block['type'] === 'wysiwyg' || $block['type'] === 'pdf_link') ? 'disabled' : ''; ?>>
    // ... options ...
</select>

<input type="checkbox" class="manual-override-toggle" 
    <?php echo ($block['type'] === 'html' || $block['type'] === 'wysiwyg' || $block['type'] === 'pdf_link') ? 'disabled' : ''; ?>>
```

## Key Learnings
1. **Field State Management**: Properly initialize and maintain field states based on block type
2. **Preview Generation**: Handle different block types consistently in preview
3. **Template Integration**: Properly pull and process template content
4. **State Persistence**: Ensure states persist through save/load cycles
5. **Block Spacing**: Maintain consistent spacing in preview display

## Testing Verification
1. PDF Link blocks display correctly in preview
2. Only template field is interactable
3. Other fields remain disabled after save/load
4. Template content is correctly pulled and displayed
5. Block spacing is consistent
6. States persist through page reloads

## Related Issues
- [WYSIWYG Content Not Saving](wysiwyg-save-issue-20241230-1511.md)
- [Preview Content Generation Issue](preview-content-issue-20241230-2330.md)

## Search Keywords
pdf link, block type, field states, preview generation, template content, state persistence, block initialization, field disabling 