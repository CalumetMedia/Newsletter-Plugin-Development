/**
 * UI Operations
 * @module blocks/operations/ui-operations
 * @description Handles block UI operations and WYSIWYG editor management
 */

import { blockStore } from '../state/store';
import { previewOperations } from './preview-operations';
import { BlockOperations } from './block-operations';

class UIOperations {
    /**
     * Initialize a block's UI components
     * @param {jQuery} $block Block element
     */
    initializeBlock($block) {
        this.setupBlockEventHandlers($block);
        this.initializeWysiwygEditor($block);
        this.initializeSortable($block);
    }

    /**
     * Set up event handlers for a block
     * @param {jQuery} $block Block element
     * @private
     */
    setupBlockEventHandlers($block) {
        // Block type change
        $block.find('.block-type').on('change', (e) => {
            e.stopPropagation();
            if (window.isUpdateInProgress?.()) return;

            const newBlockType = $(e.target).val();
            const blockIndex = $block.data('index');
            
            // Save content before editor cleanup
            const oldEditorId = `wysiwyg-editor-${blockIndex}`;
            if (tinymce.get(oldEditorId)) {
                const content = tinymce.get(oldEditorId).getContent();
                tinymce.execCommand('mceRemoveEditor', true, oldEditorId);
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(oldEditorId);
                }
                $block.data('stored-content', content);
            }

            this.handleBlockTypeChange($block, newBlockType);
        });

        // Title change
        $block.find('.block-title-input').on('change keyup paste input', () => {
            if (!window.isUpdateInProgress?.()) {
                previewOperations.updatePreview('title_change');
            }
        });

        // Show title toggle
        $block.find('.show-title-toggle').on('change', () => {
            if (window.newsletterState?.isReady) {
                if (typeof debouncedAutoSave === 'function') {
                    debouncedAutoSave();
                }
                previewOperations.updatePreview('show_title_change');
            }
        });

        // Story count change
        $block.find('.block-story-count').on('change', (e) => {
            const storyCount = $(e.target).val();
            const isManual = $block.find('.manual-override-toggle').prop('checked');
            
            if (!isManual) {
                this.handleStoryCountChange($block, storyCount);
            }
        });

        // Manual override toggle
        $block.find('.manual-override-toggle').on('change', (e) => {
            const isManual = $(e.target).prop('checked');
            this.handleManualOverrideToggle($block, isManual);
            
            if (window.newsletterState?.isReady) {
                previewOperations.updatePreview('manual_override_change');
            }
        });
    }

    /**
     * Handle block type changes
     * @param {jQuery} $block Block element
     * @param {string} newType New block type
     * @private
     */
    handleBlockTypeChange($block, newType) {
        // Hide all content sections
        $block.find('.content-block, .html-block, .wysiwyg-block').hide();

        // Show relevant section
        switch (newType) {
            case 'content':
                $block.find('.content-block').show();
                break;
            case 'html':
                $block.find('.html-block').show();
                break;
            case 'wysiwyg':
                $block.find('.wysiwyg-block').show();
                // Initialize WYSIWYG editor
                const blockIndex = $block.data('index');
                const editorId = `wysiwyg-editor-${blockIndex}`;
                this.initializeWysiwygEditor($block);
                break;
        }

        previewOperations.updatePreview('block_type_change');
    }

    /**
     * Initialize WYSIWYG editor for a block
     * @param {jQuery} $block Block element
     * @private
     */
    initializeWysiwygEditor($block) {
        const blockIndex = $block.data('index');
        const editorId = `wysiwyg-editor-${blockIndex}`;
        
        if (!tinymce.get(editorId)) {
            wp.editor.initialize(editorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'paste,lists,link,textcolor,wordpress,wplink,hr,charmap,wptextpattern',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,forecolor,hr',
                    setup: (editor) => {
                        editor.on('change keyup paste input', () => {
                            if (window.isUpdateInProgress?.()) return;
                            
                            editor.save();
                            previewOperations.updatePreview('wysiwyg_content_change');
                        });
                    }
                },
                quicktags: true,
                mediaButtons: true
            });
        }
    }

    /**
     * Initialize sortable functionality for a block
     * @param {jQuery} $block Block element
     * @private
     */
    initializeSortable($block) {
        const $sortableList = $block.find('ul.sortable-posts');
        if (!$sortableList.length) return;

        // Destroy existing sortable if it exists
        if ($sortableList.hasClass('ui-sortable')) {
            $sortableList.sortable('destroy');
        }
        
        const blockIndex = $block.data('index');
        const manualOverride = $block.find('input[name*="[manual_override]"]').prop('checked');
        
        $sortableList.sortable({
            handle: '.story-drag-handle',
            items: '> li',
            axis: 'y',
            cursor: 'move',
            containment: 'parent',
            tolerance: 'pointer',
            disabled: !manualOverride,
            cancel: !manualOverride ? "*" : "",
            update: (event, ui) => {
                if (!manualOverride) {
                    $sortableList.sortable('cancel');
                    return false;
                }
                
                // Update order values
                $sortableList.find('> li').each((index, element) => {
                    $(element).find('.post-order').val(index);
                });
                
                previewOperations.updatePreview('sortable_update');
            }
        });
    }

    /**
     * Handle story count changes
     * @param {jQuery} $block Block element
     * @param {string|number} count New story count
     * @private
     */
    handleStoryCountChange($block, count) {
        const blockIndex = $block.data('index');
        const categoryId = $block.find('select[name*="[category]"]').val();
        const dateRange = $block.find('select[name*="[date_range]"]').val();
        
        if (categoryId) {
            BlockOperations.loadBlockPosts($block, categoryId, blockIndex, dateRange, count);
        }
    }

    /**
     * Handle manual override toggle
     * @param {jQuery} $block Block element
     * @param {boolean} isManual Whether manual override is enabled
     * @private
     */
    handleManualOverrideToggle($block, isManual) {
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

export const uiOperations = new UIOperations(); 