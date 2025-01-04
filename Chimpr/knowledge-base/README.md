# Chimpr Newsletter Plugin Knowledge Base
**Current Version**: 2.3.2.4.1
**Last Updated**: December 31, 2024

## Overview
Chimpr is a WordPress newsletter plugin designed for managing and automating newsletter creation, campaign management, and content distribution. Originally developed for The Wire Report, it has evolved into a comprehensive newsletter management system with advanced automation capabilities.

## Core Functionality
- Newsletter creation and management
- Block-based content system
- Template management
- Campaign automation
- PDF generation
- Mailchimp integration
- Preview system
- Content automation

## File Structure

### Plugin Root
```
Chimpr-v2.3.2.4.1/
├── admin/                 # Admin interface and dashboard
├── assets/               # JavaScript, CSS, and media files
├── includes/            # Core plugin functionality
│   ├── ajax/           # AJAX handlers
│   ├── pdf/            # PDF generation system
│   └── helpers.php     # Utility functions
├── knowledge-base/      # Documentation and development resources
│   ├── features/       # Current feature documentation
│   ├── bugs/           # Bug tracking and known issues
│   ├── versions/       # Version history and roadmap
│   └── feature-development/  # Upcoming feature specifications
└── templates/          # Default templates and layouts
```

### Key Documentation Files
- [Newsletter Preview System](features/newsletter-preview-system.md)
- [Block Management](features/block-management.md)
- [PDF Generation](features/pdf-generation.md)
- [Newsletter Templates](features/newsletter-templates.md)
- [Error Handling](features/error-handling.md)

### Development Roadmap
- [Version History](versions/version-history.md)
- [Current Version Notes](versions/2.3.2.4.1-notes.md)
- [Upcoming Features](versions/future-versions.md)

## Critical Components

### Block System
The plugin uses a custom block management system for content organization. Blocks can be:
- WYSIWYG content
- Post selections
- PDF links
- Custom HTML
- Template-based content

Reference: [Block Management Documentation](features/block-management.md)

### Template System
Templates are managed through a hierarchical system:
- Newsletter templates
- Block templates
- PDF templates
- Header/footer templates

Reference: [Template System Documentation](features/newsletter-templates.md)

### Preview Generation
Real-time preview generation with:
- Content rendering
- Template processing
- Block validation
- State management

Reference: [Preview System Documentation](features/newsletter-preview-system.md)

## Development Guidelines

### Code Standards
- WordPress coding standards
- PHP 7.4+ compatibility
- Modern JavaScript (ES6+)
- Modular architecture
- Extensive error logging

### Critical Variables
- Always preserve block order
- Maintain template variable case sensitivity
- Use proper content keys ('html' vs 'content')
- Handle WYSIWYG state carefully

### State Management
- Block state preservation
- Editor content persistence
- Template variable handling
- Preview state synchronization

## Future Development
See [feature-development/](feature-development/) for detailed specifications of upcoming features:
- Custom Post Types (2.5.0)
- A/B Testing (2.5.1)
- Advanced Integrations (2.6+)

## Known Issues
Current critical issues are tracked in [bugs/bug_tracker.md](bugs/bug_tracker.md)

## Integration Points
- WordPress core
- Mailchimp API
- Events Manager
- Advanced Custom Fields (planned)
- MemberPress (planned)

## Performance Considerations
- Memory management for PDF generation
- Query optimization for large datasets
- Cache implementation for previews
- Resource handling for block operations

## Security Notes
- Input validation required for all content
- Output sanitization for templates
- Access control for admin functions
- API key management for integrations

This knowledge base serves as the central reference for understanding, maintaining, and extending the Chimpr plugin. For specific implementation details, refer to the relevant documentation files in the features directory. 