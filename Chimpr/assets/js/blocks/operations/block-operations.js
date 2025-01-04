/**
 * Block Operations
 * @module blocks/operations/block-operations
 * @description Handles all block CRUD operations
 */

import { blockStore } from '../state/store';
import { BLOCK_TYPES, BLOCK_CONFIGS } from '../core/block-types';

export class BlockOperations {
    /**
     * Create a new block
     * @param {string} type Block type from BLOCK_TYPES
     * @param {Object} initialData Initial block data
     * @returns {Object} Created block
     */
    static createBlock(type, initialData = {}) {
        const config = BLOCK_CONFIGS[type];
        if (!config) throw new Error(`Invalid block type: ${type}`);

        const block = {
            id: Date.now(),
            type,
            content: initialData.content || config.defaultContent,
            settings: initialData.settings || {},
            timestamp: new Date().toISOString()
        };

        const state = blockStore.getState();
        blockStore.setState({
            blocks: [...state.blocks, block],
            isDirty: true
        });

        return block;
    }

    /**
     * Update existing block
     * @param {number} blockId Block ID to update
     * @param {Object} updates Updates to apply
     */
    static updateBlock(blockId, updates) {
        const state = blockStore.getState();
        const blockIndex = state.blocks.findIndex(b => b.id === blockId);
        
        if (blockIndex === -1) throw new Error(`Block not found: ${blockId}`);

        const updatedBlocks = [...state.blocks];
        updatedBlocks[blockIndex] = {
            ...updatedBlocks[blockIndex],
            ...updates,
            timestamp: new Date().toISOString()
        };

        blockStore.setState({
            blocks: updatedBlocks,
            isDirty: true
        });
    }

    /**
     * Delete block by ID
     * @param {number} blockId Block ID to delete
     */
    static deleteBlock(blockId) {
        const state = blockStore.getState();
        blockStore.setState({
            blocks: state.blocks.filter(b => b.id !== blockId),
            isDirty: true
        });
    }

    /**
     * Reorder blocks
     * @param {number} fromIndex Original position
     * @param {number} toIndex New position
     */
    static reorderBlocks(fromIndex, toIndex) {
        const state = blockStore.getState();
        const blocks = [...state.blocks];
        const [movedBlock] = blocks.splice(fromIndex, 1);
        blocks.splice(toIndex, 0, movedBlock);

        blockStore.setState({
            blocks,
            isDirty: true
        });
    }
} 