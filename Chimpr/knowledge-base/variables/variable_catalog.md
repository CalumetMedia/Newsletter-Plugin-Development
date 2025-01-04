# Chimpr Plugin Variable Catalog
**Version**: 2.3.2.4.1
**Last Updated**: January 2, 2025

## Overview
This document catalogs all variables used throughout the Chimpr plugin, their types, locations, and potential conflicts or issues.

## Structure
Each variable entry follows this format:
```
Variable: [name]
Type: [data type]
Scope: [global/local/class property]
Files: [list of files where used]
Purpose: [brief description]
Potential Issues: [any identified conflicts or concerns]
```

## Categories
Variables are organized into the following categories:
1. Configuration Variables
2. Template Variables
3. Block System Variables
4. State Management Variables
5. Integration Variables
6. PDF Generation Variables
7. Preview System Variables
8. Admin Interface Variables

## Component Documentation
This catalog has been split into component-specific documentation for better organization and maintenance:

1. [Core Variables](components/core.md)
   - Plugin configuration
   - Global instances
   - Core WordPress integration

2. [Block System](components/blocks.md)
   - Block data structures
   - State management
   - Editor integration

3. [Template System](components/templates.md)
   - Template types
   - Template data structures
   - Template inheritance

4. [Mailchimp Integration](components/mailchimp.md) [To be created]
   - API configuration
   - Campaign management
   - List synchronization

5. [PDF Generation](components/pdf.md) [To be created]
   - PDF templates
   - Generation settings
   - TCPDF integration

6. [Cron Automation](components/cron.md) [To be created]
   - Scheduling variables
   - Automation settings
   - Time management

7. [Admin Interface](components/admin.md) [To be created]
   - UI state management
   - Form handling
   - User preferences

8. [Preview System](components/preview.md) [To be created]
   - Preview generation
   - State synchronization
   - Display settings

## Variables To Be Documented

### Configuration Constants
```
Variable: NEWSLETTER_PLUGIN_DIR
Type: string
Scope: global constant
Files: 
- newsletter-plugin.php
- Used across multiple include files
Purpose: Defines the absolute path to the plugin directory
Potential Issues: None identified
```

```
Variable: NEWSLETTER_PLUGIN_URL
Type: string
Scope: global constant
Files: 
- newsletter-plugin.php
- Used in admin scripts
Purpose: Defines the URL to the plugin directory for asset loading
Potential Issues: None identified
```

```
Variable: NEWSLETTER_PLUGIN_VERSION
Type: string
Scope: global constant
Files: 
- newsletter-plugin.php
- Used in script/style enqueuing
Current Value: '2.3.2.4'
Purpose: Tracks plugin version and used for cache busting
Potential Issues: Version mismatch with README.md (shows 2.3.2.4.1)
```

### Class Instances
```
Variable: $pdf_logger
Type: object (Newsletter_PDF_Logger)
Scope: global
Files: newsletter-plugin.php
Purpose: Instance of PDF logging system
Potential Issues: None identified
```

```
Variable: $mailchimp
Type: object (Newsletter_Mailchimp_API)
Scope: global
Files: newsletter-plugin.php
Purpose: Instance of Mailchimp API integration
Potential Issues: None identified
```

### Function Parameters
```
Variable: $hook
Type: string
Scope: local (function parameter)
Files: newsletter-plugin.php:my_newsletter_enqueue_editor_scripts()
Purpose: WordPress admin page hook name
Potential Issues: None identified
```

### Block System Variables
```
Variable: $blocks
Type: array
Scope: function parameter
Files: 
- includes/helpers.php:get_newsletter_posts()
- includes/helpers.php:newsletter_generate_preview_content()
Purpose: Stores newsletter block data structure
Structure: {
    type: string,
    show_title: boolean,
    title: string,
    block_title: string,
    template_id: string,
    posts?: array,
    html?: string,
    wysiwyg?: string
}
Potential Issues: None identified
```

```
Variable: $newsletter_data
Type: array
Scope: local
Files: includes/helpers.php:get_newsletter_posts()
Purpose: Processed and sanitized block data
Potential Issues: None identified
```

```
Variable: $block_data
Type: array
Scope: local
Files: includes/helpers.php:get_newsletter_posts()
Purpose: Individual block processing container
Potential Issues: None identified
```

