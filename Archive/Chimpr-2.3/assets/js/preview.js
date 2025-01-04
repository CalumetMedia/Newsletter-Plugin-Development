(function($) {

window.updatePreview = function() {
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

})(jQuery);
