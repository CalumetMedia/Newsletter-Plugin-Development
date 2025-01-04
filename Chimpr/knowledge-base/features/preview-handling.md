# Newsletter Preview Handling

## Critical Components

### 1. Post Selection
- ALWAYS use 'checked' as the key for post selection status
- Never introduce alternative keys (like `selected`) as this causes preview failures
- Maintain key consistency throughout the entire data flow

### 2. Required Functions
The following functions must be preserved in `preview.js`:
```javascript
// Core functionality
collectBlockStates()    // Collects all block data
collectPostStates()     // Collects post selection state
generatePreview()       // Generates preview HTML
updatePreviewDisplay()  // Updates preview container
saveBlocks()           // Manual save functionality

// State management
resetPreviewState()     // Resets preview state
saveAllEditors()       // Saves TinyMCE editors
initializePreview()    // Initializes preview functionality
autoSaveAndUpdatePreview() // Handles auto-save
debouncedAutoSave()    // Debounced auto-save
```

### 3. State Management
Required variables:
```javascript
previewUpdatePromise    // Tracks current preview update
globalUpdateInProgress  // Prevents concurrent updates
previewTimeout         // Handles update debouncing
activeRequests         // Tracks active AJAX requests
```

## Data Flow
1. User selects posts in UI
2. Frontend collects post states with 'checked' key
3. AJAX sends data maintaining 'checked' key
4. Backend processes maintaining 'checked' key
5. Preview generation uses 'checked' key
6. Database stores with 'checked' key

## Testing Requirements
1. Verify post selection persistence
2. Confirm preview displays all selected posts
3. Test auto-save functionality
4. Ensure preview updates when posts are selected/deselected

## Common Issues
- Posts not appearing in preview despite being selected
- Preview not updating after selection changes
- Post selection not persisting after save
- Preview generation timing out

## Prevention Checklist
1. Verify key consistency in data flow
2. Check all required functions present
3. Validate state management variables
4. Test preview generation with selections
5. Confirm data structure preservation

## Related Files
- `preview.js`
- `ajax-generate-preview.php`
- `helpers.php`
- `block-manager.js`

## Warning
DO NOT:
1. Change key names without full impact analysis
2. Remove or modify critical functions
3. Alter state management variables
4. Change data structure without validation 