### Template System Variables
```
Variable: $available_templates
Type: array
Scope: local
Files: includes/helpers.php:newsletter_generate_preview_content()
Source: get_option('newsletter_templates')
Purpose: Stores available newsletter templates
Potential Issues: None identified
```

```
Variable: $header_template_id
Type: string
Scope: local
Files: includes/helpers.php:newsletter_generate_preview_content()
Source: get_option("newsletter_header_template_$newsletter_slug")
Purpose: Identifies selected header template
Potential Issues: None identified
```

```
Variable: $newsletter_slug
Type: string
Scope: function parameter
Files: 
- includes/helpers.php:newsletter_generate_preview_content()
- includes/helpers.php:newsletter_handle_blocks_form_submission_non_ajax()
Purpose: Unique identifier for newsletter instance
Potential Issues: None identified
```

### WordPress Options
```
Variable: newsletter_templates
Type: array
Scope: WordPress option
Files: includes/helpers.php
Purpose: Stores all newsletter templates
Potential Issues: None identified
```

```
Variable: newsletter_header_template_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/helpers.php
Purpose: Stores header template selection for specific newsletter
Potential Issues: Dynamic option name based on slug - needs careful handling
```

### Mailchimp Integration Variables
```
Variable: $api_key
Type: string
Scope: private class property
Files: includes/class-mailchimp-api.php
Source: get_option('mailchimp_api_key')
Purpose: Stores Mailchimp API authentication key
Potential Issues: None identified
```

```
Variable: $api_endpoint
Type: string
Scope: private class property
Files: includes/class-mailchimp-api.php
Default: 'https://[dc].api.mailchimp.com/3.0/'
Purpose: Base URL for Mailchimp API requests
Potential Issues: None identified
```

```
Variable: $datacenter
Type: string
Scope: private class property
Files: includes/class-mailchimp-api.php
Purpose: Stores Mailchimp datacenter identifier from API key
Potential Issues: None identified
```

### WordPress Options (Mailchimp Related)
```
Variable: mailchimp_api_key
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Stores Mailchimp API key
Potential Issues: None identified
```

```
Variable: mailchimp_list_id
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Stores default Mailchimp list/audience ID
Potential Issues: None identified
```

```
Variable: mailchimp_from_name
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Global default sender name for campaigns
Potential Issues: None identified
```

```
Variable: mailchimp_reply_to
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Global default reply-to email for campaigns
Potential Issues: None identified
```

```
Variable: newsletter_from_name_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific sender name override
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_reply_to_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific reply-to email override
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_target_tags_{$newsletter_slug}
Type: array
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific Mailchimp segment tags
Potential Issues: Dynamic option name based on slug - needs careful handling
```

### PDF Generation Variables
```
Variable: $template_id
Type: string
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Stores the ID of the PDF template to use
Default: Value from get_option("newsletter_pdf_template_id_$newsletter_slug", 'default')
Potential Issues: None identified
```

```
Variable: $newsletter_slug
Type: string
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Identifies the newsletter instance for PDF generation
Potential Issues: None identified
```

### PDF System Constants
```
Variable: PDF_PAGE_ORIENTATION
Type: string constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines PDF page orientation
Potential Issues: Dependency on TCPDF constants
```

```
Variable: PDF_UNIT
Type: string constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines measurement unit for PDF
Potential Issues: Dependency on TCPDF constants
```

```
Variable: PDF_PAGE_FORMAT
Type: string/array constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines page size for PDF
Potential Issues: Dependency on TCPDF constants
```

### WordPress Options (PDF Related)
```
Variable: newsletter_pdf_template_id_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Stores PDF template selection for specific newsletter
Default: 'default'
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_templates
Type: array
Scope: WordPress option
Files: 
- includes/pdf/class-newsletter-pdf-generator.php
- includes/helpers.php
Purpose: Stores all newsletter templates including PDF templates
Structure: {
    template_id: {
        content: string,
        html: string
    }
}
Potential Issues: 
- Used across multiple components (PDF and HTML generation)
- Structure assumptions may vary between components
```

