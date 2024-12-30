# Known Issues and Solutions

## WYSIWYG Content Lost During Drag and Drop (2024-12-30)
**Issue**: WYSIWYG editor content was being lost when blocks were reordered using drag and drop.
**Solution**: Updated block reordering logic to properly preserve and reinitialize WYSIWYG editors.
**Details**: See `/knowledge-base/2024/12/wysiwyg-drag-drop-issue-20241230-1512.md`

## WYSIWYG Content Not Saving (2024-12-30)
**Issue**: WYSIWYG editor content was not being preserved after save operations.
**Solution**: Updated block data handling in form submissions and AJAX operations to properly preserve WYSIWYG content.
**Details**: See `/knowledge-base/2024/12/wysiwyg-save-issue-20241230-1511.md`

---
*For detailed documentation of issues and their resolutions, see the `/knowledge-base` directory.* 