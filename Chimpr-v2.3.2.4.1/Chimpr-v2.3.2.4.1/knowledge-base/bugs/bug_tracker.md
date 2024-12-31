# Bug Tracker
**Last Updated**: 2024-12-31
**Status**: Active Monitoring
**Priority**: High

## Current Issues

### 1. WYSIWYG Editor Content Not Saving
**Status**: Active
**Priority**: High
**Component**: Block Management System

#### Related Recent Bug Fixes
- Fixed issue with WYSIWYG content not persisting after refresh (v2.3.2.4.1)
  - Implemented specialized comparison for WYSIWYG blocks
  - Added content normalization using trim() and wp_kses_post()
  - Improved block change detection logic
- Fixed WYSIWYG content loss during drag and drop (v2.3.2.4)
- Fixed WYSIWYG editor display and content persistence issues (v2.3.2.4.1)
  - Improved editor initialization and cleanup process
  - Enhanced textarea management
  - Added proper state tracking during transitions

#### Potential Causes
- TinyMCE editor initialization timing issues
- Content state management during auto-save operations
- Race conditions between manual and auto-save
- Editor instance cleanup during block operations

#### Key Variables & Functions to Check
- `previewUpdatePromise` - Check for conflicts with editor saves
- `autoSaveTimeout` - Verify timing with editor state
- `initWysiwygEditor()` - Editor initialization sequence
- `window.tinymce.get()` - Editor instance management
- `currentContent` storage and restoration logic

#### Investigation Points
1. Editor initialization sequence in `block-manager.js`
2. Content preservation during auto-save in `ajax-save-blocks.php`
3. TinyMCE event handlers for content changes
4. State management during block reordering
5. Memory management for editor instances

### 2. Add Newsletter Navigation Issue
**Status**: Active
**Priority**: Medium
**Component**: Navigation System

#### Related Recent Bug Fixes
No directly related recent fixes found in KNOWN_ISSUES.md

#### Potential Causes
- WordPress admin_url() parameter handling
- AJAX response processing
- Redirect handling after newsletter creation
- Permission verification sequence

#### Key Variables & Functions to Check
- `newsletter_stories_handle_form_submission()` - Form processing
- `wp_redirect()` calls and parameters
- `admin_url()` construction
- Newsletter slug generation and validation

#### Investigation Points
1. Form submission handler in `form-handlers.php`
2. Redirect logic in success handlers
3. URL construction for settings page
4. Session state management during creation
5. Permission verification sequence

### 3. PDF Generation System Failure
**Status**: Active
**Priority**: High
**Component**: PDF Generation System

#### Related Recent Bug Fixes
- Fixed PDF Link Block Preview Issue (v2.3.2.4.1)
  - Updated preview generation to handle PDF Link blocks
  - Enhanced block initialization
  - Added proper spacing in preview generation
  - Fixed template content retrieval

#### Potential Causes
- Memory allocation issues
- Template processing errors
- TCPDF library conflicts
- Content sanitization problems
- Resource path resolution

#### Key Variables & Functions to Check
- `Newsletter_PDF_Generator` class methods
- `memory_limit` and `max_execution_time` settings
- Template loading functions
- Resource path constants
- Error logging configuration

#### Investigation Points
1. Memory usage during generation
2. Template processing sequence
3. Resource path resolution
4. Error logging in `class-newsletter-pdf-generator.php`
5. Content sanitization methods
6. TCPDF configuration settings

### 4. Campaign Table Performance Issues
**Status**: Active
**Priority**: High
**Component**: Campaign Management System

#### Related Recent Bug Fixes
No directly related recent fixes found in KNOWN_ISSUES.md, but there are related template and preview content fixes that might impact performance:
- Fixed Preview Content Generation Issue (v2.3.2.4.1)
  - Fixed template content retrieval
  - Added proper template fallback logic
  - Improved error handling for post processing

#### Potential Causes
- Inefficient database queries
- Large post meta tables
- Unindexed campaign data
- Resource-intensive status checks
- Excessive API calls to Mailchimp

#### Key Variables & Functions to Check
- Campaign table query construction
- Database indices on campaign tables
- Mailchimp API call frequency
- Page load sequence
- Data caching implementation

#### Investigation Points
1. Database query optimization in campaign loading
2. Index usage on campaign tables
3. Mailchimp API interaction patterns
4. Campaign status caching strategy
5. Pagination implementation
6. Data structure for campaign storage

## Notes
- All issues are being actively monitored
- Priority levels may change based on user impact
- Regular updates will be provided as investigation progresses
- Additional issues will be added as identified

## Related Documentation
- [Newsletter Preview System](../features/newsletter-preview-system.md)
- [Block Management](../features/block-management.md)
- [PDF Generation](../features/pdf-generation.md)
- [Campaign Management](../features/newsletter-campaigns.md) 