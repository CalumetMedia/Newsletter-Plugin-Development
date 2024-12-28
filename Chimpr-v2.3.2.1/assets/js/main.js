(function($) {

    $(document).ready(function() {
        // Ensure TinyMCE is loaded
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('temp-editor'); // Clean up any existing instances
        }

        // Initialize datepickers
        if (typeof initDatepickers === 'function') {
            initDatepickers();
        }

        // Initialize existing blocks
        $('.block-item').each(function() {
            if (typeof initializeBlockEvents === 'function') {
                initializeBlockEvents($(this));
            }
        });

        // Initial preview
        if (typeof updatePreview === 'function') {
            updatePreview();
        }

        // Tab persist
        var activeTab = localStorage.getItem('activeNewsletterTab');
        if (activeTab) {
            $('.nav-tab[data-tab="' + activeTab + '"]').click();
        }

        // Setup send now handler if available
        if (typeof setupSendNowHandler === 'function') {
            setupSendNowHandler();
        }
    });

})(jQuery);