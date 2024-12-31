# Newsletter Stories Feature Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Management
**Tags**: #stories #blocks #mailchimp #preview #campaign

## Overview
The Newsletter Stories feature provides a comprehensive interface for managing newsletter content through a block-based system. It enables users to create, organize, and preview newsletter content, with integrated Mailchimp campaign management and PDF generation capabilities.

## Core Components

### 1. Campaign Management
- Campaign naming and subject line configuration
- Mailchimp integration for test emails and campaign scheduling
- PDF generation functionality
- Tag targeting for subscriber segmentation

### 2. Block System
- Content blocks for category-based stories
- Advertising blocks for custom HTML content
- Dynamic block reordering
- Template selection per block
- Story count controls

### 3. Preview System
- Real-time preview generation
- Template-based rendering
- Mobile-responsive preview
- Custom header/footer HTML support

## File Structure

### Main Files
1. **`admin/newsletter-stories.php`**
   - Core functionality implementation
   - Form handling and UI rendering
   - Block management
   - Campaign settings

2. **`admin/partials/block-item.php`**
   - Block template rendering
   - Individual block settings
   - Block type controls

3. **`admin/partials/render-preview.php`**
   - Preview generation
   - Template processing
   - Content assembly

### Supporting Files
1. **`includes/helpers.php`**
   - Utility functions
   - Data processing
   - Option management

2. **`includes/form-handlers.php`**
   - Form submission processing
   - Data validation
   - Security checks

## Key Functions and Hooks

### Action Hooks
```php
add_action('admin_post_newsletter_stories_form_submission', 'newsletter_stories_handle_form_submission');
add_action('admin_notices', 'newsletter_display_admin_notices');
```

### Core Functions
1. **Campaign Management**
   ```php
   newsletter_stories_handle_form_submission()
   newsletter_display_admin_notices()
   ```

2. **Block Management**
   ```php
   get_option("newsletter_blocks_$newsletter_slug", [])
   update_option("newsletter_blocks_$newsletter_slug", $blocks)
   ```

3. **Preview Generation**
   ```php
   include NEWSLETTER_PLUGIN_DIR . 'admin/partials/render-preview.php'
   ```

## Data Structures

### Block Data
```php
{
    type: string,        // 'content' or 'advertising'
    category: string,    // Category ID for content blocks
    title: string,       // Block title
    posts: array,        // Selected posts for content blocks
    html: string,        // Custom HTML for advertising blocks
    template_id: string, // Selected template
    story_count: string  // Number of stories to display
}
```

### Campaign Settings
```php
{
    subject_line: string,    // Email subject
    target_tags: array,      // Mailchimp tags
    custom_header: string,   // Custom header HTML
    custom_footer: string,   // Custom footer HTML
    send_time: string,       // Scheduled send time
    send_days: array         // Scheduled send days
}
```

## UI Components

### 1. Campaign Settings Tab
- Campaign name display
- Subject line input
- Next scheduled time display
- Save/Reset functionality

### 2. Tag Targeting Tab
- Mailchimp tag selection
- Subscriber segmentation options
- Tag management interface

### 3. Header/Footer HTML Tabs
- Custom HTML editors
- Template variables support
- Preview integration

### 4. Block Management
- Block type selection
- Category assignment
- Template selection
- Story count controls
- Drag-and-drop reordering

### 5. Mailchimp Integration
- Test email functionality
- Draft campaign creation
- Campaign scheduling
- Immediate send option

## JavaScript Integration

### Core Functionality
1. **Block Management**
   - Dynamic block addition/removal
   - Block reordering
   - Template selection
   - Category loading

2. **Preview System**
   - Real-time preview updates
   - Template processing
   - Content assembly

3. **Campaign Management**
   - Test email dialog
   - Campaign scheduling
   - Send confirmation

### Event Handlers
```javascript
jQuery(document).ready(function($) {
    // Tab Navigation
    $('.nav-tab').on('click', ...)
    
    // Block Management
    $('#add-block').on('click', ...)
    $('#blocks-container').sortable(...)
    
    // Campaign Actions
    $('#send-test-email').on('click', ...)
    $('#send-to-mailchimp').on('click', ...)
    $('#schedule-campaign').on('click', ...)
    $('#send-now').on('click', ...)
});
```

## Security Measures

### 1. Input Validation
- Nonce verification
- Capability checking
- Data sanitization

### 2. Form Security
- CSRF protection
- XSS prevention
- Secure form submission

### 3. API Security
- Mailchimp API key protection
- Secure API communication
- Error handling

## Best Practices

### 1. Block Management
- Limit block count for performance
- Validate block data before save
- Maintain block order integrity
- Handle template availability

### 2. Preview Generation
- Cache preview content
- Optimize template processing
- Handle missing content gracefully
- Maintain responsive design

### 3. Campaign Management
- Verify settings before send
- Double-confirm campaign actions
- Log campaign operations
- Handle API failures gracefully

## Common Issues and Solutions

### 1. Preview Generation
- Issue: Preview not updating
- Solution: Clear preview cache and regenerate

### 2. Block Ordering
- Issue: Blocks not saving order
- Solution: Verify jQuery UI sortable initialization

### 3. Template Processing
- Issue: Templates not loading
- Solution: Check template file existence and permissions

## Related Documentation
- [Newsletter Configuration](newsletter-configuration.md)
- [Block Management](newsletter-blocks.md)
- [Preview System](newsletter-preview.md)
- [Campaign Management](newsletter-campaigns.md)
- [Mailchimp Integration](mailchimp-integration.md) 