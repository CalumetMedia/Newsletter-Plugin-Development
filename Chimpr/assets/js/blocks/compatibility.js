/**
 * Compatibility Layer
 * @module blocks/compatibility
 * @description Provides backward compatibility for existing code
 */

import { blockStore } from './state/store';
import { BlockOperations } from './operations/block-operations';
import { previewOperations } from './operations/preview-operations';
import { uiOperations } from './operations/ui-operations';
import { stateOperations } from './operations/state-operations';

// Deprecation warning helper
function warnDeprecated(oldName, newName) {
    console.warn(`[Deprecated] ${oldName} is deprecated. Please use ${newName} instead.`);
}

// Map old functions to new implementations
export function initializeCompatibilityLayer() {
    // From block-manager.js
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

    // Core functions
    window.isUpdateInProgress = () => {
        warnDeprecated('isUpdateInProgress', 'stateOperations.isUpdateInProgress');
        return stateOperations.isUpdateInProgress();
    };

    window.setUpdateInProgress = (value) => {
        warnDeprecated('setUpdateInProgress', 'stateOperations.setUpdateInProgress');
        return stateOperations.setUpdateInProgress(value);
    };

    window.storeHtmlContent = ($block) => {
        warnDeprecated('storeHtmlContent', 'stateOperations.storeHtmlContent');
        return stateOperations.storeHtmlContent($block);
    };

    window.restoreHtmlContent = ($block) => {
        warnDeprecated('restoreHtmlContent', 'stateOperations.restoreHtmlContent');
        return stateOperations.restoreHtmlContent($block);
    };

    window.collectPostData = ($block) => {
        warnDeprecated('collectPostData', 'stateOperations.collectPostData');
        return stateOperations.collectPostData($block);
    };

    window.saveBlockState = ($block, isManual, callback) => {
        warnDeprecated('saveBlockState', 'stateOperations.saveBlockState');
        return stateOperations.saveBlockState($block, isManual, callback);
    };

    window.updateBlockVisuals = ($block, isManual) => {
        warnDeprecated('updateBlockVisuals', 'stateOperations.updateBlockVisuals');
        return stateOperations.updateBlockVisuals($block, isManual);
    };

    // From preview.js
    window.updatePreview = (trigger) => {
        warnDeprecated('updatePreview', 'previewOperations.updatePreview');
        return previewOperations.updatePreview(trigger);
    };

    // From block-manager.js
    window.initializeBlock = ($block) => {
        warnDeprecated('initializeBlock', 'uiOperations.initializeBlock');
        return uiOperations.initializeBlock($block);
    };

    window.initializeSortable = ($block) => {
        warnDeprecated('initializeSortable', 'uiOperations.initializeSortable');
        return uiOperations.initializeSortable($block);
    };

    // From auto-save.js
    window.debouncedAutoSave = () => {
        warnDeprecated('debouncedAutoSave', 'Use the new state management system');
        stateOperations.saveBlockState(null, false);
    };
}

// Initialize compatibility layer
document.addEventListener('DOMContentLoaded', () => {
    initializeCompatibilityLayer();
}); 