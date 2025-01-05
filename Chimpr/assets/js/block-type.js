/**
 * block-type.js
 * Manages toggling between block types (content, html, wysiwyg, pdf_link, etc.)
 * and setting up the appropriate fields or editors.
 *
 * Depends on:
 *   - jQuery
 *   - state.js (for isUpdateInProgress, setUpdateInProgress, etc.)
 *   - any utilities like trackEditorChanges (if needed)
 */
(function($, window) {

  /**
   * Toggle block fields and content areas when the block type changes.
   *
   * @param {jQuery} block - The `.block-item` element
   * @param {string} blockType - The new block type string
   */
  function handleBlockTypeChange(block, blockType) {
      const blockIndex = block.data('index');
      console.log('Block type change initiated:', {
          blockIndex: blockIndex,
          oldType: block.find('.block-type').data('previous-type'),
          newType: blockType,
          hasHtmlContent: block.find('.block-html').val() ? true : false,
          htmlContent: block.find('.block-html').val(),
          hasWysiwygContent: block.find('.wysiwyg-editor-content').val() ? true : false
      });

      if (block.data('type-change-in-progress')) {
          return;
      }
      block.data('type-change-in-progress', true);

      const editorId = 'wysiwyg-editor-' + blockIndex;
      let existingContent = '';
      let oldType = block.find('.block-type').data('previous-type');
      
      // If we’re leaving WYSIWYG, capture existing WYSIWYG content
      if (oldType === 'wysiwyg' && tinymce.get(editorId)) {
          existingContent = tinymce.get(editorId).getContent();
          tinymce.execCommand('mceRemoveEditor', true, editorId);
      } 
      // If we’re leaving HTML, grab it from the textarea
      else if (oldType === 'html') {
          existingContent = block.find('.html-block textarea').val();
      }

      // Hide all possible content sections
      block.find('.content-block, .html-block, .wysiwyg-block').hide();

      // Enable/disable appropriate fields based on block type
      setupBlockFields(block, blockType);

      // Show and set up the new content section
      if (blockType === 'content') {
          block.find('.content-block').show();
          setupContentBlock(block); 
          // function likely in block-manager or block-type
      } 
      else if (blockType === 'html') {
          block.find('.html-block').show();
          setupHtmlBlock(block, existingContent);
      } 
      else if (blockType === 'wysiwyg') {
          setupWysiwygBlock(block, existingContent);
      }

      // Remember the new type
      block.find('.block-type').data('previous-type', blockType);
      block.data('type-change-in-progress', false);

      // Trigger a preview update
      if (typeof window.updatePreview === 'function') {
          window.updatePreview('block_type_change');
      }

      // Log final visibility
      console.log('Block visibility after change:', {
          contentBlock: block.find('.content-block').is(':visible'),
          htmlBlock: block.find('.html-block').is(':visible'),
          wysiwygBlock: block.find('.wysiwyg-block').is(':visible')
      });
  }

  /**
   * Enables/disables fields based on the block type.
   *
   * @param {jQuery} block - The `.block-item`
   * @param {string} blockType
   */
  function setupBlockFields(block, blockType) {
      const isContentType = (blockType === 'content');
      const isPdfLinkType = (blockType === 'pdf_link');

      block.find('.category-select select, .date-range-row select, .story-count-row select, .manual-override-toggle')
          .prop('disabled', !(isContentType || isPdfLinkType))
          .closest('div')
          .css('opacity', (isContentType || isPdfLinkType) ? '1' : '0.7');
  }

  /**
   * Sets up the content block area, if any extra steps are needed.
   * (If you have a dedicated function for “content” blocks.)
   *
   * @param {jQuery} block
   */
  function setupContentBlock(block) {
      // For many use cases, this might be blank or minimal.
      // Example: "Ensure .block-posts is visible, etc."
      block.find('.content-block').show();
      // If you need more logic, place it here or in a separate utility.
  }

  /**
   * Sets up the HTML block: show the textarea, populate it with old content, etc.
   *
   * @param {jQuery} block
   * @param {string} existingContent
   */
  function setupHtmlBlock(block, existingContent) {
      const blockIndex = block.data('index');
      console.log('Setting up HTML block:', { blockIndex, existingContent });

      const $container = block.find('.html-block');
      $container.show();

      // Initialize textarea with the old content
      const $textarea = $container.find('textarea');
      if ($textarea.length) {
          console.log(`Setting HTML textarea value for block ${blockIndex}...`);
          $textarea.val(existingContent);
      }
  }

  /**
   * Sets up the WYSIWYG block: remove any old editor instance, create a fresh <textarea>, and re-init.
   *
   * @param {jQuery} block
   * @param {string} existingContent
   */
  function setupWysiwygBlock(block, existingContent) {
      const $container = block.find('.wysiwyg-block');
      $container.show();

      const blockIndex = block.data('index');
      const editorId = 'wysiwyg-editor-' + blockIndex;

      // Clean up existing editor if needed
      if (tinymce.get(editorId)) {
          tinymce.execCommand('mceRemoveEditor', true, editorId);
      }

      // Remove old <textarea>, create a new one with existingContent
      $container.find('textarea').remove();
      $container.append(`
          <textarea 
            id="${editorId}" 
            name="blocks[${blockIndex}][wysiwyg]" 
            class="wysiwyg-editor-content"
          >${existingContent}</textarea>
      `);

      // Then re-init after a short delay
      setTimeout(() => {
          if (typeof window.initWysiwygEditor === 'function') {
              window.initWysiwygEditor(block);
          }
      }, 100);
  }

  // Export our methods to the global scope
  window.handleBlockTypeChange = handleBlockTypeChange;
  window.setupBlockFields = setupBlockFields;
  window.setupContentBlock = setupContentBlock;
  window.setupHtmlBlock = setupHtmlBlock;
  window.setupWysiwygBlock = setupWysiwygBlock;

})(jQuery, window);
