# Preview Content Generation Issue - December 30, 2024

## Issue Summary
Preview content was not displaying stories in the newsletter preview panel due to incorrect template content retrieval and variable handling.

## Symptoms
1. Newsletter preview panel showed no stories
2. No error messages in the UI
3. PHP error logs showed "Cannot use object of type WP_Post as array"

## Root Cause Analysis
1. Template Content Retrieval:
   - Code was looking for template content in `['content']` key
   - Templates actually store content in `['html']` key
   - No proper fallback when template not found

2. Variable Case Mismatch:
   - Code was using uppercase variables (e.g., '{TITLE}')
   - Templates use lowercase variables (e.g., '{title}')
   - No case normalization in place

3. Missing Template Handling:
   - Default template fallback was removed
   - Thumbnail conditional logic was missing
   - Error handling for post processing was incomplete

## Resolution
The following changes were made to `includes/helpers.php`:

1. Template Content Access:
   ```php
   // Before (incorrect)
   if (isset($available_templates[$template_id]['content'])) {
       $template_content = $available_templates[$template_id]['content'];
   }

   // After (correct)
   if (($template_id === '0' || $template_id === 0) && isset($available_templates[0])) {
       $template_content = $available_templates[0]['html'];
   } elseif (!empty($template_id) && isset($available_templates[$template_id]) && isset($available_templates[$template_id]['html'])) {
       $template_content = $available_templates[$template_id]['html'];
   } else {
       // Fallback to default template
       if (file_exists(NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php')) {
           ob_start();
           include NEWSLETTER_PLUGIN_DIR . 'templates/default-template.php';
           $template_content = ob_get_clean();
       } else {
           $template_content = '<div class="post-content">{title}<br>{content}</div>';
       }
   }
   ```

2. Variable Replacement:
   ```php
   // Before (incorrect)
   $replacements = [
       '{TITLE}' => $post->post_title,
       '{CONTENT}' => $post->post_content
   ];

   // After (correct)
   $replacements = [
       '{title}'         => esc_html($post->post_title),
       '{content}'       => wp_kses_post($post->post_content),
       '{thumbnail_url}' => esc_url($thumbnail_url),
       '{permalink}'     => esc_url(get_permalink($post->ID)),
       '{excerpt}'       => wp_kses_post(get_the_excerpt($post)),
       '{author}'        => esc_html(get_the_author_meta('display_name', $post->post_author)),
       '{publish_date}'  => esc_html(get_the_date('', $post->ID)),
       '{categories}'    => esc_html(implode(', ', wp_get_post_categories($post->ID, ['fields' => 'names'])))
   ];
   ```

3. Error Handling:
   ```php
   try {
       $replacements = [ /* ... */ ];
       $newsletter_html .= strtr($block_content, $replacements);
   } catch (Exception $e) {
       error_log("Error processing post ID: " . $post->ID . " - " . $e->getMessage());
       continue;
   }
   ```

## Prevention Measures
1. Template Content:
   - Always use 'html' key for template content
   - Verify template existence before use
   - Provide proper fallback templates
   - Log template retrieval failures

2. Variable Handling:
   - Use lowercase for all template variables
   - Document required variables
   - Maintain consistent variable naming
   - Add proper escaping for all variables

3. Error Prevention:
   - Add comprehensive error handling
   - Log processing failures
   - Validate data before use
   - Maintain proper fallbacks

## Testing Requirements
1. Template Retrieval:
   - Test with existing template
   - Test with missing template
   - Test with default template
   - Test with invalid template ID

2. Variable Replacement:
   - Verify all variables are replaced
   - Check escaping is applied
   - Test with missing data
   - Validate conditional logic

3. Error Handling:
   - Test with invalid post data
   - Verify error logging
   - Check fallback behavior
   - Validate error recovery

## Related Files
- `includes/helpers.php`
- `templates/default-template.php`
- `KNOWN_ISSUES.md`

## See Also
- Post Selection Key Usage Documentation
- Template Variable Standards
- Preview Generation Process 