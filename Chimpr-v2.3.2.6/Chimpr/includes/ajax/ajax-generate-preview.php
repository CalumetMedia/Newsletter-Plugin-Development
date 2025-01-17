<?php
// includes/ajax/ajax-generate-preview.php
if (!defined('ABSPATH')) exit;

if (!function_exists('newsletter_generate_preview')):

function newsletter_generate_preview() {
    // Set time limit for preview generation
    set_time_limit(120);
    
    // Increase memory limit if needed
    $current_limit = ini_get('memory_limit');
    if (intval($current_limit) < 256) {
        ini_set('memory_limit', '256M');
    }

    // Enable output compression
    if (!ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'On');
    }

    try {
        check_ajax_referer('generate_preview_nonce', 'security');
        
        if (!isset($_POST['newsletter_slug'])) {
            wp_send_json_error('Missing newsletter slug');
            return;
        }

        $newsletter_slug = sanitize_text_field($_POST['newsletter_slug']);
        if (empty($newsletter_slug)) {
            wp_send_json_error('Invalid newsletter slug');
            return;
        }
        
        // Get saved blocks
        $saved_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
        if (!is_array($saved_blocks)) {
            wp_send_json_error('Invalid blocks format in database');
            return;
        }

        // Update template selections if provided
        if (isset($_POST['header_template'])) {
            update_option(
                "newsletter_header_template_$newsletter_slug",
                sanitize_text_field(wp_unslash($_POST['header_template']))
            );
        }
        if (isset($_POST['footer_template'])) {
            update_option(
                "newsletter_footer_template_$newsletter_slug",
                sanitize_text_field(wp_unslash($_POST['footer_template']))
            );
        }

        // Validate and decode saved selections
        $saved_selections = [];
        if (isset($_POST['saved_selections'])) {
            $decoded = json_decode(stripslashes($_POST['saved_selections']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('Invalid saved selections format');
                return;
            }
            if (!is_array($decoded)) {
                wp_send_json_error('Invalid saved selections structure');
                return;
            }
            
            // Validate each block's structure
            foreach ($decoded as $block_index => $block_data) {
                if (!is_array($block_data)) {
                    continue;
                }
                
                if (!isset($block_data['type'])) {
                    continue;
                }
                
                // Specific validation for WYSIWYG blocks
                if ($block_data['type'] === 'wysiwyg' && !isset($block_data['wysiwyg'])) {
                    // Initialize empty WYSIWYG content rather than skipping
                    $block_data['wysiwyg'] = '';
                }
                
                $saved_selections[$block_index] = $block_data;
            }
        }

        // Merge saved selections into blocks
        foreach ($saved_selections as $block_index => $block_data) {
            
            if (!isset($saved_blocks[$block_index])) {
                $saved_blocks[$block_index] = [];
            }
            
            // Handle WYSIWYG content
            if (isset($block_data['type']) && $block_data['type'] === 'wysiwyg') {
                
                $saved_blocks[$block_index]['type'] = 'wysiwyg';
                
                // Check if WYSIWYG content exists in new data
                if (!empty($block_data['wysiwyg'])) {
                    $saved_blocks[$block_index]['wysiwyg'] = wp_kses_post($block_data['wysiwyg']);
                } else {
                    // Preserve existing WYSIWYG content if present
                    $existing_blocks = get_option("newsletter_blocks_$newsletter_slug", []);
                    if (isset($existing_blocks[$block_index]['wysiwyg'])) {
                        $saved_blocks[$block_index]['wysiwyg'] = $existing_blocks[$block_index]['wysiwyg'];
                    } else {
                        $saved_blocks[$block_index]['wysiwyg'] = '';
                    }
                }
            }
            
            // Handle HTML content
            if (isset($block_data['type']) && $block_data['type'] === 'html') {
                $saved_blocks[$block_index]['type'] = 'html';
                $saved_blocks[$block_index]['html'] = wp_kses_post($block_data['html']);
            }

            // Handle PDF Link content - treat like HTML but pull from template
            if (isset($block_data['type']) && $block_data['type'] === 'pdf_link') {
                $saved_blocks[$block_index]['type'] = 'pdf_link';
                
                // Get template content if template_id is set
                if (isset($block_data['template_id'])) {
                    $available_templates = get_option('newsletter_templates', []);
                    $template_id = $block_data['template_id'];
                    
                    if (isset($available_templates[$template_id])) {
                        // Store the template content as if it was HTML content
                        $saved_blocks[$block_index]['html'] = wp_kses_post($available_templates[$template_id]['html']);
                    } else {
                        $saved_blocks[$block_index]['html'] = ''; // Empty if template not found
                    }
                }
            }
            
            // Handle other block data
            foreach (['title', 'show_title', 'template_id', 'category', 'date_range', 'story_count', 'manual_override'] as $field) {
                if (isset($block_data[$field])) {
                    $saved_blocks[$block_index][$field] = $block_data[$field];
                }
            }

            // Handle posts data
            if (isset($block_data['posts']) && is_array($block_data['posts'])) {
                $saved_blocks[$block_index]['posts'] = [];
                foreach ($block_data['posts'] as $post_id => $post_data) {
                    if (!is_array($post_data)) {
                        continue;
                    }

                    // Only store checked posts
                    if ((isset($post_data['checked']) && $post_data['checked'] === '1') || 
                        (isset($post_data['selected']) && $post_data['selected'] === '1')) {
                        $saved_blocks[$block_index]['posts'][$post_id] = [
                            'checked' => '1',  // Always store as 'checked'
                            'order' => isset($post_data['order']) ? intval($post_data['order']) : PHP_INT_MAX
                        ];
                    }
                }
            }
        }

        // Generate preview content
        $preview_html = newsletter_generate_preview_content($newsletter_slug, $saved_blocks);
        if ($preview_html === false) {
            wp_send_json_error('Error generating preview content');
            return;
        }

        // Build preview HTML
        $final_html = '<div class="newsletter-preview-container">';
        $custom_css = apply_filters('newsletter_preview_custom_css', '');
        if (!empty($custom_css)) {
            $final_html .= '<style type="text/css">';
            $final_html .= '.newsletter-preview-container {' . esc_html($custom_css) . '}';
            $final_html .= '</style>';
        }
        
        // Add PDF Link specific styles if needed
        $final_html .= '<style type="text/css">';
        $final_html .= '.pdf-link-block { /* Add any specific styling for PDF Link blocks */ }';
        $final_html .= '</style>';
        
        $final_html .= $preview_html;
        $final_html .= '</div>';

        wp_send_json_success($final_html);
        
    } catch (Exception $e) {
        wp_send_json_error('Error generating preview: ' . $e->getMessage());
    }
}

endif;

// Register the AJAX action only if we're in an admin context
if (is_admin()) {
    add_action('wp_ajax_generate_preview', 'newsletter_generate_preview');
}
