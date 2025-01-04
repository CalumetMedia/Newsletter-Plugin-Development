(function($) {

window.updateBlockDates = function(block) {
    var days = parseInt(block.find('.block-date-range-select').val());
    var endDateStr = block.find('.block-end-date').val();
    var endDate = new Date(endDateStr + 'T23:59:59'); // Set to end of day

    // Calculate start date by subtracting days
    var startDate = new Date(endDate);
    startDate.setDate(endDate.getDate() - days);
    startDate.setHours(0,0,0,0); // Set to start of day

    var formattedStart = startDate.toISOString().split('T')[0];
    var formattedEnd = endDate.toISOString().split('T')[0];

    block.find('.block-start-date').val(formattedStart);
    block.find('.block-start-date-display .date').text(formattedStart);

    return {
        startDate: formattedStart,
        endDate: formattedEnd
    };
};

window.initDatepickers = function() {
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showAnim: "slideDown",
        onSelect: function() {
            $('#blocks-container .block-item').each(function() {
                var block = $(this);
                var blockType = block.find('.block-type').val();
                var categoryId = block.find('.block-category').val();
                var currentIndex = block.data('index');

                if (blockType === 'content' && categoryId) {
                    loadBlockPosts(block, categoryId, currentIndex);
                }
            });
        }
    });
};

})(jQuery);
