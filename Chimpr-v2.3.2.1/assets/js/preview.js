(function($) {

window.generatePreview = function() {
    var formData = $('#blocks-form').serializeArray();
    formData.push({ name: 'action', value: 'generate_preview' });
    formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
    formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

    $.ajax({
        url: newsletterData.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#preview-content').html(response.data);
            } else {
                $('#preview-content').html('<p>' + response.data + '</p>');
            }
        },
        error: function(xhr, status, error) {
            $('#preview-content').html('<p>Error generating preview: ' + error + '</p>');
        }
    });
};

window.updatePreview = function() {
    var storyCountValues = {};
    $('.block-story-count').each(function() {
        var $block = $(this).closest('.block-item');
        var blockIndex = $block.data('index');
        storyCountValues[blockIndex] = $(this).val();
    });
    
    console.log('Stored story count values:', storyCountValues);
    
    generatePreview();
    
    setTimeout(function() {
        $('.block-story-count').each(function() {
            var $block = $(this).closest('.block-item');
            var blockIndex = $block.data('index');
            if (storyCountValues[blockIndex]) {
                $(this).val(storyCountValues[blockIndex]);
            }
        });
        console.log('Restored story count values:', storyCountValues);
    }, 100);
};

})(jQuery);
