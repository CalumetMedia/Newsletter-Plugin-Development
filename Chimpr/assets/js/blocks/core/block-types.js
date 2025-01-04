/**
 * Block Types Definition
 * @module blocks/core/block-types
 * @description Defines all available block types and their properties
 */

export const BLOCK_TYPES = {
    TEXT: 'text',
    HTML: 'html',
    IMAGE: 'image',
    SPACER: 'spacer',
    BUTTON: 'button'
};

export const BLOCK_CONFIGS = {
    [BLOCK_TYPES.TEXT]: {
        name: 'Text Block',
        hasEditor: true,
        defaultContent: ''
    },
    [BLOCK_TYPES.HTML]: {
        name: 'HTML Block',
        hasEditor: false,
        defaultContent: ''
    },
    [BLOCK_TYPES.IMAGE]: {
        name: 'Image Block',
        hasEditor: false,
        defaultContent: null
    },
    [BLOCK_TYPES.SPACER]: {
        name: 'Spacer',
        hasEditor: false,
        defaultHeight: 20
    },
    [BLOCK_TYPES.BUTTON]: {
        name: 'Button',
        hasEditor: false,
        defaultText: 'Click Here'
    }
}; 