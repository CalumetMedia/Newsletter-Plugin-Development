# Mailchimp Integration System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #mailchimp #api #campaigns #scheduling #automation

## Overview
The Mailchimp Integration System provides comprehensive functionality for managing newsletter campaigns through the Mailchimp API. It handles campaign creation, scheduling, testing, and sending, with support for templates, segments, and automated scheduling.

## System Architecture

### 1. Core Components
```php
// Main component structure
Newsletter_Mailchimp_API
├── API Communication
├── Campaign Management
├── Content Handling
├── Schedule Management
└── Error Handling
```

### 2. File Structure
```
includes/
├── class-mailchimp-api.php         // Core API functionality
├── mailchimp-integration.php       // Integration helpers
├── ajax/
│   ├── ajax-mailchimp.php         // AJAX handlers
│   └── ajax-schedule.php          // Scheduling handlers
└── cron-automation.php            // Automated tasks
```

## API Implementation

### 1. API Configuration
```php
class Newsletter_Mailchimp_API {
    private $api_key;
    private $api_endpoint = 'https://[dc].api.mailchimp.com/3.0/';
    private $datacenter;

    public function __construct() {
        $this->api_key = get_option('mailchimp_api_key', '');
        if ($this->api_key) {
            $this->datacenter = substr(strstr($this->api_key, '-'), 1);
            $this->api_endpoint = str_replace('[dc]', $this->datacenter, $this->api_endpoint);
        }
    }
}
```

### 2. Campaign Creation
```php
public function create_campaign($newsletter_slug, $subject_line, $campaign_name) {
    $list_id = get_option('mailchimp_list_id');
    $recipients = ['list_id' => $list_id];
    
    // Handle segment targeting
    $target_tags = get_option("newsletter_target_tags_$newsletter_slug", []);
    if (!empty($target_tags)) {
        $recipients['segment_opts'] = [
            'saved_segment_id' => intval($target_tags[0])
        ];
    }

    $payload = [
        'type' => 'regular',
        'recipients' => $recipients,
        'settings' => [
            'subject_line' => $subject_line,
            'title' => $campaign_name,
            'from_name' => $from_name,
            'reply_to' => $reply_to,
            'to_name' => '*|FNAME|*',
            'authenticate' => true,
            'auto_footer' => false,
            'inline_css' => true
        ]
    ];

    return $this->make_request('campaigns', 'POST', $payload);
}
```

## Campaign Management

### 1. Campaign Operations
```php
// Set campaign content
public function set_campaign_content($campaign_id, $html_content) {
    return $this->make_request(
        "campaigns/$campaign_id/content",
        'PUT',
        ['html' => $html_content]
    );
}

// Send campaign
public function send_campaign($campaign_id) {
    return $this->make_request(
        "campaigns/$campaign_id/actions/send",
        'POST'
    );
}

// Delete campaign
public function delete_campaign($campaign_id) {
    return $this->make_request(
        "campaigns/$campaign_id",
        'DELETE'
    );
}
```

### 2. Test Email Functionality
```php
public function send_test_email($campaign_id, $test_email) {
    $payload = [
        'test_emails' => [$test_email],
        'send_type' => 'html'
    ];
    
    $response = $this->make_request(
        "campaigns/$campaign_id/actions/test",
        'POST',
        $payload
    );
    
    // Clean up test campaign
    $this->make_request("campaigns/$campaign_id", 'DELETE');
    return $response;
}
```

## Scheduling System

### 1. Campaign Scheduling
```php
public function schedule_campaign($campaign_id, $timestamp) {
    // Convert to UTC for Mailchimp API
    $local_dt = new DateTime("@$timestamp");
    $local_dt->setTimezone(wp_timezone());
    $utc_dt = clone $local_dt;
    $utc_dt->setTimezone(new DateTimeZone('UTC'));
    
    return $this->make_request(
        "campaigns/$campaign_id/actions/schedule",
        'POST',
        ['schedule_time' => $utc_dt->format('Y-m-d\TH:i:s\Z')]
    );
}
```

### 2. Schedule Management
```php
// Unschedule campaign
public function unschedule_campaign($campaign_id) {
    return $this->make_request(
        "campaigns/$campaign_id/actions/unschedule",
        'POST'
    );
}

// Get upcoming campaigns
public function get_upcoming_scheduled_campaigns($minutes_ahead = 75) {
    $response = $this->make_request('campaigns', 'GET', [
        'status' => 'schedule',
        'sort_field' => 'schedule_time',
        'sort_dir' => 'ASC'
    ]);
}
```

## AJAX Integration

### 1. Campaign Creation
```javascript
window.createMailchimpCampaign = function(subject_line, campaign_name) {
    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        data: {
            action: 'create_mailchimp_campaign',
            security: newsletterData.nonceMailchimp,
            newsletter_slug: newsletterData.newsletterSlug,
            subject_line: subject_line,
            campaign_name: campaign_name
        }
    });
};
```

### 2. Campaign Scheduling
```javascript
window.createAndScheduleCampaign = function(scheduleDateTime) {
    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        data: {
            action: 'create_and_schedule_campaign',
            security: newsletterData.nonceMailchimp,
            newsletter_slug: newsletterData.newsletterSlug,
            schedule_datetime: scheduleDateTime
        }
    });
};
```

## Error Handling

### 1. API Error Handling
```php
private function make_request($endpoint, $method = 'GET', $payload = null) {
    if (empty($this->api_key)) {
        return new WP_Error('no_api_key', 'Mailchimp API key is not set');
    }

    try {
        // Make request
        $response = wp_remote_request(...);
        
        // Handle response
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['status']) && $body['status'] === 'error') {
            return new WP_Error('mailchimp_error', $body['detail']);
        }
        
        return $body;
    } catch (Exception $e) {
        return new WP_Error('request_failed', $e->getMessage());
    }
}
```

### 2. AJAX Error Handling
```javascript
error: function(xhr, status, error) {
    console.error('AJAX Error:', {xhr, status, error});
    alert("Error: " + error);
}
```

## Best Practices

### 1. API Usage
- Validate API key before requests
- Handle rate limiting
- Implement proper error handling
- Cache API responses
- Log API interactions

### 2. Campaign Management
- Verify campaign data
- Implement safety checks
- Handle timezone conversions
- Maintain audit trail
- Clean up test campaigns

### 3. Security
- Validate all inputs
- Use nonce verification
- Check user capabilities
- Sanitize API responses
- Implement logging

## Common Issues and Solutions

### 1. API Connection
- **Issue**: API key validation fails
- **Solution**: Verify API key format
- **Prevention**: Regular key validation
- **Monitoring**: Log API responses

### 2. Campaign Scheduling
- **Issue**: Timezone mismatches
- **Solution**: Proper UTC conversion
- **Prevention**: Timezone validation
- **Monitoring**: Schedule verification

### 3. Content Syncing
- **Issue**: Content not updating
- **Solution**: Verify content push
- **Prevention**: Content validation
- **Monitoring**: Track content updates

## Dependencies
- WordPress HTTP API
- WordPress Options API
- jQuery for AJAX
- Newsletter Template System
- WordPress Cron System

## Related Documentation
- [Newsletter Templates](newsletter-templates.md)
- [Newsletter Stories](newsletter-stories.md)
- [Campaign Automation](campaign-automation.md)
- [Error Handling](error-handling.md) 