/**
 * State Operations
 * @module blocks/operations/state-operations
 * @description Handles block state management and updates
 */

import { blockStore } from '../state/store';
import { previewOperations } from './preview-operations';

class StateOperations {
    /**
     * Check if an update is in progress
     * @returns {boolean} Whether an update is in progress
     */
    isUpdateInProgress() {
        return typeof window.isPreviewUpdateInProgress === 'function' ? 
            window.isPreviewUpdateInProgress() : false;
    }

    /**
     * Set update in progress state
     * @param {boolean} value New state value
     */
    setUpdateInProgress(value) {
        if (typeof window.setPreviewUpdateInProgress === 'function') {
            window.setPreviewUpdateInProgress(value);
        }
    }

    /**
     * Store HTML content for a block
     * @param {jQuery} $block Block element
     */
    storeHtmlContent($block) {
        const blockIndex = $block.data('index');
        const $textarea = $block.find('textarea[name*="[html]"]');
        if ($textarea.length) {
            window.newsletterState.htmlContents[blockIndex] = $textarea.val();
            console.log(`Stored HTML content for block ${blockIndex}, length: ${$textarea.val().length}`);
        }
    }

    /**
     * Restore HTML content for a block
     * @param {jQuery} $block Block element
     */
    restoreHtmlContent($block) {
        const blockIndex = $block.data('index');
        const $textarea = $block.find('textarea[name*="[html]"]');
        if ($textarea.length && window.newsletterState.htmlContents[blockIndex]) {
            const content = window.newsletterState.htmlContents[blockIndex];
            $textarea.val(content);
            console.log(`Restored HTML content for block ${blockIndex}, length: ${content.length}`);
        }
    }

    /**
     * Collect post data from a block
     * @param {jQuery} $block Block element
     * @returns {Object} Post data
     */
    collectPostData($block) {
        const posts = {};
        const $items = $block.find('.block-posts li');
        
        $items.each(function(index) {
            const $post = $(this);
            const postId = $post.data('post-id');
            const $checkbox = $post.find('input[type="checkbox"][name*="[checked]"]');
            const currentOrder = $post.find('.post-order').val();
            
            if ($checkbox.prop('checked')) {
                posts[postId] = {
                    checked: '1',
                    order: currentOrder || index.toString()
                };
            }
        });
        
        return posts;
    }

    /**
     * Save block state
     * @param {jQuery} $block Block element
     * @param {boolean} isManual Whether manual override is enabled
     * @param {Function} callback Callback function
     * @returns {Promise} Save promise
     */
    saveBlockState($block, isManual, callback) {
        const blocks = [];
        
        $('#blocks-container .block-item').each((index, element) => {
            const $currentBlock = $(element);
            const blockType = $currentBlock.find('.block-type').val();
            
            const blockData = {
                type: blockType,
                title: $currentBlock.find('.block-title-input').val(),
                show_title: $currentBlock.find('.show-title-toggle').prop('checked') ? 1 : 0,
                template_id: $currentBlock.find('.block-template').val() || '0',
                category: $currentBlock.find('.block-category').val() || '',
                date_range: $currentBlock.find('.block-date-range').val() || '7',
                story_count: $currentBlock.find('.block-story-count').val() || 'disable',
                manual_override: ($currentBlock.is($block) ? isManual : $currentBlock.find('.manual-override-toggle').prop('checked')) ? 1 : 0,
                posts: this.collectPostData($currentBlock)
            };
            
            blocks.push(blockData);
        });

        return $.ajax({
            url: newsletterData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_newsletter_blocks',
                security: newsletterData.nonceSaveBlocks,
                newsletter_slug: newsletterData.newsletterSlug,
                blocks: blocks
            },
            success: (response) => {
                if (response.success && callback) {
                    callback(response);
                }
            }
        });
    }

    /**
     * Update block visuals
     * @param {jQuery} $block Block element
     * @param {boolean} isManual Whether manual override is enabled
     */
    updateBlockVisuals($block, isManual) {
        const $postsList = $block.find('.sortable-posts');
        const $checkboxes = $postsList.find('input[type="checkbox"]');
        
        $postsList.css({
            'pointer-events': isManual ? 'auto' : 'none',
            'opacity': isManual ? '1' : '0.7'
        });
        
        $checkboxes.prop('disabled', !isManual);
        $postsList.find('.story-drag-handle').css('cursor', isManual ? 'move' : 'default');
        
        const $storyCount = $block.find('.block-story-count');
        $storyCount.prop('disabled', isManual);
        $storyCount.css('opacity', isManual ? '0.7' : '1');
    }
}

export const stateOperations = new StateOperations(); 