<?php
// includes/ajax-handlers.php
if (!defined('ABSPATH')) {
    exit;
}

// Include helper functions
include_once NEWSLETTER_PLUGIN_DIR . 'includes/helpers.php';

// Include each AJAX handler file
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-load-block-posts.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-generate-preview.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-save-blocks.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-schedule.php';
include_once NEWSLETTER_PLUGIN_DIR . 'includes/ajax/ajax-mailchimp.php';

