# Error Handling and Logging System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core System Component
**Tags**: #error-handling #logging #debugging #monitoring

## Overview
The Error Handling and Logging System provides comprehensive error tracking, debugging capabilities, and logging functionality across the newsletter plugin. It implements structured logging, error handling patterns, and debugging tools to maintain system reliability and facilitate troubleshooting.

## System Architecture

### 1. Core Components
```php
// Main component structure
Error_Handling_System
├── PDF_Logger
├── Mailchimp_Error_Handler
├── AJAX_Error_Handler
└── Debug_Logger
```

### 2. File Structure
```
includes/
├── pdf/
│   └── class-newsletter-pdf-logger.php   // PDF-specific logging
├── class-mailchimp-api.php              // Mailchimp error handling
├── ajax/
│   ├── ajax-mailchimp.php              // AJAX error responses
│   └── ajax-save-blocks.php            // Block saving errors
└── form-handlers.php                    // Form submission errors
```

## Logging System

### 1. PDF Logger Implementation
```php
class Newsletter_PDF_Logger {
    private $log_directory;
    
    public function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = sprintf(
            "[%s] [%s]: %s\n",
            $timestamp,
            strtoupper($level),
            $message
        );
        
        $log_file = $this->log_directory . '/pdf-' . date('Y-m-d') . '.log';
        error_log($log_entry, 3, $log_file);
    }
}
```

### 2. Mailchimp Error Handling
```php
private function handle_api_error($response) {
    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 400) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $error_message = isset($body['detail']) ? 
            $body['detail'] : 'Unknown API error';
        return new WP_Error('api_error', $error_message, $body);
    }
}
```

## Error Types and Handling

### 1. API Errors
- Mailchimp API failures
- WordPress API errors
- External service errors
- Network timeouts
- Authentication failures

### 2. Processing Errors
- PDF generation failures
- Content processing errors
- Template rendering issues
- Data validation errors
- Resource limitations

### 3. User Input Errors
- Form validation failures
- Invalid data submissions
- Missing required fields
- Format validation errors
- Security check failures

## Logging Levels

### 1. Error
```php
// Critical errors that need immediate attention
$logger->error("Failed to generate PDF: Memory limit exceeded");
```

### 2. Info
```php
// General operational information
$logger->info("Campaign created successfully: ID {$campaign_id}");
```

### 3. Debug
```php
// Detailed debugging information
if (defined('WP_DEBUG') && WP_DEBUG) {
    $logger->debug("Processing block data: " . print_r($block, true));
}
```

## Error Response Patterns

### 1. AJAX Responses
```php
function handle_ajax_error($error) {
    wp_send_json_error([
        'message' => $error->get_error_message(),
        'code' => $error->get_error_code(),
        'data' => $error->get_error_data()
    ]);
}
```

### 2. API Responses
```php
function handle_api_error($response) {
    if (is_wp_error($response)) {
        $this->log($response->get_error_message(), 'error');
        return new WP_Error(
            'api_error',
            'API request failed: ' . $response->get_error_message()
        );
    }
}
```

## Debug Tools

### 1. Log Retrieval
```php
public function get_logs($days = 1) {
    $logs = array();
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $log_file = $this->log_directory . '/pdf-' . $date . '.log';
        if (file_exists($log_file)) {
            $logs[$date] = file_get_contents($log_file);
        }
    }
    return $logs;
}
```

### 2. Log Management
```php
public function clear_logs() {
    $files = glob($this->log_directory . '/pdf-*.log');
    foreach ($files as $file) {
        @unlink($file);
    }
}
```

## Best Practices

### 1. Error Handling
- Use appropriate error types
- Include context in messages
- Handle errors at proper level
- Maintain error hierarchy
- Implement fallback behavior

### 2. Logging
- Use consistent formats
- Include timestamps
- Add context information
- Rotate logs regularly
- Monitor log sizes

### 3. Debugging
- Use debug levels
- Include stack traces
- Log variable states
- Track process flow
- Monitor performance

## Common Issues and Solutions

### 1. Log File Growth
- **Issue**: Excessive log size
- **Solution**: Implement rotation
- **Prevention**: Regular cleanup
- **Monitoring**: Size checks

### 2. Error Propagation
- **Issue**: Cascading failures
- **Solution**: Proper catching
- **Prevention**: Validation
- **Monitoring**: Error rates

### 3. Memory Usage
- **Issue**: Memory exhaustion
- **Solution**: Chunked processing
- **Prevention**: Size limits
- **Monitoring**: Usage tracking

## Dependencies
- WordPress Error System
- File System Access
- AJAX Handlers
- WordPress Options API
- Mailchimp API

## Related Documentation
- [Newsletter Configuration](newsletter-configuration.md)
- [PDF Generation](pdf-generation.md)
- [Mailchimp Integration](mailchimp-integration.md)
- [Block Management](block-management.md) 