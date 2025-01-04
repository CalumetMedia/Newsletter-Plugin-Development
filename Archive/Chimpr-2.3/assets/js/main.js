(function($) {

    $(document).ready(function() {
        // Ensure TinyMCE is loaded
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('temp-editor'); // Clean up any existing instances
        }

        // Initialize datepickers
        initDatepickers();

        // Initialize existing blocks
        $('.block-item').each(function() {
            initializeBlockEvents($(this));
        });

        // Initial preview
        updatePreview();

        // Tab persist
        var activeTab = localStorage.getItem('activeNewsletterTab');
        if (activeTab) {
            $('.nav-tab[data-tab="' + activeTab + '"]').click();
        }

        // Removed toggleScheduleControls() since it's no longer defined

        // Setup send now handler
        setupSendNowHandler();
    });

})(jQuery);
