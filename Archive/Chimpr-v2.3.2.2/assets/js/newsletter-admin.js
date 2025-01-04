jQuery(document).ready(function($) {
    // Initialize blockIndex to count how many .block-item are currently present.
    window.blockIndex = $('#blocks-container .block-item').length || 0;

    // Ensure TinyMCE is loaded and remove any temp editors if needed.
    if (typeof wp !== 'undefined' && wp.editor) {
        wp.editor.remove('temp-editor');
    }

    window.blockIndex = $('#blocks-container .block-item').length || 0;

    // The rest of the code has been moved into separate JS files.
});
