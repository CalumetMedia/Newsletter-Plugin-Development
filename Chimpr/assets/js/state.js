/**
 * state.js
 * This file handles global variables and shared state management for the block manager.
 */

(function (window) {
    // If blockManagerInitialized already exists, respect it; otherwise, set it to false.
    window.blockManagerInitialized = window.blockManagerInitialized || false;

    // If newsletterState already exists, use it. Otherwise, set default properties.
    if (!window.newsletterState) {
        window.newsletterState = {
            blocksLoaded: 0,
            totalBlocks: 0,
            postsData: {},
            isReady: false,
            isUpdateInProgress: false,
            editorContents: {},
            pendingUpdates: new Set(),
        };
    }

    /**
     * Check if update is in progress (shared across the codebase).
     * 
     * @returns {boolean}
     */
    function isUpdateInProgress() {
        // If a global function isPreviewUpdateInProgress() is defined, call it.
        // Otherwise, rely on the local flag in newsletterState.
        if (typeof window.isPreviewUpdateInProgress === 'function') {
            return window.isPreviewUpdateInProgress();
        } else {
            return window.newsletterState.isUpdateInProgress;
        }
    }

    /**
     * Set the update-in-progress status for the entire application.
     * 
     * @param {boolean} value
     */
    function setUpdateInProgress(value) {
        // If a global function setPreviewUpdateInProgress() is defined, call it.
        // Otherwise, update the local flag in newsletterState.
        if (typeof window.setPreviewUpdateInProgress === 'function') {
            window.setPreviewUpdateInProgress(value);
        } else {
            window.newsletterState.isUpdateInProgress = value;
        }
    }

    /**
     * Clear a pending update for a given editorId.
     * 
     * @param {string} editorId
     */
    function clearPendingUpdates(editorId) {
        window.newsletterState.pendingUpdates.delete(editorId);
    }

    // Export functions to the window object so other files can use them.
    window.isUpdateInProgress = isUpdateInProgress;
    window.setUpdateInProgress = setUpdateInProgress;
    window.clearPendingUpdates = clearPendingUpdates;

})(window);
