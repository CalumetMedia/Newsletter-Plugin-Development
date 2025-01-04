# WYSIWYG Content Persistence Issue - December 30, 2024

## Issue Description
WYSIWYG block content was not persisting after page reloads without explicit saving. The content would be saved correctly but would be missing upon refresh, indicating an issue with how blocks were being retrieved and initialized.

## Root Causes
1. Auto-save functionality was tightly coupled with preview generation
   - Race conditions between save and preview operations
   - Inconsistent state management between operations
   - Lack of proper request tracking

2. Type mismatch in server-side handling of blocks data
   - WordPress's automatic unserialization affecting POST data
   - Incorrect assumptions about data types
   - Missing type validation before operations

3. Inconsistent handling of 'checked' versus 'selected' keys
   - Legacy code using mixed terminology
   - Inconsistent key usage across components
   - Data structure transformation issues

4. Content preservation issues
   - Empty content overwriting valid content
   - Race conditions in concurrent save operations
   - Missing validation of save operations

## Changes Made

### 1. Separation of Concerns
- Created dedicated auto-save.js for handling save operations
  ```javascript
  // Separate auto-save functionality
  function autoSaveBlocks() {
      saveTinyMCEContent();
      const blockData = collectBlockData();
      return sendSaveRequest(blockData);
  }
  ```

- Simplified preview.js to focus on preview generation
  ```javascript
  // Preview-specific functionality
  function generatePreview() {
      const blockStates = collectBlockStates();
      return updatePreviewDisplay(blockStates);
  }
  ```

### 2. Data Type Handling
- Added type checking for POST data
  ```php
  // Server-side type validation
  if (is_string($_POST['blocks'])) {
      $blocks_json = stripslashes($_POST['blocks']);
      $blocks = json_decode($blocks_json, true);
  } else if (is_array($_POST['blocks'])) {
      $blocks = $_POST['blocks'];
  } else {
      wp_send_json_error(['message' => 'Invalid blocks data type']);
  }
  ```

### 3. Content Preservation
- Enhanced save verification
  ```php
  // Multiple validation steps
  $save_result = update_option($option_name, $blocks);
  $verify_save = get_option($option_name);
  if (serialize($verify_save) !== serialize($blocks)) {
      error_log('Save verification failed');
      return false;
  }
  ```

- Added content preservation logic
  ```php
  // Preserve existing content during auto-save
  if ($is_auto_save && !empty($existing_content)) {
      if (empty($new_content) || trim($new_content) === '<p></p>') {
          return $existing_content;
      }
  }
  ```

### 4. Debug Logging
- Added comprehensive logging
  ```php
  // Track critical operations
  error_log("Operation type: " . ($is_auto_save ? 'auto-save' : 'manual save'));
  error_log("Blocks data type: " . gettype($blocks_data));
  error_log("Save operation result: " . var_export($save_result, true));
  error_log("Content preservation: " . ($preserved ? 'true' : 'false'));
  ```

## Testing Verification
1. Content Persistence
   - ✓ Content persists after page reload
   - ✓ Auto-save preserves existing content
   - ✓ Manual save works correctly

2. Data Handling
   - ✓ Proper type checking implemented
   - ✓ Save verification working
   - ✓ Error logging functional

3. Race Conditions
   - ✓ Concurrent saves handled properly
   - ✓ Preview generation doesn't interfere
   - ✓ Request tracking implemented

## Known Limitations
1. WordPress's automatic data transformation
   - May affect POST data handling
   - Requires consistent type checking
   - See Type Handling Implementation Notes in KNOWN_ISSUES.md

2. Legacy Code Compatibility
   - Some files may still use 'selected'
   - Backward compatibility maintained
   - Migration plan needed for full consistency

## Related Changes
1. Files Modified:
   - auto-save.js (new file)
   - preview.js (simplified)
   - ajax-save-blocks.php (enhanced)
   - block-item.php (updated)

2. Documentation Updated:
   - KNOWN_ISSUES.md
   - saving-blocks.md
   - preview-handling.md

## Future Considerations
1. Complete separation of preview and save logic
2. Enhanced error handling system
3. Improved race condition management
4. Standardized key usage across codebase

## Version Information
- Plugin Version: 2.3.2.4.1
- Last Updated: December 30, 2024
- Contributors: [List of contributors] 