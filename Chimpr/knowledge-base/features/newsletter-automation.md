# Newsletter Automation System Documentation
**Last Updated**: 2024-12-31
**Feature Type**: Core Newsletter Component
**Tags**: #automation #cron #scheduling #mailchimp #campaigns

## Overview
The Newsletter Automation System provides sophisticated scheduling and automation capabilities for newsletter distribution. It handles automated campaign creation, scheduling, and sending through Mailchimp, with support for both scheduled and ad-hoc newsletters.

## System Architecture

### 1. Core Components
```php
// Main component structure
Newsletter_Cron_Automation
├── Scheduling Engine
├── Campaign Generator
├── Time Management
└── Error Handling
```

### 2. File Structure
```
includes/
├── cron-automation.php           // Core automation logic
├── ajax/
│   ├── ajax-schedule.php        // Schedule handlers
│   └── ajax-mailchimp.php       // Mailchimp integration
└── admin/
    └── individual-settings.php   // Scheduling settings
```

## Scheduling System

### 1. Configuration Options
```php
// Newsletter scheduling settings
$settings = [
    'is_ad_hoc'  => boolean,     // Ad-hoc vs scheduled
    'send_days'  => array,       // Array of weekdays
    'send_time'  => string,      // Time in HH:mm format
    'timezone'   => string       // WordPress timezone
];
```

### 2. Time Calculation
```php
// Next send time calculation
$tz = wp_timezone();
$now = new DateTime('now', $tz);
$send_dt = (clone $now)->setTime(
    (int)$time_parts[0],
    (int)$time_parts[1],
    0
);

// Lookahead window
$cutoff = (clone $now)->modify("+{$lookahead_minutes} minutes");
```

## Automation Process

### 1. Cron Setup
```php
// Initialize cron job
if (!wp_next_scheduled('newsletter_automated_send')) {
    wp_schedule_event(
        time(),
        'daily',
        'newsletter_automated_send'
    );
}
```

### 2. Newsletter Processing
```php
public function process_automated_send() {
    // Get candidate newsletters
    foreach ($newsletters as $newsletter_slug => $newsletter_name) {
        // Skip ad-hoc newsletters
        if (get_option("newsletter_is_ad_hoc_$newsletter_slug", 0)) {
            continue;
        }

        // Check scheduling criteria
        if ($send_dt > $now && $send_dt <= $cutoff) {
            $candidate_newsletters[$newsletter_slug] = [
                'name' => $newsletter_name,
                'local_dt' => $send_dt,
                'timestamp' => $send_dt->getTimestamp()
            ];
        }
    }
}
```

## Campaign Management

### 1. Campaign Creation
```php
// Create campaign for scheduled send
$campaign = $mailchimp->create_campaign(
    $newsletter_slug,
    $subject_line,
    $campaign_name
);

// Set campaign content
$content_result = $mailchimp->set_campaign_content(
    $campaign['id'],
    $content
);

// Schedule campaign
$schedule_result = $mailchimp->schedule_campaign(
    $campaign['id'],
    $utc_schedule
);
```

### 2. PDF Integration
```php
// Add PDF link to campaign if enabled
if (get_option("newsletter_opt_into_pdf_$newsletter_slug", false)) {
    $pdf_url = get_option("newsletter_current_pdf_url_$newsletter_slug");
    if ($pdf_url) {
        $content .= sprintf(
            '<div class="pdf-download">
                <a href="%s">Download PDF Version</a>
            </div>',
            esc_url($pdf_url)
        );
    }
}
```

## Dashboard Integration

### 1. Status Display
```php
// Newsletter status indicators
$status_types = [
    'adhoc'         => 'Ad Hoc',
    'scheduled'     => 'Scheduled',
    'not-scheduled' => 'Not Scheduled'
];

// Schedule pattern display
$scheduled_pattern = sprintf(
    'Every %s at %s',
    implode(', ', $day_names),
    date('g:i a', strtotime($send_time))
);
```

### 2. Next Send Calculation
```php
// Calculate next send time
for ($i = 0; $i < 14; $i++) {
    $check = clone $now;
    $check->modify("+{$i} day");
    $day_str = strtolower($check->format('l'));
    
    if (in_array($day_str, $send_days)) {
        $check->setTime((int)$time_parts[0], (int)$time_parts[1], 0);
        if ($check > $now) {
            $next_send = $check;
            break;
        }
    }
}
```

## Error Handling

### 1. Campaign Verification
```php
// Check existing campaign
$stored_campaign_id = get_option(
    "newsletter_mailchimp_campaign_id_$newsletter_slug",
    ''
);
if (!empty($stored_campaign_id)) {
    $campaign_info = $mailchimp->get_campaign_info($stored_campaign_id);
    if (!is_wp_error($campaign_info)) {
        if ($campaign_info['status'] === 'scheduled') {
            // Skip duplicate scheduling
            continue;
        }
    }
}
```

### 2. Error Logging
```php
// Log automation errors
error_log("Failed to create campaign: " . $campaign->get_error_message());
error_log("Failed to set content: " . $content_result->get_error_message());
error_log("Failed to schedule campaign: " . $schedule_result->get_error_message());
```

## Best Practices

### 1. Time Management
- Use WordPress timezone settings
- Handle UTC conversions properly
- Implement proper lookahead windows
- Validate scheduling parameters
- Handle timezone changes

### 2. Campaign Management
- Verify campaign existence
- Check for duplicates
- Handle content generation
- Manage PDF attachments
- Track campaign status

### 3. Error Handling
- Implement proper logging
- Handle API failures
- Manage scheduling conflicts
- Track automation status
- Provide error notifications

## Common Issues and Solutions

### 1. Scheduling Issues
- **Issue**: Timezone mismatches
- **Solution**: Use WordPress timezone
- **Prevention**: Validate time settings
- **Monitoring**: Log schedule times

### 2. Campaign Duplicates
- **Issue**: Multiple campaigns
- **Solution**: Check existing campaigns
- **Prevention**: Track campaign IDs
- **Monitoring**: Log campaign creation

### 3. Content Generation
- **Issue**: Empty content
- **Solution**: Validate content
- **Prevention**: Content checks
- **Monitoring**: Track generation

## Dependencies
- WordPress Cron System
- Mailchimp API
- Newsletter Template System
- PDF Generation System
- WordPress Options API

## Related Documentation
- [Newsletter Configuration](newsletter-configuration.md)
- [Mailchimp Integration](mailchimp-integration.md)
- [PDF Generation](pdf-generation.md)
- [Error Handling](error-handling.md) 