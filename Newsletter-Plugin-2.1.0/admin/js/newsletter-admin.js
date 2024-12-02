jQuery(document).ready(function($) {
    // Initialize the datepicker
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown",
        onSelect: function() {
            fetchStories();
        }
    });

    // Function to fetch stories
    function fetchStories() {
        const startDate = $("#start_date").val();
        const endDate = $("#end_date").val();

        if (startDate && endDate) {
            $.ajax({
                url: newsletterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_stories_list',
                    start_date: startDate,
                    end_date: endDate,
                    newsletter_id: newsletterData.newsletterId,
                    security: newsletterData.ajaxNonce
                },
                beforeSend: function() {
                    $("#stories-container").html('<p>Loading stories...</p>');
                    $("#preview-content").html('<p>Select stories to see a preview here.</p>');
                },
                success: function(response) {
                    if (response.success) {
                        $("#stories-container").html(response.data);
                        setupSortable();
                        setupCheckboxes();
                        loadStoryPreview();
                    } else {
                        $("#stories-container").html('<p>' + response.data + '</p>');
                    }
                },
                error: function() {
                    $("#stories-container").html('<p>Error loading stories. Please try again.</p>');
                }
            });
        }
    }

    // Initialize sortable
    function setupSortable() {
        $(".stories-sortable").sortable({
            placeholder: "sortable-placeholder",
            update: function() {
                loadStoryPreview();
            }
        });
        $(".stories-sortable").disableSelection();
    }

    // Setup checkboxes
    function setupCheckboxes() {
        $('#select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.stories-sortable input[type="checkbox"]').prop('checked', isChecked);
            loadStoryPreview();
        });

        $('.stories-sortable input[type="checkbox"]').on('change', function() {
            var allChecked = $('.stories-sortable input[type="checkbox"]').length === $('.stories-sortable input[type="checkbox"]:checked').length;
            $('#select-all').prop('checked', allChecked);
            loadStoryPreview();
        });

        // Add padding around "Select All" label
        $('#select-all').closest('label').css('padding', '10px 0');
    }

    // Load story preview
    function loadStoryPreview() {
        const selectedPostIds = [];

        $('.stories-sortable .story-item').each(function() {
            const checkbox = $(this).find('input[type="checkbox"]');
            if (checkbox.is(':checked')) {
                selectedPostIds.push($(this).data('post-id'));
            }
        });

        // Get the selected template ID
        const selectedTemplateId = $('#selected_template_id').val() || 'default';

        if (selectedPostIds.length > 0) {
            $.ajax({
                url: newsletterData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'load_story_preview',
                    post_ids: selectedPostIds,
                    newsletter_id: newsletterData.newsletterId,
                    template_id: selectedTemplateId,
                    security: newsletterData.ajaxNonce
                },
                success: function(response) {
                    if (response.success) {
                        // Wrap the preview content in a container to scope CSS
                        $('#preview-content').html('<div class="newsletter-preview">' + response.data + '</div>');
                    } else {
                        $('#preview-content').html('<p>' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#preview-content').html('<p>Error loading story previews. Please try again.</p>');
                }
            });
        } else {
            $('#preview-content').html('<p>Select stories to see a preview here.</p>');
        }
    }

    // Fetch stories on page load if dates are already selected
    if ($("#start_date").val() && $("#end_date").val()) {
        fetchStories();
    }
});
