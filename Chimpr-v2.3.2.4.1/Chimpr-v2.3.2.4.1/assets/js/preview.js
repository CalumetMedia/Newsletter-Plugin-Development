(function($) {
    // Single instance of any running preview update
    let previewUpdatePromise = null;
    let previewInitialized = false;

    window.updatePreview = function(skipAutoSave) {
        // Cancel any existing preview update
        if (previewUpdatePromise && previewUpdatePromise.abort) {
            previewUpdatePromise.abort();
        }

        var formData = $('#blocks-form').serializeArray();
        formData.push({ name: 'action', value: 'generate_preview' });
        formData.push({ name: 'newsletter_slug', value: newsletterData.newsletterSlug });
        formData.push({ name: 'security', value: newsletterData.nonceGeneratePreview });

        previewUpdatePromise = $.ajax({
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
            },
            complete: function() {
                previewUpdatePromise = null;
            }
        });
    };

    // Initialize preview functionality
    function initializePreview() {
        if (previewInitialized) return;
        previewInitialized = true;

        // Initial preview update
        updatePreview();

        // Set up direct preview updates for certain changes
        $('#blocks-container').on('change', '.block-type, .block-template', function() {
            updatePreview();
        });
    }

    // Initialize when document and TinyMCE are ready
    $(document).ready(function() {
        if (window.tinyMCE) {
            window.tinyMCE.on('init', function() {
                initializePreview();
            });
        } else {
            initializePreview();
        }
    });

})(jQuery);