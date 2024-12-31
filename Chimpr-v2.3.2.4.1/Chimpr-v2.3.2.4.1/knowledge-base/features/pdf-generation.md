# PDF Generation System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #pdf #tcpdf #templates #generation #export

## Overview
The PDF Generation System provides robust functionality for converting newsletter content into professionally formatted PDF documents. It utilizes TCPDF for PDF generation, supports custom templates, and includes comprehensive logging and error handling.

## System Architecture

### 1. Core Components
```php
// Main component structure
Newsletter_PDF_Generator
├── Newsletter_PDF_Controller   // Handles routing and initialization
├── Newsletter_PDF_Admin       // Admin interface management
├── Newsletter_PDF_Mailchimp   // Mailchimp integration
├── Newsletter_PDF_Security    // Security measures
└── Newsletter_PDF_Logger      // Logging system
```

### 2. File Structure
```
includes/pdf/
├── class-newsletter-pdf-generator.php   // Core generation logic
├── class-newsletter-pdf-controller.php  // Main controller
├── class-newsletter-pdf-admin.php      // Admin interface
├── class-newsletter-pdf-mailchimp.php  // Mailchimp integration
├── class-newsletter-pdf-security.php   // Security handling
├── class-newsletter-pdf-logger.php     // Logging system
├── pdf-functions.php                   // Helper functions
├── templates/                          // PDF templates
│   ├── default-pdf-template.php
│   └── pdf-styles.php
└── views/                             // Admin views
    └── pdf-preview.php
```

## PDF Generation Process

### 1. Initialization
```php
// Set up PDF generator
$generator = new Newsletter_PDF_Generator($newsletter_slug, $template_id);

// Configure TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Newsletter Plugin');
$pdf->SetAuthor('Newsletter Plugin');
$pdf->SetTitle($newsletter_slug . ' Newsletter');
```

### 2. Content Processing
```php
// Generate HTML content
$html_content = $this->generate_html_content($blocks, $template);

// Apply template
$processed_content = str_replace(
    ['{CUSTOM_HEADER}', '{CONTENT}', '{CUSTOM_CSS}'],
    [$custom_header, $newsletter_content, $custom_css],
    $template['content']
);
```

### 3. PDF Configuration
```php
// Basic PDF settings
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetFont('helvetica', '', 10);
```

## Template System

### 1. Default Template Structure
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{NEWSLETTER_NAME}</title>
    {PDF_STYLES}
</head>
<body>
    <div class="pdf-header">
        <img src="{HEADER_LOGO}" alt="Newsletter Logo" />
        <div class="pdf-date">{DATE}</div>
    </div>
    <div class="pdf-content">
        {CONTENT}
    </div>
    <div class="pdf-footer">
        <div class="page-number">Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div>
</body>
</html>
```

### 2. Template Placeholders
- `{NEWSLETTER_NAME}`: Newsletter title
- `{PDF_STYLES}`: Custom PDF styles
- `{HEADER_LOGO}`: Logo image path
- `{DATE}`: Current date
- `{CONTENT}`: Main content
- `{PAGE_NUM}`: Current page number
- `{PAGE_COUNT}`: Total pages

## Security Measures

### 1. File Security
```php
// Secure directory creation
$upload_dir = wp_upload_dir();
$secure_dir = $upload_dir['basedir'] . '/secure';
wp_mkdir_p($secure_dir);

// Access control
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}
```

### 2. Input Validation
```php
// Sanitize inputs
$newsletter_slug = sanitize_text_field($_GET['newsletter_slug']);
$template_id = intval($_POST['template_id']);

// Verify nonce
check_ajax_referer('newsletter_pdf_preview', 'nonce');
```

## Logging System

### 1. Log Implementation
```php
class Newsletter_PDF_Logger {
    public function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s]: %s\n", 
            $timestamp, 
            strtoupper($level), 
            $message
        );
        
        $log_file = $this->log_directory . '/pdf-' . date('Y-m-d') . '.log';
        error_log($log_entry, 3, $log_file);
    }
}
```

### 2. Log Levels
- **Error**: Critical issues
- **Info**: General operations
- **Debug**: Detailed debugging (WP_DEBUG only)

## Integration Points

### 1. Mailchimp Integration
```php
// Add PDF link to campaign
public function add_pdf_link_to_campaign($content, $newsletter_slug) {
    if (!newsletter_pdf_enabled($newsletter_slug)) {
        return $content;
    }

    $pdf_url = get_option("newsletter_current_pdf_url_$newsletter_slug");
    return str_replace('{PDF_LINK}', $this->get_pdf_link_html($pdf_url), $content);
}
```

### 2. Admin Interface
```php
// PDF controls rendering
public function render_pdf_controls() {
    ?>
    <div class="pdf-controls">
        <button class="preview-pdf">Preview PDF</button>
        <button class="generate-pdf">Generate PDF</button>
        <div class="pdf-status"></div>
    </div>
    <?php
}
```

## Error Handling

### 1. Generation Errors
```php
try {
    $pdf->Output($output_path, 'F');
    if (!file_exists($output_path)) {
        return new WP_Error('pdf_not_found', 'PDF file was not created');
    }
} catch (Exception $e) {
    error_log("[PDF Generator] Failed: " . $e->getMessage());
    return new WP_Error('pdf_generation_failed', $e->getMessage());
}
```

### 2. Template Errors
```php
if (!$template) {
    error_log("[PDF Generator] Template not found: $template_id");
    return new WP_Error('missing_template', 'PDF template not found');
}
```

## Best Practices

### 1. Performance
- Use memory efficiently
- Implement proper error handling
- Cache template data
- Optimize image processing
- Clean up temporary files

### 2. Security
- Validate all inputs
- Secure file storage
- Implement access control
- Sanitize template content
- Log security events

### 3. Maintenance
- Regular log rotation
- Template versioning
- Error monitoring
- Performance tracking
- Security updates

## Common Issues and Solutions

### 1. Memory Issues
- **Issue**: Memory limit exceeded
- **Solution**: Increase memory allocation
- **Prevention**: Optimize content processing
- **Monitoring**: Track memory usage

### 2. Template Problems
- **Issue**: Missing placeholders
- **Solution**: Validate template structure
- **Prevention**: Template validation
- **Monitoring**: Log template usage

### 3. File Access
- **Issue**: Permission denied
- **Solution**: Check directory permissions
- **Prevention**: Proper setup
- **Monitoring**: Log file operations

## Dependencies
- TCPDF Library
- WordPress File System
- WordPress Options API
- Newsletter Template System
- Mailchimp Integration

## Related Documentation
- [Newsletter Templates](newsletter-templates.md)
- [Newsletter Stories](newsletter-stories.md)
- [Mailchimp Integration](mailchimp-integration.md)
- [Security Measures](security-measures.md) 