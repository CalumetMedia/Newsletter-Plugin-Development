<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

// Sanitize an array of checkbox inputs
function np_sanitize_checkbox_array($input) {
    return is_array($input) ? array_map('sanitize_text_field', $input) : [];
}

// Function to log errors for debugging purposes
function np_log_error($message) {
    $log_file = WP_CONTENT_DIR . '/debug.log';  // Log file path in the wp-content directory
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, 3, $log_file);
}
