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

### WYSIWYG Content Not Persisting After Refresh (v2.3.2.4.1)
**Issue**: WYSIWYG block content was not persisting after page reloads, particularly when blocks were reordered or modified.
**Root Cause**: Block comparison logic was too strict, using direct serialization comparison which failed due to minor HTML formatting differences.
**Solution**: 
1. Implemented specialized comparison for WYSIWYG blocks:
   - Normalize content using trim() and wp_kses_post()
   - Compare normalized content instead of raw serialization
   - Added detailed logging for content changes
2. Improved block change detection:
   - Check block count changes
   - Compare block types separately
   - Handle WYSIWYG content with special comparison
   - Maintain strict comparison for other block types

**Critical Implementation Notes**:
1. Block Comparison Rules:
   ```php
   // WYSIWYG blocks - normalize and compare content
   if ($block['type'] === 'wysiwyg') {
       $current = trim(wp_kses_post($current_content));
       $new = trim(wp_kses_post($new_content));
       $changed = ($current !== $new);
   }
   // Other blocks - use strict serialization
   else {
       $changed = (serialize($current) !== serialize($new));
   }
   ```

2. Change Detection Hierarchy:
   - Block count changes must be checked first
   - Block type changes indicate structural modification
   - Content-specific comparison for WYSIWYG
   - Index preservation for block order

3. Prevention Measures:
   - Never use direct serialization for WYSIWYG content
   - Always normalize before comparison
   - Preserve intentional whitespace
   - Track content length changes
   - Verify block count consistency
   - Maintain block type integrity
   - Handle empty content cases
   - Verify after every save
   - Compare normalized content
   - Log all state changes

**Details**: See `/knowledge-base/2024/12/wysiwyg-persistence-issue-20241230-1515.md`

### Blocks Save Operation Failing (v2.3.2.4.1)
**Issue**: Newsletter blocks were failing to save, particularly with WYSIWYG content.
**Root Cause**: 
1. Strict serialization comparison failed to handle HTML formatting variations
2. Block comparison didn't account for intentional whitespace/formatting differences
**Solution**: 
1. Implemented block-type-specific comparison logic
2. Added normalized content comparison for WYSIWYG blocks
3. Maintained strict comparison for non-WYSIWYG blocks
4. Enhanced logging to track block changes
**Details**: See `/knowledge-base/features/saving-blocks.md` and `/knowledge-base/2024/12/blocks-save-issue-20241230-1514.md`

### Story Count Selection Not Updating (v2.3.2.4.1)
**Issue**: Story count selection changes were not immediately reflected in the UI, only after save and reload.
**Root Cause**: Event handlers for story count changes were not properly bound and state management was inconsistent.
**Solution**: 
1. Implemented proper event handling with namespaced events
2. Added state management for story count changes
3. Ensured proper initialization of story count on block creation
4. Added manual override mode handling
**Details**: See `/knowledge-base/2024/12/story-count-selection-issue-20241230-2300.md`

### Preview Content Generation Issue (v2.3.2.4.1)
**Issue**: Preview content was not displaying stories due to incorrect template content retrieval.
**Root Cause**: 
1. Template content was being accessed using incorrect array key ('content' instead of 'html')
2. Template variable replacements were using incorrect case (uppercase instead of lowercase)
3. Missing proper fallback for template content when not found
**Solution**: 
1. Fixed template content retrieval to use correct 'html' key
2. Restored proper template variable case handling
3. Added proper template fallback logic
4. Restored thumbnail conditional handling
5. Added comprehensive error handling for post processing
**Details**: See `/knowledge-base/2024/12/preview-content-issue-20241230-2330.md`

### Critical Template Variable Usage - v2.3.2.4.1
This is a critical issue affecting template content handling.

#### Template Variable Standards:
1. Variable Case:
   - Always use lowercase for template variables (e.g., '{title}', '{content}')
   - Never use uppercase variants (e.g., '{TITLE}', '{CONTENT}')

2. Template Content Storage:
   - Templates must store content in 'html' key
   - Never use alternative keys like 'content' or 'template'

3. Required Template Variables:
   ```
   {title} - Post title
   {content} - Post content
   {thumbnail_url} - Featured image URL
   {permalink} - Post URL
   {excerpt} - Post excerpt
   {author} - Post author name
   {publish_date} - Post publication date
   {categories} - Post categories
   ```

4. Conditional Tags:
   ```
   {if_thumbnail}...{/if_thumbnail} - Content shown only if thumbnail exists
   ```

#### Prevention Measures:
1. Template Validation:
   - Always verify template exists before use
   - Check for 'html' key in template data
   - Provide proper fallback template
   - Log template retrieval failures

2. Variable Replacement:
   - Use consistent lowercase variables
   - Maintain all required variables
   - Preserve conditional logic
   - Handle missing data gracefully

3. Error Prevention:
   - Validate template structure
   - Check template content exists
   - Verify variable replacements
   - Log processing errors

**Details**: See `/knowledge-base/2024/12/preview-content-issue-20241230-2330.md`

