# Newsletter Template System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #templates #blocks #pdf #email #customization

## Overview
The Newsletter Template System provides a flexible framework for managing and applying templates across different aspects of the newsletter system. It supports multiple template types, including block templates, header/footer templates, and PDF templates, with a robust placeholder system for dynamic content insertion.

## System Architecture

### 1. Template Types
```php
$template_types = [
    'header' => 'Header Template',
    'footer' => 'Footer Template',
    'block'  => 'Block Template',
    'pdf'    => 'PDF Template'
];
```

### 2. Data Structure
```php
{
    template_id: {
        name: string,       // Template name
        type: string,       // Template type
        html: string,       // Template HTML content
        content?: string    // Optional PDF-specific content
    }
}
```

## Template Management

### 1. Storage System
- Templates stored in WordPress options table
- Option key: `newsletter_templates`
- Default template fallback mechanism
- Template caching implementation

### 2. Template Operations
```php
// Template Creation
$templates[] = [
    'name' => $template_name,
    'type' => $template_type,
    'html' => $template_html
];

// Template Update
$templates[$template_index] = [
    'name' => $template_name,
    'type' => $template_type,
    'html' => $template_html
];

// Template Deletion
unset($templates[$template_index]);
```

## Placeholder System

### 1. Available Placeholders
- **Post Content**
  - `{title}`: Post title
  - `{excerpt}`: Post excerpt
  - `{content}`: Full post content
  - `{permalink}`: Post URL
  - `{thumbnail_url}`: Featured image URL
  - `{author}`: Post author name
  - `{date}`: Post date
  - `{categories}`: Post categories
  - `{tags}`: Post tags

- **Conditional Blocks**
  - `{if_thumbnail}...{/if_thumbnail}`: Thumbnail conditional
  - `{stories_loop}...{/stories_loop}`: Multiple stories loop

- **PDF Specific**
  - `{CONTENT}`: Main content area
  - `{PAGE_NUM}`: Current page number
  - `{PAGE_COUNT}`: Total pages
  - `{HEADER_LOGO}`: PDF header logo
  - `{PDF_STYLES}`: PDF-specific styles

### 2. Usage Examples
```html
<!-- Basic Post Template -->
<div class="post">
    {if_thumbnail}
        <img src="{thumbnail_url}" alt="{title}" />
    {/if_thumbnail}
    <h2>{title}</h2>
    <div class="meta">
        By {author} on {date}
    </div>
    <div class="content">
        {content}
    </div>
</div>

<!-- Multiple Stories Template -->
<div class="stories-container">
    {stories_loop}
        <article class="story">
            <h3>{title}</h3>
            <p>{excerpt}</p>
            <a href="{permalink}">Read more</a>
        </article>
    {/stories_loop}
</div>
```

## Template Processing

### 1. Content Processing
```php
// Template content retrieval
if (isset($available_templates[$template_id])) {
    $template_content = $available_templates[$template_id]['html'];
} else {
    // Fallback to default template
    include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
}

// Placeholder replacement
$processed_content = str_replace(
    ['{title}', '{content}', '{date}'],
    [$post_title, $post_content, $post_date],
    $template_content
);
```

### 2. PDF Generation
```php
// PDF template application
$html = str_replace(
    ['{CONTENT}', '{DATE}', '{NEWSLETTER_NAME}'],
    [$content, date('F j, Y'), $newsletter_name],
    $template['html']
);
```

## Security Measures

### 1. Input Validation
- Template name sanitization
- HTML content sanitization
- User capability verification
- Nonce verification

### 2. Output Sanitization
```php
// HTML content sanitization
$template_html = wp_kses_post(wp_unslash($template['html']));

// Attribute escaping
echo esc_attr($template['name']);
```

## Best Practices

### 1. Template Creation
- Use inline CSS for email compatibility
- Follow email HTML best practices
- Implement proper spacing
- Use mobile-responsive design
- Include fallback fonts

### 2. Template Management
- Maintain consistent naming conventions
- Document template purposes
- Implement version control
- Test across email clients
- Validate HTML structure

### 3. Performance
- Cache template data
- Optimize HTML structure
- Minimize DOM operations
- Use efficient selectors
- Implement lazy loading

## Common Issues and Solutions

### 1. Template Loading
- **Issue**: Template not found
- **Solution**: Implement fallback mechanism
- **Prevention**: Validate template existence
- **Monitoring**: Log template loading

### 2. Placeholder Processing
- **Issue**: Unprocessed placeholders
- **Solution**: Verify placeholder format
- **Prevention**: Implement validation
- **Monitoring**: Track replacement failures

### 3. PDF Generation
- **Issue**: Missing template content
- **Solution**: Check template structure
- **Prevention**: Validate PDF templates
- **Monitoring**: Log generation process

## Integration Points

### 1. Block Manager
```javascript
// Template selection handling
function handleTemplateChange(block) {
    var templateId = block.find('.template-select select').val();
    updatePreview();
}
```

### 2. Preview System
```php
// Template preview generation
function generate_preview($newsletter_slug, $blocks) {
    $template = get_template($template_id);
    return apply_template($content, $template);
}
```

## Dependencies
- WordPress Options API
- HTML Sanitization Functions
- Template Processing System
- PDF Generation System
- Preview Generation System

## Related Documentation
- [Newsletter Stories](newsletter-stories.md)
- [PDF Generation](pdf-generation.md)
- [Block Management](newsletter-blocks.md)
- [Preview System](newsletter-preview-system.md) 