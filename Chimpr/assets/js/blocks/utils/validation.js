/**
 * Block Validation Utilities
 * @module blocks/utils/validation
 * @description Validation utilities for blocks
 */

import { BLOCK_TYPES, BLOCK_CONFIGS } from '../core/block-types';

export class BlockValidator {
    /**
     * Validate block structure
     * @param {Object} block Block to validate
     * @returns {Array} Array of validation errors
     */
    static validateBlock(block) {
        const errors = [];

        if (!block.id) {
            errors.push('Block ID is required');
        }

        if (!block.type || !BLOCK_TYPES[block.type.toUpperCase()]) {
            errors.push(`Invalid block type: ${block.type}`);
        }

        if (!block.timestamp) {
            errors.push('Block timestamp is required');
        }

        const config = BLOCK_CONFIGS[block.type];
        if (config && config.hasEditor && typeof block.content !== 'string') {
            errors.push('Content must be a string for editor blocks');
        }

        return errors;
    }

    /**
     * Validate block content
     * @param {Object} block Block to validate
     * @returns {boolean} Whether content is valid
     */
    static validateContent(block) {
        const config = BLOCK_CONFIGS[block.type];
        if (!config) return false;

        switch (block.type) {
            case BLOCK_TYPES.TEXT:
            case BLOCK_TYPES.HTML:
                return typeof block.content === 'string';
            case BLOCK_TYPES.IMAGE:
                return Boolean(block.content && block.content.url);
            case BLOCK_TYPES.BUTTON:
                return Boolean(block.content && block.content.text && block.content.url);
            case BLOCK_TYPES.SPACER:
                return typeof block.content.height === 'number';
            default:
                return true;
        }
    }

    /**
     * Sanitize block data
     * @param {Object} block Block to sanitize
     * @returns {Object} Sanitized block
     */
    static sanitizeBlock(block) {
        const config = BLOCK_CONFIGS[block.type];
        if (!config) return block;

        return {
            ...block,
            content: this.sanitizeContent(block.type, block.content),
            settings: this.sanitizeSettings(block.type, block.settings)
        };
    }

    /**
     * Sanitize block content
     * @private
     */
    static sanitizeContent(type, content) {
        switch (type) {
            case BLOCK_TYPES.TEXT:
            case BLOCK_TYPES.HTML:
                return String(content || '');
            case BLOCK_TYPES.SPACER:
                return {
                    height: Number(content?.height) || BLOCK_CONFIGS[type].defaultHeight
                };
            default:
                return content;
        }
    }

    /**
     * Sanitize block settings
     * @private
     */
    static sanitizeSettings(type, settings = {}) {
        return {
            ...BLOCK_CONFIGS[type]?.defaultSettings,
            ...settings
        };
    }
} 