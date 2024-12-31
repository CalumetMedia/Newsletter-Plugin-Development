# Known Issues and Critical Considerations - v2.3.2.4

## Issue Summary

### WYSIWYG Preview Not Displaying (v2.3.2.4)
**Issue**: WYSIWYG editor content was not displaying in the preview panel after editing.
**Solution**: Updated preview generation and AJAX handling to properly collect, transmit, and render WYSIWYG content.
**Details**: See `/knowledge-base/2024/12/wysiwyg-preview-issue-20241230-1513.md`

### WYSIWYG Content Lost During Drag and Drop (v2.3.2.4)
**Issue**: WYSIWYG editor content was being lost when blocks were reordered using drag and drop.
**Solution**: Updated block reordering logic to properly preserve and reinitialize WYSIWYG editors.
**Details**: See `/knowledge-base/2024/12/wysiwyg-drag-drop-issue-20241230-1512.md`

### WYSIWYG Content Not Saving (v2.3.2.4)
**Issue**: WYSIWYG editor content was not being preserved after save operations.
**Solution**: Updated block data handling in form submissions and AJAX operations to properly preserve WYSIWYG content.
**Details**: See `/knowledge-base/2024/12/wysiwyg-save-issue-20241230-1511.md`

### Blocks Save Operation Failing (v2.3.2.4)
**Issue**: Newsletter blocks were failing to save, particularly when containing special characters or WYSIWYG content.
**Solution**: Fixed data sanitization and handling in the save operation to prevent double unslashing of content.
**Details**: See `/knowledge-base/features/saving-blocks.md` and `/knowledge-base/2024/12/blocks-save-issue-20241230-1514.md`

### WYSIWYG Content Not Persisting After Refresh (v2.3.2.4)
**Issue**: WYSIWYG block content was not persisting after page reloads without explicit saving, despite being saved correctly.
**Solution**: Separated auto-save from preview generation, fixed type handling in server-side code, and improved content preservation logic.
**Details**: See `/knowledge-base/2024/12/wysiwyg-persistence-issue-20241230-1515.md`

---

## Critical Implementation Issues

### Post Selection Key Usage ('checked' vs 'selected') - v2.3.2.4
This is a critical issue affecting post selection and preview functionality.

#### Function-Level Key Usage:
1. Frontend (JavaScript):
   - `block-manager.js`: Uses 'checked' for UI and data storage
   - `preview.js`: Must use 'checked' consistently (NOT 'selected')

2. Backend (PHP):
   - `get_newsletter_posts()`: Expects and validates 'checked'
   - `newsletter_generate_preview_content()`: Must receive 'checked' (NOT 'selected')
   - Database storage: Uses 'checked' consistently

#### Data Flow Issues:
Frontend (checked) -> AJAX (must stay checked) -> Preview Generation (expects checked) -> Database (stores checked)

#### Critical Mistakes Found:
1. Key Transformation Errors:
   - Preview generation was incorrectly transforming 'checked' to 'selected'
   - AJAX requests were inconsistent in key usage
   - Post data structure was being lost during transformations

2. File-Specific Issues:
   - `preview.js`: Lost critical functions during updates
   - `ajax-generate-preview.php`: Inconsistent key handling
   - `helpers.php`: Incorrect post data transformation

#### Prevention Measures:
1. Key Standardization:
   - ALWAYS use 'checked' as the standard key
   - NEVER introduce 'selected' or alternative keys
   - Maintain key consistency across all data flows

2. Code Review Requirements:
   - Verify key usage in all data transformations
   - Check for complete function preservation during updates
   - Ensure debug logging tracks key usage

3. Testing Requirements:
   - Validate post selection persistence
   - Verify preview generation with selected posts
   - Confirm data structure preservation

#### Related Files:
- `knowledge-base/features/preview-handling.md`
- `knowledge-base/2024/12/preview-failure-20241230-2200.md`

### Critical Functions in preview.js - v2.3.2.4
The following functions MUST be preserved in all updates:
1. `collectBlockStates()`
2. `collectPostStates()`
3. `generatePreview()`
4. `updatePreviewDisplay()`
5. `saveBlocks()`
6. `resetPreviewState()`
7. `saveAllEditors()`
8. `initializePreview()`
9. `autoSaveAndUpdatePreview()`
10. `debouncedAutoSave()`

#### State Management Variables:
- `previewUpdatePromise`
- `globalUpdateInProgress`
- `previewTimeout`
- `activeRequests`

DO NOT remove or modify these without explicit requirements.

### AI Assistant Common Mistakes - v2.3.2.4

