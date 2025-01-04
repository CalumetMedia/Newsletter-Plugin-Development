/**
 * Block State Management Store
 * @module blocks/state/store
 * @description Centralized state management for blocks
 */

class BlockStore {
    constructor() {
        this.state = {
            blocks: [],
            activeBlock: null,
            isDirty: false,
            isLoading: false,
            errors: []
        };
        this.listeners = new Set();
    }

    /**
     * Get current state
     * @returns {Object} Current state
     */
    getState() {
        return { ...this.state };
    }

    /**
     * Update state with new values
     * @param {Object} newState Partial state update
     */
    setState(newState) {
        const oldState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.notifyListeners(oldState);
    }

    /**
     * Subscribe to state changes
     * @param {Function} listener Callback function
     * @returns {Function} Unsubscribe function
     */
    subscribe(listener) {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    /**
     * Notify all listeners of state change
     * @private
     */
    notifyListeners(oldState) {
        this.listeners.forEach(listener => listener(this.state, oldState));
    }
}

export const blockStore = new BlockStore(); 