### Cron Automation Variables
```
Variable: LOOKAHEAD_MINUTES
Type: integer constant
Scope: class constant
Files: includes/cron-automation.php
Value: 60
Purpose: Defines how far ahead to look for newsletters to schedule
Potential Issues: None identified
```

```
Variable: $instance
Type: object (Newsletter_Cron_Automation)
Scope: private static class property
Files: includes/cron-automation.php
Purpose: Singleton instance of cron automation
Potential Issues: None identified
```

### WordPress Options (Automation Related)
```
Variable: newsletter_list
Type: array
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Stores all newsletter slugs and names
Structure: {
    newsletter_slug: newsletter_name
}
Potential Issues: None identified
```

```
Variable: newsletter_is_ad_hoc_{$newsletter_slug}
Type: integer (boolean)
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Flags whether a newsletter is ad-hoc or scheduled
Default: 0
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_send_days_{$newsletter_slug}
Type: array
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Stores days of week for newsletter sending
Structure: Array of day names (e.g., ['Monday', 'Wednesday'])
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_send_time_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Stores time of day for newsletter sending
Format: 'HH:MM'
Potential Issues: 
- Dynamic option name based on slug - needs careful handling
- Time format validation needed
```

```
Variable: newsletter_subject_line_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Stores default subject line template
Default: "Newsletter ($newsletter_slug)"
Potential Issues: Dynamic option name based on slug - needs careful handling
```

```
Variable: newsletter_blocks_{$newsletter_slug}
Type: array
Scope: WordPress option
Files: includes/cron-automation.php
Purpose: Stores newsletter content blocks
Potential Issues: 
- Dynamic option name based on slug - needs careful handling
- Structure must match block system expectations
```

### WordPress Actions
```
Variable: newsletter_automated_send
Type: action hook
Scope: global
Files: includes/cron-automation.php
Purpose: Main cron hook for automated sending
Schedule: Daily
Potential Issues: None identified
```

```
Variable: pre_newsletter_automated_send
Type: action hook
Scope: global
Files: includes/cron-automation.php
Purpose: Fired before automated send process
Parameters: $newsletter_slug
Potential Issues: None identified
```

```
Variable: post_newsletter_automated_send
Type: action hook
Scope: global
Files: includes/cron-automation.php
Purpose: Fired after automated send process
Parameters: $newsletter_slug
Potential Issues: None identified
```

## Known Variable Conflicts
1. Version number mismatch:
   - README.md shows version 2.3.2.4.1
   - NEWSLETTER_PLUGIN_VERSION constant shows 2.3.2.4
   - This should be synchronized to prevent confusion

2. Block Type Consistency:
   - Block types are defined as strings ('content', 'html', 'wysiwyg', 'pdf_link')
   - These should be consistently used across the codebase
   - Recommend defining these as constants to prevent typos

3. Dynamic Option Names:
   - Multiple options use dynamic suffixes based on $newsletter_slug
   - Potential for naming conflicts or data loss if slugs contain special characters
   - Recommend implementing a validation system for newsletter slugs
   - Consider using a more structured storage approach (e.g., single option with array)

4. Template Storage:
   - newsletter_templates option is used by both PDF and HTML generation
   - Different components may assume different template structures
   - Recommend splitting into separate options or standardizing structure
   - Consider implementing template type validation

5. TCPDF Dependencies:
   - PDF generation relies on TCPDF constants
   - No fallback values defined
   - Recommend implementing default values
   - Consider wrapping TCPDF constants in plugin constants

6. Time Handling:
   - Multiple components handle time zones
   - Inconsistent use of UTC vs local time
   - Recommend standardizing time handling
   - Consider implementing central time utility class

7. Newsletter List Management:
   - newsletter_list option contains core newsletter data
   - Many dynamic options depend on slugs from this list
   - No validation between list and dependent options
   - Recommend implementing referential integrity checks

## Variable Naming Conventions
- Configuration variables: `CHIMPR_*`
- Template variables: `template_*`
- Block variables: `block_*`
- State variables: `state_*`
- Integration variables: `integration_*`

## Next Steps
1. Analyze admin directory
2. Document includes directory
3. Review template system
4. Catalog PDF generation variables
5. Document integration variables

## Notes
- This catalog will be updated as new variables are identified
- Special attention will be paid to variable scope and potential conflicts
- Documentation will include both PHP and JavaScript variables