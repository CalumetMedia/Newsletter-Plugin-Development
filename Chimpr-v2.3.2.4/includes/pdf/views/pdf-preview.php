<?php
if (!defined('ABSPATH')) exit;

function render_pdf_preview($newsletter_slug) {
    $blocks = get_option("newsletter_blocks_$newsletter_slug", []);
    $template_id = get_option("newsletter_pdf_template_id_$newsletter_slug", 'default');
    $templates = get_option('newsletter_templates', []);
    $template = isset($templates[$template_id]) ? $templates[$template_id] : null;

    if (!$template) {
        echo '<div class="error"><p>PDF template not found</p></div>';
        return;
    }

    // Include PDF styles
    require_once plugin_dir_path(__FILE__) . '../templates/pdf-styles.php';
    
    // Generate preview content
    $content = newsletter_generate_preview_content($newsletter_slug, $blocks);
    
    // Apply template
    $html = str_replace(
        ['{CONTENT}', '{DATE}', '{NEWSLETTER_NAME}'],
        [$content, date('F j, Y'), get_option("newsletter_name_$newsletter_slug", '')],
        $template['html']
    );

    echo '<div class="pdf-preview">';
    echo $html;
    echo '</div>';
}