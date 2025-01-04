<?php
if (!defined('ABSPATH')) {
   exit;
}

function newsletter_handle_blocks_form_submission() {
   check_ajax_referer('save_blocks_action', 'security');

   $newsletter_slug = isset($_POST['newsletter_slug']) ? sanitize_text_field($_POST['newsletter_slug']) : 'default';
   $blocks = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : [];
   $sanitized_blocks = [];

   error_log("Raw blocks data: " . print_r($blocks, true));

   // Get valid block indices (sequential numbers starting from 0)
   $valid_indices = range(0, count(array_filter(array_keys($blocks), 'is_numeric')) - 1);

   // Process each block with valid index
   foreach ($valid_indices as $index) {
       if (!isset($blocks[$index]) || !is_array($blocks[$index])) {
           continue;
       }

       $block = $blocks[$index];

       // Create sanitized block with default values
       $sanitized_block = [
           'type' => sanitize_text_field($block['type'] ?? 'content'),
           'title' => sanitize_text_field($block['title'] ?? ''),
           'template_id' => sanitize_text_field($block['template_id'] ?? 'default'),
           'show_title' => isset($block['show_title']),
           'date_range' => isset($block['date_range']) ? intval($block['date_range']) : 7,
           'story_count' => isset($block['story_count']) ? sanitize_text_field($block['story_count']) : 'all',
           'category' => isset($block['category']) ? intval($block['category']) : 0,
           'posts' => []
       ];

       // Handle content blocks
       if (($block['type'] ?? 'content') === 'content') {
           // Process posts if they exist
           if (!empty($block['posts']) && is_array($block['posts'])) {
               foreach ($block['posts'] as $post_id => $post_data) {
                   if (!empty($post_data['selected'])) {
                       $sanitized_block['posts'][$post_id] = [
                           'selected' => true,
                           'order' => isset($post_data['order']) ? intval($post_data['order']) : 0
                       ];
                   }
               }
           }

           // Sort posts by order if they exist
           if (!empty($sanitized_block['posts'])) {
               uasort($sanitized_block['posts'], function($a, $b) {
                   return $a['order'] - $b['order'];
               });
           }
       }
       // Handle HTML blocks
       else if (($block['type'] ?? '') === 'html') {
           $sanitized_block['html'] = wp_kses_post($block['html'] ?? '');
       }
       // Handle WYSIWYG blocks
       else if (($block['type'] ?? '') === 'wysiwyg') {
           $sanitized_block['wysiwyg'] = wp_kses_post($block['wysiwyg'] ?? '');
       }

       // Add the block regardless of content
       $sanitized_blocks[] = $sanitized_block;
   }

   error_log("Final sanitized blocks to save: " . print_r($sanitized_blocks, true));
   update_option("newsletter_blocks_$newsletter_slug", $sanitized_blocks);

   // Handle additional fields
   if (isset($_POST['subject_line'])) {
       update_option(
           "newsletter_subject_line_$newsletter_slug", 
           sanitize_text_field(wp_unslash($_POST['subject_line']))
       );
   }

   if (isset($_POST['custom_header'])) {
       update_option(
           "newsletter_custom_header_$newsletter_slug", 
           wp_kses_post(wp_unslash($_POST['custom_header']))
       );
   }

   if (isset($_POST['custom_footer'])) {
       update_option(
           "newsletter_custom_footer_$newsletter_slug", 
           wp_kses_post(wp_unslash($_POST['custom_footer']))
       );
   }

   // Return success
   wp_send_json_success('Blocks saved successfully');
}

add_action('wp_ajax_save_newsletter_blocks', 'newsletter_handle_blocks_form_submission');