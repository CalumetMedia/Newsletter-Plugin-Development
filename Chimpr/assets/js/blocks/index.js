/**
 * Blocks Module Entry Point
 * @module blocks
 * @description Main entry point for blocks functionality
 */

export { BLOCK_TYPES, BLOCK_CONFIGS } from './core/block-types';
export { blockStore } from './state/store';
export { BlockOperations } from './operations/block-operations';
export { BlockValidator } from './utils/validation';
export { previewOperations } from './operations/preview-operations';
export { uiOperations } from './operations/ui-operations';
export { stateOperations } from './operations/state-operations';
export { initializeCompatibilityLayer } from './compatibility';

// Expose necessary functions to window for legacy compatibility
window.isUpdateInProgress = () => stateOperations.isUpdateInProgress();
window.setUpdateInProgress = (value) => stateOperations.setUpdateInProgress(value);
window.collectPostData = ($block) => stateOperations.collectPostData($block);
window.saveBlockState = ($block, isManual, callback) => stateOperations.saveBlockState($block, isManual, callback);
window.updateBlockVisuals = ($block, isManual) => stateOperations.updateBlockVisuals($block, isManual);

/**
 * Initialize blocks functionality
 * @param {Object} config Configuration options
 */
export function initializeBlocks(config = {}) {
    // Initialize compatibility layer first
    initializeCompatibilityLayer();

    // Initialize global state
    window.blockManagerInitialized = false;
    window.newsletterState = {
        blocksLoaded: 0,
        totalBlocks: 0,
        postsData: {},
        isReady: false,
        isUpdateInProgress: false,
        editorContents: {},
        pendingUpdates: new Set(),
        htmlContents: {}
    };

    // Set up event listeners
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Blocks module initialized with config:', config);
        
        // Initialize store with any persisted data
        const persistedState = localStorage.getItem('blocks_state');
        if (persistedState) {
            try {
                const state = JSON.parse(persistedState);
                blockStore.setState(state);
            } catch (error) {
                console.error('Failed to load persisted state:', error);
            }
        }

        // Set up state persistence
        blockStore.subscribe((state) => {
            localStorage.setItem('blocks_state', JSON.stringify(state));
        });

        // Initialize preview system
        previewOperations.initializePreview();

        // Initialize existing blocks
        $('#blocks-container .block-item').each((index, block) => {
            uiOperations.initializeBlock($(block));
        });

        // Initialize sortable container
        $("#blocks-container").accordion({
            header: ".block-header",
            collapsible: true,
            heightStyle: "content",
            icons: false
        });

        // Mark initialization as complete
        window.blockManagerInitialized = true;
        window.newsletterState.isReady = true;
    });
}

/**
 * Add a new block
 * @param {Object} blockData Initial block data
 */
export function addBlock(blockData = {}) {
    const blockIndex = $('#blocks-container .block-item').length;
    const blockHtml = generateBlockHtml(blockIndex, blockData);
    
    $('#blocks-container').append(blockHtml);
    const $newBlock = $('#blocks-container .block-item').last();
    
    // Initialize the new block
    uiOperations.initializeBlock($newBlock);
    
    // Reinitialize accordion
    $("#blocks-container").accordion('destroy').accordion({
        header: ".block-header",
        collapsible: true,
        active: blockIndex,
        heightStyle: "content",
        icons: false
    });
    
    // Update preview
    previewOperations.updatePreview('new_block_added');
}

/**
 * Generate HTML for a new block
 * @private
 */
function generateBlockHtml(blockIndex, data = {}) {
    return `
        <div class="block-item" data-index="${blockIndex}" data-block-index="${blockIndex}">
            <h3 class="block-header">
                <div class="block-header-content">
                    <span class="dashicons dashicons-sort block-drag-handle"></span>
                    <span class="block-title">${newsletterData.blockLabel}</span>
                </div>
            </h3>
            <div class="block-content">
                <!-- Title Row -->
                <div class="block-row">
                    <div class="block-field">
                        <label>${newsletterData.blockTitleLabel}</label>
                        <input type="text" name="blocks[${blockIndex}][title]" class="block-title-input" value="${data.title || ''}">
                    </div>
                    <div class="block-checkbox">
                        <input type="checkbox" name="blocks[${blockIndex}][show_title]" class="show-title-toggle toggle-switch" value="1" id="show-title-${blockIndex}" ${data.show_title ? 'checked' : ''}>
                        <label for="show-title-${blockIndex}">Show Title in Preview</label>
                    </div>
                </div>
                
                <!-- Block Type and Template Row -->
                <div class="block-row">
                    <div class="block-field">
                        <label>Block Type:</label>
                        <select name="blocks[${blockIndex}][type]" class="block-type">
                            ${Object.entries(BLOCK_TYPES).map(([key, value]) => 
                                `<option value="${value}" ${data.type === value ? 'selected' : ''}>${key}</option>`
                            ).join('')}
                        </select>
                    </div>
                </div>

                <div class="content-block" style="display:${data.type === 'content' ? 'block' : 'none'}">
                    <div class="block-posts">
                        <p>${newsletterData.selectCategoryPrompt}</p>
                    </div>
                </div>

                <div class="html-block" style="display:${data.type === 'html' ? 'block' : 'none'}">
                    <div class="block-field">
                        <label>Custom HTML:</label>
                        <textarea name="blocks[${blockIndex}][html]" rows="5" class="block-html">${data.html || ''}</textarea>
                    </div>
                </div>

                <div class="wysiwyg-block" style="display:${data.type === 'wysiwyg' ? 'block' : 'none'}">
                    <div class="block-field">
                        <label>WYSIWYG Content:</label>
                        <textarea name="blocks[${blockIndex}][wysiwyg]" class="wysiwyg-editor-content" id="wysiwyg-editor-${blockIndex}">${data.wysiwyg || ''}</textarea>
                    </div>
                </div>

                <button type="button" class="button button-large action-button remove-button block-remove-btn" data-block-index="${blockIndex}">
                    <span class="dashicons dashicons-trash button-icon"></span>
                    <strong>REMOVE BLOCK</strong>
                </button>
            </div>
        </div>
    `;
} 