### WYSIWYG Editor Initialization Issue (v2.3.2.4.1)
**Issue**: WYSIWYG editor window was not initializing when changing block type, requiring a save and refresh.
**Root Cause**: 
1. Editor initialization was not properly handling the block type change event
2. Existing editor instances were not being properly cleaned up
3. DOM elements were not fully ready when editor was being initialized

**Solution**: 
1. Implemented proper cleanup of existing editor instances before initialization
2. Added AJAX-based content loading for WYSIWYG blocks
3. Improved editor initialization timing with proper delays
4. Added explicit visibility handling for editor elements
5. Enhanced error handling and state management during type changes

**Critical Implementation Notes**:
1. Editor Cleanup:
   - Remove existing TinyMCE instances
   - Clean up WordPress editor instances
   - Reset DOM element states

2. Content Loading:
   - Load WYSIWYG content via AJAX
   - Preserve existing content during type changes
   - Handle initialization after content load

3. Initialization Timing:
   - Use appropriate delays for DOM readiness
   - Handle visibility states explicitly
   - Manage update states during transitions

**Details**: See `/knowledge-base/2024/12/wysiwyg-init-issue-20241230-2400.md`

### Manual Override Toggle Not Persisting (v2.3.2.4.1)
**Issue**: Manual override checkbox was not staying checked when clicked, immediately reverting to unchecked state.
**Root Cause**: 
1. Multiple save operations were interfering with each other
2. Unnecessary post reloading was causing state to reset
3. Incorrect block comparison in state saving logic
**Solution**: 
1. Simplified manual override toggle handling
2. Removed unnecessary post reloading
3. Fixed block comparison in state saving
4. Streamlined state management
**Details**: See `/knowledge-base/2024/12/manual-override-toggle-issue-20241230-2400.md`

### WYSIWYG Editor Display and Content Persistence (v2.3.2.4.1)
**Issue**: WYSIWYG editor was not displaying properly when switching block types and content was not being preserved.
**Root Cause**: 
1. Editor initialization timing issues during block type changes
2. Content not being properly preserved during editor cleanup and reinitialization
3. DOM elements not being properly managed during editor transitions

**Solution**: 
1. Improved editor initialization and cleanup process:
   ```javascript
   // Store existing content before cleanup
   var existingContent = '';
   if (tinymce.get(editorId)) {
       existingContent = tinymce.get(editorId).getContent();
       tinymce.execCommand('mceRemoveEditor', true, editorId);
   } else if ($('#' + editorId).length) {
       existingContent = $('#' + editorId).val();
   }
   ```

2. Enhanced textarea management:
   ```javascript
   // Ensure clean textarea with preserved content
   $container.find('textarea').remove();
   $container.append(
       '<textarea id="' + editorId + '" ' +
       'name="blocks[' + blockIndex + '][wysiwyg]" ' +
       'class="wysiwyg-editor-content">' + existingContent + '</textarea>'
   );
   ```

3. Improved initialization timing and state management:
   - Added proper delays for DOM readiness
   - Enhanced cleanup of existing editor instances
   - Implemented proper state tracking during transitions
   - Added comprehensive error handling

**Critical Implementation Notes**:
1. Content Preservation Rules:
   - Always store existing content before cleanup
   - Properly handle both TinyMCE and raw textarea content
   - Ensure content is preserved during editor transitions
   - Verify content after reinitialization

2. Editor Instance Management:
   - Clean up existing instances before creating new ones
   - Handle both TinyMCE and WordPress editor instances
   - Maintain proper state tracking during transitions
   - Prevent duplicate initialization

3. DOM Element Handling:
   - Properly manage textarea visibility
   - Handle editor container display states
   - Ensure proper cleanup of old elements
   - Maintain consistent element structure

**Details**: See `/knowledge-base/2024/12/wysiwyg-display-persistence-issue-20241231-0100.md`

### PDF Link Block Preview Issue (v2.3.2.4.1)
**Issue**: PDF Link blocks were not displaying in preview and fields remained interactable when they should be disabled.
**Root Cause**: 
1. Preview generation was not properly handling PDF Link block types
2. Block initialization was not properly disabling fields for PDF Link blocks
3. Template content was not being correctly pulled for preview generation

**Solution**: 
1. Updated preview generation to handle PDF Link blocks:
   ```php
   // Handle PDF Link blocks like HTML blocks in preview
   if ($block_data['type'] === 'pdf_link' && isset($block_data['html'])) {
       $newsletter_html .= wp_kses_post(wp_unslash($block_data['html']));
   }
   ```

2. Enhanced block initialization:
   - Properly disable fields for PDF Link blocks
   - Maintain template field as only active field
   - Handle field states during save and load

3. Added proper spacing in preview generation:
   ```php
   $newsletter_html .= '<div class="newsletter-block" style="margin-bottom: 20px;">';
   ```

**Critical Implementation Notes**:
1. Field State Rules:
   - Template field must remain active
   - All other fields must be disabled
   - States must persist through save/load
   - Manual override must respect block type

2. Preview Generation:
   - Handle PDF Link blocks like HTML
   - Pull content from templates
   - Maintain proper spacing
   - Preserve block titles

**Details**: See `/knowledge-base/2024/12/pdf-link-preview-issue-20241231-0200.md`

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