#### Data Key Consistency
The AI assistant has a tendency to introduce alternative keys for existing functionality, particularly:
- Using `selected` instead of the standard `checked` for post selection status
- This causes preview failures and data inconsistency
- See `knowledge-base/features/preview-handling.md` for detailed documentation

#### Key Areas to Monitor
1. Post selection status in preview generation
2. Block data structure modifications
3. Any data key renaming or "improvements"

The AI should be explicitly instructed to maintain existing key names and data structures unless there is a specific requirement to change them.

---

## Guidelines for Future AI Assistants

### Core Principles
1. **Preserve Existing Patterns**
   - Never "improve" working code by changing variable names or data structures
   - Maintain existing key names even if alternatives seem more logical
   - Follow the codebase's established patterns and conventions
   - Request previous working versions when fixing regressions

2. **Incremental Changes Only**
   - Make minimal, targeted changes to fix specific issues
   - Never rewrite entire files or functions without explicit instruction
   - Document all changes with clear explanations
   - Compare changes against known working versions

3. **Validation Requirements**
   - Always read and understand existing code before making changes
   - Verify dependencies and function relationships
   - Test data structure preservation throughout the flow
   - Validate against previous working versions when available

### Common Failure Points to Avoid
1. **Data Structure Modifications**
   - Don't rename keys (e.g., 'checked' to 'selected')
   - Don't change array structures or indexing methods
   - Don't modify existing object shapes
   - Don't assume current version is correct without checking history

2. **Function Preservation**
   - Don't remove or modify critical functions
   - Don't change function signatures
   - Don't alter state management variables
   - Don't rewrite working code without reference version

3. **File Management**
   - Don't create new files without explicit instruction
   - Don't move code between files
   - Don't modify file structure
   - Don't proceed without access to all relevant files

### Required Checks Before Changes
1. **Code Analysis**
   - Read all related files completely
   - Understand data flow between components
   - Map dependencies and side effects
   - Request previous working versions for comparison

2. **Change Impact**
   - Document all files affected by changes
   - List all functions modified
   - Identify potential side effects
   - Compare against known working versions

3. **Testing Points**
   - Verify data structure preservation
   - Check function integrity
   - Validate state management
   - Confirm compatibility with previous versions

### Version Comparison Requirements
1. **Before Making Changes**
   - Request previous working version of affected files
   - Identify what specifically worked in the previous version
   - Understand why changes were made from working version
   - Document key differences between versions

2. **During Implementation**
   - Reference working version for correct patterns
   - Maintain critical functionality from previous version
   - Preserve working data structures
   - Keep known working code paths intact

3. **After Changes**
   - Verify changes align with working version patterns
   - Confirm no regressions from working functionality
   - Document any deviations from previous version
   - Explain why changes are safe if different from working version

### When in Doubt
1. Ask for clarification rather than make assumptions
2. Request access to related files if needed
3. Document uncertainties and potential risks
4. Propose minimal changes first
5. **Request previous working versions for comparison**
6. **Compare against known working code before proceeding**

---
*For detailed documentation of issues and their resolutions, see the `/knowledge-base` directory.* 

## Type Handling Implementation Notes (v2.3.2.4)
Added December 30, 2024 based on WYSIWYG persistence fixes:

### Server-Side Data Handling
1. WordPress's automatic unserialization can affect POST data:
   ```php
   // Always check data type before processing
   if (is_string($_POST['blocks'])) {
       $blocks_json = stripslashes($_POST['blocks']);
       $blocks = json_decode($blocks_json, true);
   } else if (is_array($_POST['blocks'])) {
       $blocks = $_POST['blocks'];
   }
   ```

2. Save operation verification must be explicit:
   ```php
   $save_result = update_option($option_name, $blocks);
   $verify_save = get_option($option_name);
   if (serialize($verify_save) !== serialize($blocks)) {
       error_log('Save verification failed');
       return false;
   }
   ```

### Content Preservation Rules
1. During auto-save operations:
   - Check for existing content before overwriting
   - Preserve content if new content is empty/blank
   - Verify save success with explicit checks
   - Log all content state changes

2. Error prevention:
   - Never assume POST data type
   - Always verify save operation success
   - Implement proper request tracking
   - Handle race conditions appropriately

### Debug Logging Requirements
1. Log data types at critical points:
   ```php
   error_log("Blocks data type: " . gettype($blocks_data));
   error_log("Save operation result: " . var_export($save_result, true));
   error_log("Verification result: " . var_export($actually_saved, true));
   ```

2. Track content preservation:
   ```php
   if ($is_auto_save && !empty($existing_content)) {
       error_log("Preserving existing content during auto-save");
       error_log("New content empty: " . (empty($new_content) ? 'true' : 'false'));
   }
   ``` 