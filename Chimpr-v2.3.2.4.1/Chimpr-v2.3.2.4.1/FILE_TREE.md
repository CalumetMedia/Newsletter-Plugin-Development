# Chimpr Plugin File Structure
**Last Updated**: December 31, 2024

## Directory Tree
```
Chimpr-v2.3.2.4.1/
├── newsletter-plugin.php           # Main plugin file, handles initialization and core setup
├── admin/                         # Admin interface files
│   ├── admin-page.php            # Main admin dashboard
│   ├── newsletter-settings.php    # Newsletter configuration interface
│   ├── newsletter-stories.php     # Story selection and management
│   ├── campaign-manager.php       # Campaign creation and management
│   ├── templates.php             # Template management interface
│   └── partials/                 # UI components
│       ├── render-preview.php     # Preview generation
│       └── block-editor.php       # Block editing interface
├── includes/                      # Core functionality
│   ├── class-mailchimp-api.php   # Mailchimp API integration
│   ├── class-newsletter.php      # Newsletter core functionality
│   ├── helpers.php              # Utility functions
│   ├── ajax/                    # AJAX handlers
│   │   ├── ajax-save-blocks.php  # Block saving operations
│   │   ├── ajax-preview.php      # Preview generation
│   │   └── ajax-mailchimp.php    # Mailchimp operations
│   └── pdf/                     # PDF generation system
│       ├── class-pdf-generator.php # PDF generation core
│       ├── templates/            # PDF templates
│       └── views/               # PDF rendering views
├── assets/                       # Frontend resources
│   ├── js/                      # JavaScript files
│   │   ├── editor.js            # WYSIWYG editor functionality
│   │   ├── preview.js           # Preview system
│   │   ├── blocks.js            # Block management
│   │   └── campaign.js          # Campaign management
│   ├── css/                     # Stylesheets
│   │   ├── admin.css            # Admin interface styles
│   │   ├── editor.css           # Editor styles
│   │   └── preview.css          # Preview styles
│   └── images/                  # Image assets
└── templates/                    # Newsletter templates
    ├── default/                 # Default template files
    ├── blocks/                  # Block templates
    └── pdf/                     # PDF templates
```

## File Descriptions

### Core Files
- `newsletter-plugin.php`: Main plugin initialization, hooks, and core setup
- `class-newsletter.php`: Core newsletter functionality and data management
- `helpers.php`: Common utility functions and helper methods
- `individual-settings.php`: Handles individual newsletter configuration and settings

### Admin Interface
- `admin-page.php`: Main admin dashboard and navigation
- `newsletter-settings.php`: Newsletter configuration and settings management
- `newsletter-stories.php`: Story selection and content management interface
- `campaign-manager.php`: Campaign creation, scheduling, and management
- `templates.php`: Template creation, editing, and management interface

### AJAX Handlers
- `ajax-save-blocks.php`: Handles block content saving and validation
- `ajax-preview.php`: Manages preview generation and updates
- `ajax-mailchimp.php`: Handles Mailchimp API operations

### PDF System
- `class-pdf-generator.php`: Core PDF generation functionality
- `pdf-template.php`: PDF template rendering and processing
- `pdf-preview.php`: PDF preview generation

### JavaScript Files
- `editor.js`: WYSIWYG editor initialization and management
- `preview.js`: Real-time preview generation and updates
- `blocks.js`: Block management and interaction handling
- `campaign.js`: Campaign creation and management functionality

### Stylesheets
- `admin.css`: Admin interface styling
- `editor.css`: WYSIWYG editor and block styling
- `preview.css`: Newsletter preview styling

### Template Files
- `default-template.php`: Default newsletter template
- `block-templates.php`: Block-specific templates
- `pdf-templates.php`: PDF generation templates 