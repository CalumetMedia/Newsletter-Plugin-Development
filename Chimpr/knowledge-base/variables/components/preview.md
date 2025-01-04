# Preview System Variables
**Component**: Newsletter Preview System
**Last Updated**: January 2, 2025

## Core Variables
```
Variable: $preview_state
Type: object
Scope: global (JavaScript)
Files: assets/js/preview.js
Purpose: Manages preview panel state
Structure: {
    isLoading: boolean,
    lastUpdate: number,
    content: string,
    error: string | null
}
```

```
Variable: $preview_content
Type: string
Scope: local
Files: includes/ajax/ajax-preview.php
Purpose: Stores generated preview HTML
Source: newsletter_generate_preview_content()
```

## WordPress Options
```
Variable: newsletter_preview_settings
Type: array
Scope: WordPress option
Files: includes/preview-settings.php
Purpose: Global preview system settings
Structure: {
    auto_refresh: boolean,
    refresh_interval: number,
    cache_duration: number
}
```

## AJAX Variables
```
Variable: preview_nonce
Type: string
Scope: global (JavaScript)
Files: assets/js/preview.js
Purpose: Security nonce for preview AJAX calls
Generation: wp_create_nonce('newsletter_preview')
```

```
Variable: preview_ajax_data
Type: object
Scope: local (JavaScript)
Files: assets/js/preview.js
Purpose: Data structure for preview AJAX requests
Structure: {
    action: string,
    nonce: string,
    newsletter_slug: string,
    blocks: array
}
```

## State Management Variables
```
Variable: $block_preview_cache
Type: array
Scope: global
Files: includes/preview-cache.php
Purpose: Caches preview content by block hash
Structure: {
    block_hash: {
        content: string,
        timestamp: number
    }
}
```

```
Variable: $preview_errors
Type: array
Scope: global
Files: includes/preview-error-handler.php
Purpose: Tracks preview generation errors
Structure: Array of error messages and codes
```

## Template Variables
```
Variable: $preview_template
Type: string
Scope: local
Files: includes/preview-renderer.php
Purpose: Template for preview container
Default: From preview-container.php template file
```

## JavaScript Event Variables
```
Variable: previewEvents
Type: object
Scope: global (JavaScript)
Files: assets/js/preview.js
Purpose: Custom event handlers for preview updates
Structure: {
    onUpdate: function[],
    onError: function[],
    onStateChange: function[]
}
```

## Known Issues
1. State Management:
   - Preview state can become out of sync with editor
   - No automatic recovery from failed updates
   - Need for better state synchronization

2. Performance:
   - Large content previews can be slow
   - Cache implementation needs optimization
   - Consider implementing partial updates

3. Error Handling:
   - Error messages not always user-friendly
   - Some errors not properly propagated
   - Need for better error recovery

4. Template Integration:
   - Preview templates may not match final output
   - Template changes not immediately reflected
   - Need for template validation system

## Dependencies
- WordPress AJAX API
- Block system
- Template system
- Cache system
``` 