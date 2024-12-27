(function($) {

window.initWysiwygEditor = function(block) {
    block.find('.wysiwyg-editor').each(function() {
        var editorId = $(this).attr('id');
        
        // Remove existing editor if it exists
        if (tinymce && tinymce.get(editorId)) {
            tinymce.execCommand('mceRemoveEditor', true, editorId);
        }

        wp.editor.initialize(editorId, {
            tinymce: {
                wpautop: true,
                plugins: 'paste,lists,link,textcolor,wordpress,wplink',
                toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Save content to textarea
                        updatePreview();
                    });
                }
            },
            quicktags: true
        });
    });
}

window.handleBlockTypeChange = function(block, blockType) {
    var wysiwygContent = block.find('.wysiwyg-editor').val();

    if (blockType === 'content') {
        block.find('.content-block').show();
        block.find('.html-block').hide();
        block.find('.wysiwyg-block').hide();
        block.find('.template-select').show();
    } else if (blockType === 'html') {
        block.find('.content-block').hide();
        block.find('.html-block').show();
        block.find('.wysiwyg-block').hide();
        block.find('.template-select').hide();
    } else if (blockType === 'wysiwyg') {
        block.find('.content-block').hide();
        block.find('.html-block').hide();
        block.find('.wysiwyg-block').show();
        block.find('.template-select').hide();
        block.find('.wysiwyg-editor').val(wysiwygContent);
        
        // Initialize WYSIWYG with slight delay
        setTimeout(function() {
            initWysiwygEditor(block);
            // Force visual mode
            var editorId = block.find('.wysiwyg-editor').attr('id');
            if (tinymce && tinymce.get(editorId)) {
                tinymce.get(editorId).setMode('visual');
            }
        }, 200);
    }
    updatePreview();
}

// Initialize on page load
$(document).ready(function() {
    // Ensure TinyMCE is loaded
    if (typeof wp !== 'undefined' && wp.editor) {
        wp.editor.remove('temp-editor'); // Clean up any existing instances
    }
    
    $('.block-type').each(function() {
        if ($(this).val() === 'wysiwyg') {
            initWysiwygEditor($(this).closest('.block-item'));
        }
    });
});

})(jQuery);