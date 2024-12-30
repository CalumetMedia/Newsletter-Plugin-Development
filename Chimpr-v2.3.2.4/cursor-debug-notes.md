# Cursor Debug Notes

## Debug Note Creation Prompt
```
@cursor-debug-notes.md

This command will:
1. Add a new concise debug entry to cursor-debug-notes.md with:
   - Timestamp
   - Brief issue description
   - 2-3 key investigation points (prefixed with 🔍)
   - Core problems solved (bullet points only)
   - Core problems remaining (bullet points only)
   - Link to detailed log file

2. Create a new detailed debug log file in debug-logs/YYYY-MM-DD-HHMM-brief-description.md containing:
   - Full timestamp
   - Detailed issue summary
   - Debug data and logs
   - Root cause analysis
   - Code changes with examples
   - Verification steps
   - Related issues
   - Future considerations
   - Detailed commands and error paths

Note: Keep the main notes file concise. All implementation details, code examples, and extended explanations should go in the separate log file.

## Key Files
- `assets/js/preview.js` - Preview generation and management
- `assets/js/block-manager.js` - Block initialization and state
- `admin/partials/render-preview.php` - Server-side preview
- `includes/ajax/ajax-generate-preview.php` - AJAX preview endpoint
- `includes/helpers.php` - Core helper functions

## Debug Logs
### 2024-12-29
- [21:43 UTC] Save operation failing, manual mode issues - [details](debug-logs/2024-12-29-2143-save-functionality.md)
  - 🔍 Error: `Failed to save blocks`
  - 🔍 Data format mismatch between frontend/backend
  - 🔍 Option saving strategy needed revision
  
Problems Solved:
- Fixed basic save operation functionality
- Corrected data format handling
- Improved error reporting

Problems Remaining:
- Manual mode state persistence
- Checkbox state consistency
- Save operation performance

### 2024-12-30
- [23:15 UTC] Manual mode checkbox persistence issue - [details](debug-logs/2024-12-30-2315-checkbox-persistence.md)
  - 🔍 Unchecked posts reappearing as checked after reload
  - 🔍 Mismatch between frontend checkbox state and backend storage
  - 🔍 Legacy code preserving old selections causing state conflicts

Problems Solved:
- Fixed checkbox state preservation
- Corrected frontend/backend state sync
- Removed legacy state conflicts

Problems Remaining:
- Manual mode toggle issues
- State inconsistency after preview
- Multiple save operation conflicts

### 2024-12-31
- [03:45 UTC] Checkbox state persistence investigation - [details](debug-logs/2024-12-31-0345-checkbox-persistence.md)
  - 🔍 Checkbox states not persisting in manual mode
  - 🔍 Data structure mismatch between save and load operations
  - 🔍 Inconsistent handling of checked/unchecked values

Problems Solved:
- Standardized checkbox state handling
- Fixed data structure consistency
- Improved state validation

Problems Remaining:
- Manual mode toggle edge cases
- Save operation atomicity
- State recovery mechanisms

### 2024-12-31
- [04:30 UTC] Story Selection and Manual Override Promise Chain Fix - [details](debug-logs/2024-12-31-0430-story-selection-promise-fix.md)
  - 🔍 Story count selector affecting manual override checkbox
  - 🔍 Callback/Promise inconsistency causing errors
  - 🔍 State management issues in toggle operations

Problems Solved:
- Fixed story count selector specificity
- Standardized Promise-based async handling
- Corrected manual override toggle behavior
- Improved error handling and logging

Problems Remaining:
- Performance optimization opportunities
- State cleanup on error edge cases
- Long-term state persistence 