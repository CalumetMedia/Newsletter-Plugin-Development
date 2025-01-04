(function($) {
    // Single instance of initialization tracking
    let mainInitialized = false;

    $(document).ready(function() {
        // Prevent duplicate initialization
        if (mainInitialized) {
            return;
        }
        mainInitialized = true;

        // Ensure TinyMCE is loaded
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('temp-editor'); // Clean up any existing instances
        }

        // Initialize datepickers
        if (typeof initDatepickers === 'function') {
            initDatepickers();
        }

        // Track preview initialization
        let previewInitialized = false;

        // Initialize existing blocks with a slight delay to ensure everything is ready
        setTimeout(function() {
            let initializationPromises = [];

            $('.block-item').each(function() {
                if (typeof initializeBlockEvents === 'function') {
                    const promise = new Promise((resolve) => {
                        initializeBlockEvents($(this));
                        resolve();
                    });
                    initializationPromises.push(promise);
                }
            });

            // Wait for all blocks to initialize before initial preview
            Promise.all(initializationPromises).then(() => {
                // Only update preview if no blocks have been loaded yet
                if (!previewInitialized && typeof updatePreview === 'function' && !$('.block-item[data-posts-loaded]').length) {
                    previewInitialized = true;
                    updatePreview();
                }
            });
        }, 100);

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