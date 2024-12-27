(function($) {
    window.initializeSortable = function(block) {
        var sortableList = block.find('ul.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.story-drag-handle',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
                }
            });
        }
    };

    window.initializeBlockEvents = function(block) {
        initializeSortable(block);
        block.find('input[type="checkbox"]').off('change').on('change', updatePreview);
        block.find('.block-template').off('change').on('change', updatePreview);

        // When category changes, load new posts
        block.find('.block-category').off('change').on('change', function() {
            var categoryId = $(this).val();
            if (categoryId) {
                loadBlockPosts(block, categoryId, block.data('index'));
            } else {
                block.find('.block-posts').html('<p>Please select a category to display posts.</p>');
            }
        });

        var blockType = block.find('.block-type').val();
        if (blockType === 'html' || blockType === 'wysiwyg') {
            block.find('.template-select').hide();
        }
    };

    window.updateBlockIndices = function() {
        $('#blocks-container .block-item').each(function(index) {
            $(this).attr('data-index', index);
            var customTitle = $(this).find('.block-title-input').val();
            $(this).find('.block-title').text(customTitle || newsletterData.blockLabel);
            $(this).find('.block-title-input').attr('name', 'blocks[' + index + '][title]');
            $(this).find('.block-type').attr('name', 'blocks[' + index + '][type]');
            $(this).find('.block-template').attr('name', 'blocks[' + index + '][template_id]');
            $(this).find('.block-category').attr('name', 'blocks[' + index + '][category]');

            $(this).find('.wysiwyg-editor').attr('name', 'blocks[' + index + '][wysiwyg]');

            $(this).find('.block-posts input[type="checkbox"]').each(function() {
                var postId = $(this).closest('li').data('post-id');
                $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][selected]');
            });
            $(this).find('.block-posts .post-order').each(function() {
                var postId = $(this).closest('li').data('post-id');
                $(this).attr('name', 'blocks[' + index + '][posts][' + postId + '][order]');
            });
            $(this).find('.html-block textarea').attr('name', 'blocks[' + index + '][html]');
        });

        window.blockIndex = $('#blocks-container .block-item').length;
    };

    window.resetBlock = function(block) {
        const sortableList = block.find('.sortable-posts');
        const postCount = parseInt(block.find('.block-post-count').val() || 5);
        const posts = sortableList.find('li').toArray();

        posts.sort((a, b) => {
            return $(a).index() - $(b).index();
        });

        sortableList.empty();
        posts.forEach((post, index) => {
            $(post).find('input[type="checkbox"]').prop('checked', index < postCount);
            $(post).find('.post-order').val(index);
            sortableList.append(post);
        });

        updatePreview();
    };

    window.initializeBlockUI = function(block) {
        const sortableList = block.find('.sortable-posts');
        if (sortableList.length) {
            sortableList.sortable({
                handle: '.dashicons-menu',
                update: function() {
                    sortableList.find('li').each(function(index) {
                        $(this).find('.post-order').val(index);
                    });
                    updatePreview();
                }
            });
        }
        block.find('input[type="checkbox"]').on('change', updatePreview);
    };

    window.addBlock = function() {
        var blockHtml = '<div class="block-item" data-index="' + blockIndex + '">';
        blockHtml += '<div class="block-header">';
        blockHtml += '<span class="dashicons dashicons-sort block-drag-handle"></span>';
        blockHtml += '<span class="block-title">' + newsletterData.blockLabel + '</span>';
        blockHtml += '<span class="dashicons dashicons-arrow-down-alt2 block-accordion-toggle"></span>';
        blockHtml += '</div>';
        blockHtml += '<div class="block-content" style="display: none;">';
        blockHtml += '<div class="title-row" style="display: flex; align-items: center; margin-bottom: 10px;">';
        blockHtml += '<div style="width: 25%;"><label>' + newsletterData.blockTitleLabel + '</label>';
        blockHtml += '<input type="text" name="blocks[' + blockIndex + '][title]" class="block-title-input" /></div>';
        blockHtml += '<div style="margin-left: 15px;"><label><input type="checkbox" name="blocks[' + blockIndex + '][show_title]" class="show-title-toggle" value="1" checked>Show Title in Preview</label></div>';
        blockHtml += '</div>';

        blockHtml += '<label>' + newsletterData.blockTypeLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][type]" class="block-type">';
        blockHtml += '<option value="content">' + newsletterData.contentLabel + '</option>';
        blockHtml += '<option value="html">HTML</option>';
        blockHtml += '<option value="wysiwyg">WYSIWYG Editor</option>';
        blockHtml += '</select>';

        blockHtml += '<div class="template-select">';
        blockHtml += '<label>' + newsletterData.templateLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][template_id]" class="block-template">';
        blockHtml += '<option value="default">Default Template</option>';
        if (newsletterData.availableTemplates) {
            $.each(newsletterData.availableTemplates, function(index, template) {
                if (template.id !== 'default') {
                    blockHtml += '<option value="' + template.id + '">' + template.name + '</option>';
                }
            });
        }
        blockHtml += '</select></div>';

        blockHtml += '<div class="wysiwyg-block" style="display:none;">';
        blockHtml += '<label>Content:</label>';
        blockHtml += '<textarea class="wysiwyg-editor" id="wysiwyg-' + blockIndex + '" ';
        blockHtml += 'name="blocks[' + blockIndex + '][wysiwyg]" style="width:100%; height:300px;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<div class="content-block">';
        blockHtml += '<label>' + newsletterData.selectCategoryLabel + '</label>';
        blockHtml += '<select name="blocks[' + blockIndex + '][category]" class="block-category">';
        blockHtml += '<option value="">' + newsletterData.selectCategoryOption + '</option>';
        $.each(newsletterData.categories, function(index, category) {
            blockHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });
        blockHtml += '</select>';

        blockHtml += '<div class="block-posts"></div></div>';

        blockHtml += '<div class="html-block" style="display:none;">';
        blockHtml += '<label>HTML</label>';
        blockHtml += '<textarea name="blocks[' + blockIndex + '][html]" rows="5" style="width:100%;"></textarea>';
        blockHtml += '</div>';

        blockHtml += '<button type="button" class="button remove-block">' + newsletterData.removeBlockLabel + '</button>';
        blockHtml += '</div></div>';

        $('#blocks-container').append(blockHtml);
        blockIndex++;

        var newBlock = $('#blocks-container .block-item').last();
        initializeBlockEvents(newBlock);
        updatePreview();
    };

    window.handleBlockTypeChange = function(block, blockType) {
        var wysiwygContent = block.find('.wysiwyg-editor').val();
        if (blockType === 'content') {
            block.find('.content-block').show();
            block.find('.html-block').hide();
            block.find('.wysiwyg-block').hide();
            block.find('.template-select').show();
        } else if (blockType === 'html') {
            block.find('.content-block').hide();
            block.find('.html-block').show();
            block.find('.wysiwyg-block').hide();
            block.find('.template-select').hide();
        } else if (blockType === 'wysiwyg') {
            block.find('.content-block').hide();
            block.find('.html-block').hide();
            block.find('.wysiwyg-block').show();
            block.find('.template-select').hide();
            block.find('.wysiwyg-editor').val(wysiwygContent);
            initWysiwygEditor(block);
            setTimeout(function() {
                var editorId = block.find('.wysiwyg-editor').attr('id');
                if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                    tinymce.get(editorId).setMode('visual');
                }
            }, 200);
        }
        updatePreview();
    };

})(jQuery);
