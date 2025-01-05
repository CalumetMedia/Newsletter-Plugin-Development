# Admin Interface Variables
**Component**: WordPress Admin Interface
**Last Updated**: January 2, 2025

## Dashboard Variables
```
Variable: $newsletter_list
Type: array
Scope: local
Files: admin/admin-page-dashboard.php
Purpose: List of all newsletters and their names
Structure: {
    newsletter_slug: newsletter_name
}
Potential Issues: None identified
```

```
Variable: $wp_timezone
Type: string
Scope: local
Files: admin/admin-page-dashboard.php
Source: get_option('timezone_string')
Default: 'UTC'
Purpose: WordPress timezone setting for date display
Potential Issues: Potential mismatch with PHP default timezone
```

## Campaign Management Variables
```
Variable: $current_page
Type: integer
Scope: local
Files: admin/admin-page-campaigns.php
Purpose: Current page number for campaign pagination
Default: 1
```

```
Variable: $items_per_page
Type: integer
Scope: local
Files: admin/admin-page-campaigns.php
Purpose: Number of campaigns to display per page
Default: 10
```

## Settings Variables
```
Variable: newsletter_settings_active_tab
Type: string
Scope: WordPress option
Files: admin/newsletter-settings-tabs.php
Purpose: Stores the currently active settings tab
Default: 'general'
```

## Individual Newsletter Settings
```
Variable: newsletter_is_ad_hoc_{$newsletter_slug}
Type: integer (boolean)
Scope: WordPress option
Files: 
- admin/admin-page-dashboard.php
- admin/individual-settings.php
Purpose: Flags whether a newsletter is ad-hoc or scheduled
Default: 0
Potential Issues: Dynamic option name based on slug
```

```
Variable: newsletter_send_days_{$newsletter_slug}
Type: array
Scope: WordPress option
Files: 
- admin/admin-page-dashboard.php
- admin/individual-settings.php
Purpose: Days of week for scheduled sending
Structure: Array of day names
Potential Issues: Dynamic option name based on slug
```

```
Variable: newsletter_send_time_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: 
- admin/admin-page-dashboard.php
- admin/individual-settings.php
Purpose: Time of day for scheduled sending
Format: 'HH:MM'
Potential Issues: 
- Dynamic option name based on slug
- Time format validation needed
```

## Template Management Variables
```
Variable: $template_types
Type: array
Scope: local
Files: admin/templates.php
Purpose: Available template types for selection
Values: ['newsletter', 'block', 'pdf', 'header', 'footer']
```

## Known Issues
1. Timezone Handling:
   - Multiple timezone settings (WordPress, PHP, server)
   - Potential for inconsistent time display
   - Need for centralized timezone management

2. Dynamic Settings:
   - Many settings use dynamic option names with newsletter slugs
   - No validation between settings and newsletter existence
   - Potential for orphaned settings

3. Pagination State:
   - Campaign list pagination relies on GET parameters
   - No state persistence between page loads
   - Consider adding user preferences for items per page

## Dependencies
- WordPress admin interface
- WordPress options API
- Mailchimp API for campaign management
- Template system for template management
``` 