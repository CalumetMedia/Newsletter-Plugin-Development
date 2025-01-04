# Preview Generation Failure Incident Report
Date: 2024-12-30 22:00 UTC

## Issue Description
Preview generation failed due to inconsistent key usage between frontend and backend systems, specifically the transformation of post selection keys from 'checked' to 'selected'.

## Root Cause Analysis
1. Data Flow Disruption:
   - Frontend correctly used 'checked' for post selection
   - Data transformation incorrectly introduced 'selected' key
   - Backend expected 'checked' but received 'selected'
   - Preview generation failed due to key mismatch

2. Code Integrity Issues:
   - Critical functions missing from `preview.js`
   - State management variables lost during updates
   - Inconsistent key handling in AJAX processing

## Impact
- Preview generation failed
- Post selections not persisting
- User experience degraded
- Data structure integrity compromised

## Changes Made
1. `preview.js`:
   - Restored all critical functions
   - Maintained consistent use of 'checked' key
   - Preserved state management variables
   - Added proper error handling

2. `ajax-generate-preview.php`:
   - Fixed key handling to maintain 'checked'
   - Added compatibility for both keys temporarily
   - Improved error logging

3. `helpers.php`:
   - Corrected post data transformation
   - Added validation for post selection keys
   - Enhanced debug logging

## Prevention Measures
1. Code Review Requirements:
   - Verify all critical functions present
   - Check key consistency throughout data flow
   - Validate state management preservation

2. Testing Protocol:
   - Verify preview generation with selections
   - Confirm post selection persistence
   - Check data structure integrity

3. Documentation Updates:
   - Added critical function list
   - Documented key usage requirements
   - Updated prevention measures

## Lessons Learned
1. Never modify key names without full impact analysis
2. Maintain complete function lists for critical files
3. Implement thorough validation for data transformations
4. Add comprehensive debug logging
5. Document all critical components and their dependencies

## Related Files
- `preview.js`
- `ajax-generate-preview.php`
- `helpers.php`
- `KNOWN_ISSUES.md` 