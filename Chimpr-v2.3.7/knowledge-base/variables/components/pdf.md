# PDF Generation Variables
**Component**: PDF Generation System
**Last Updated**: January 2, 2025

## Core Variables
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

## TCPDF Configuration Constants
```
Variable: PDF_PAGE_ORIENTATION
Type: string constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines PDF page orientation
Default: 'P' (Portrait)
Potential Issues: Dependency on TCPDF constants
```

```
Variable: PDF_UNIT
Type: string constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines measurement unit for PDF
Default: 'mm'
Potential Issues: Dependency on TCPDF constants
```

```
Variable: PDF_PAGE_FORMAT
Type: string/array constant
Scope: global (TCPDF)
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Defines page size for PDF
Default: 'A4'
Potential Issues: Dependency on TCPDF constants
```

## Security Variables
```
Variable: $allowed_tags
Type: array
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-security.php
Purpose: Defines allowed HTML tags in PDF content
Structure: Array of allowed HTML tags and attributes
```

```
Variable: $security_token
Type: string
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-security.php
Purpose: Token for PDF access validation
Generation: wp_generate_password(32, false)
```

## Logger Variables
```
Variable: $log_file
Type: string
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-logger.php
Purpose: Path to PDF generation log file
Default: WP_CONTENT_DIR . '/pdf-logs/pdf-generation.log'
```

```
Variable: $log_level
Type: integer
Scope: private class property
Files: includes/pdf/class-newsletter-pdf-logger.php
Purpose: Current logging level
Values: DEBUG = 0, INFO = 1, WARNING = 2, ERROR = 3
```

## WordPress Options
```
Variable: newsletter_pdf_template_id_{$newsletter_slug}
Type: string
Scope: WordPress option
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Stores PDF template selection for specific newsletter
Default: 'default'
Potential Issues: Dynamic option name based on slug
```

```
Variable: newsletter_pdf_settings
Type: array
Scope: WordPress option
Files: includes/pdf/class-newsletter-pdf-admin.php
Purpose: Global PDF generation settings
Structure: {
    page_size: string,
    orientation: string,
    margins: {
        top: number,
        right: number,
        bottom: number,
        left: number
    },
    font_size: number,
    header_enabled: boolean,
    footer_enabled: boolean
}
```

## Template Variables
```
Variable: $pdf_templates
Type: array
Scope: WordPress option
Files: includes/pdf/class-newsletter-pdf-generator.php
Purpose: Stores PDF-specific templates
Structure: {
    template_id: {
        name: string,
        content: string,
        css: string,
        settings: object
    }
}
```

## Known Issues
1. Template Management:
   - PDF templates stored separately from main templates
   - Potential synchronization issues
   - Need for template version control

2. Security Concerns:
   - PDF access tokens stored in plain text
   - Need for better token management
   - Consider implementing expiration system

3. Resource Management:
   - Large PDF generation can exhaust memory
   - Log files can grow unbounded
   - Need for resource monitoring and cleanup

4. TCPDF Dependencies:
   - Heavy reliance on TCPDF constants
   - No fallback values defined
   - Consider wrapping in plugin constants

## Dependencies
- TCPDF library
- WordPress filesystem API
- Template system
- Security system
``` 