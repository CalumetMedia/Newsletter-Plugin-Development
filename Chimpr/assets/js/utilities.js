/**
 * utilities.js
 * Shared helper functions used across the block manager, sorting, events, etc.
 */

(function ($, window) {
    /**
     * Gathers post info (checkbox states, ordering) from within a block.
     *
     * @param {jQuery} $block - The block element (jQuery-wrapped)
     * @returns {Object} A key-value map of postId -> {checked, order}
     */
    function collectPostData($block) {
        const posts = {};
        const $items = $block.find('.block-posts li');

        $items.each(function (index) {
            const $post = $(this);
            const postId = $post.data('post-id');
            const $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            const currentOrder = $post.find('.post-order').val();

            if ($checkbox.prop('checked')) {
                posts[postId] = {
                    checked: '1',
                    order: currentOrder || index.toString(),
                };
            }
        });

        return posts;
    }

    /**
     * Tracks WYSIWYG editor changes in newsletterState.
     *
     * @param {string} editorId
     * @param {string} content
     */
    function trackEditorChanges(editorId, content) {
        window.newsletterState.editorContents[editorId] = content;
        window.newsletterState.pendingUpdates.add(editorId);
    }

    /**
     * Verifies that the tinymce editor and the <textarea> inside a block are synchronized.
     *
     * @param {jQuery} $block - The block element (jQuery-wrapped)
     * @returns {string} The current content of the editor
     */
    function verifyWysiwygContent($block) {
        const blockIndex = $block.data('index');
        const editorId = 'wysiwyg-editor-' + blockIndex;
        const editorInstance = tinymce.get(editorId);

        if (editorInstance) {
            const content = editorInstance.getContent();
            const textarea = editorInstance.getElement();

            if (content !== textarea.value) {
                textarea.value = content;
            }
            return content;
        }

        return '';
    }

    // Attach our helpers to the window object
    window.collectPostData = collectPostData;
    window.trackEditorChanges = trackEditorChanges;
    window.verifyWysiwygContent = verifyWysiwygContent;

})(jQuery, window);
