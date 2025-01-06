# Mailchimp Integration Variables
**Component**: Mailchimp API Integration
**Last Updated**: January 2, 2025

## API Configuration Variables
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

## WordPress Options
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

## Newsletter-Specific Settings
```
Variable: newsletter_from_name_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific sender name override
Potential Issues: Dynamic option name based on slug
```

```
Variable: newsletter_reply_to_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific reply-to email override
Potential Issues: Dynamic option name based on slug
```

```
Variable: newsletter_target_tags_{$newsletter_slug}
Type: array
Scope: WordPress option
Files: includes/class-mailchimp-api.php
Purpose: Newsletter-specific Mailchimp segment tags
Potential Issues: Dynamic option name based on slug
```

## Campaign Variables
```
Variable: $campaign_data
Type: array
Scope: local
Files: includes/class-mailchimp-api.php
Purpose: Stores campaign creation/update data
Structure: {
    type: string,
    recipients: {
        list_id: string,
        segment_opts?: object
    },
    settings: {
        subject_line: string,
        title: string,
        from_name: string,
        reply_to: string,
        to_name: string,
        authenticate: boolean,
        auto_footer: boolean,
        inline_css: boolean
    }
}
```

## Known Issues
1. API Key Management:
   - No encryption for stored API key
   - Consider implementing secure storage

2. Dynamic Settings:
   - Newsletter-specific settings use dynamic option names
   - Potential for orphaned settings
   - Need for cleanup on newsletter deletion

3. Error Handling:
   - API errors not consistently logged
   - Need for better error reporting
   - Consider implementing retry logic

## Dependencies
- WordPress options API
- WordPress HTTP API
- Newsletter core system
- Cron automation system
``` 