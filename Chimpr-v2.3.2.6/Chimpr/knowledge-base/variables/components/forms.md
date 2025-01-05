# Form and AJAX Variables
**Component**: Form Handling and AJAX Systems
**Last Updated**: January 2, 2025

## Form Handler Variables
```
Variable: $form_data
Type: array
Scope: local
Files: includes/form-handlers.php
Purpose: Stores sanitized form submission data
Structure: {
    newsletter_id: string,
    action: string,
    nonce: string,
    fields: array
}
```

```
Variable: $form_errors
Type: array
Scope: global
Files: includes/form-handlers.php
Purpose: Stores form validation errors
Structure: Array of error messages by field
```

## AJAX Variables
```
Variable: ajax_response
Type: array
Scope: local
Files: includes/ajax-handlers.php
Purpose: Standard AJAX response structure
Structure: {
    success: boolean,
    data: mixed,
    message: string
}
```

```
Variable: $ajax_nonce
Type: string
Scope: global (JavaScript)
Files: includes/admin-scripts.php
Purpose: Security nonce for AJAX requests
Generation: wp_create_nonce('newsletter_ajax')
```

## Post Selection Variables
```
Variable: $selected_posts
Type: array
Scope: local
Files: includes/post-selection.php
Purpose: Stores selected post data
Structure: {
    post_id: {
        checked: boolean,
        order: number,
        override_title: string
    }
}
```

```
Variable: $post_query_args
Type: array
Scope: local
Files: includes/post-selection.php
Purpose: WP_Query arguments for post selection
Structure: WordPress query args object
```

## Utility Variables
```
Variable: $debug_mode
Type: boolean
Scope: global
Files: includes/utilities.php
Purpose: Controls debug output
Source: defined('WP_DEBUG')
```

```
Variable: $log_enabled
Type: boolean
Scope: global
Files: includes/utilities.php
Purpose: Controls error logging
Source: defined('WP_DEBUG_LOG')
```

## Form Settings
```
Variable: newsletter_form_settings
Type: array
Scope: WordPress option
Files: includes/form-handlers.php
Purpose: Global form configuration
Structure: {
    max_fields: number,
    allowed_html: array,
    validation_rules: array
}
```

## Known Issues
1. Form Validation:
   - Inconsistent error handling
   - No client-side validation
   - Need for better sanitization

2. AJAX Security:
   - Nonce verification not consistent
   - Missing capability checks
   - Need for rate limiting

3. Post Selection:
   - Order preservation issues
   - Memory issues with large post sets
   - Need for pagination

4. Error Handling:
   - Debug output in production
   - Inconsistent logging
   - Need for structured error system

## Dependencies
- WordPress AJAX API
- WordPress post system
- Form validation system
- Error logging system
``` 