(function($) {
    // Date formatting utilities
    window.formatWPDate = function(date) {
        return date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');
    }

    window.formatWPTime = function(date) {
        return String(date.getHours()).padStart(2, '0') + ':' +
            String(date.getMinutes()).padStart(2, '0');
    }

    window.formatWPDateTime = function(date) {
        return formatWPDate(date) + ' ' + formatWPTime(date);
    }

    window.getRoundedDate = function(minutesOffset = 10) {
        const now = new Date();
        const minutes = now.getMinutes();
        const roundedMinutes = Math.ceil((minutes + minutesOffset) / 15) * 15;
        now.setMinutes(roundedMinutes);
        now.setSeconds(0);
        now.setMilliseconds(0);
        return now;
    }

    // Get timezone information for debugging
    window.getTimezoneInfo = function() {
        return {
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            offset: -(new Date().getTimezoneOffset()),
            date: new Date().toString()
        };
    }

    function initializeDatePickers() {
        if ($.fn.datepicker) {
            $('.date-picker').each(function() {
                const $picker = $(this);
                const minDate = $picker.data('min-date') || 0;
                
                $picker.datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: minDate,
                    beforeShow: function(input, inst) {
                        const $calendar = $(inst.dpDiv);
                        setTimeout(function() {
                            $calendar.css({
                                'z-index': 100
                            });
                        }, 0);
                    }
                });
            });
        }
    }

    function initializeTimeSelectors() {
        $('.time-selector').each(function() {
            const $select = $(this);
            if ($select.children().length > 0) return; // Already initialized

            const hours = 24;
            const interval = 15; // 15-minute intervals
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            for (let h = 0; h < hours; h++) {
                for (let m = 0; m < 60; m += interval) {
                    const hour = String(h).padStart(2, '0');
                    const minute = String(m).padStart(2, '0');
                    const time = `${hour}:${minute}`;
                    
                    const option = $('<option>', {
                        value: time,
                        text: time
                    });

                    // Set current time (+15 min rounded up) as default
                    if (h === currentHour && m >= currentMinute) {
                        option.prop('selected', true);
                    }

                    $select.append(option);
                }
            }
        });
    }

    function initializeDateTimeRangePickers() {
        $('.datetime-range-picker').each(function() {
            const $container = $(this);
            const $startDate = $container.find('.start-date');
            const $endDate = $container.find('.end-date');
            const $startTime = $container.find('.start-time');
            const $endTime = $container.find('.end-time');

            // Initialize individual components
            if ($startDate.length) initializeDatePickers($startDate);
            if ($endDate.length) initializeDatePickers($endDate);
            if ($startTime.length) initializeTimeSelectors($startTime);
            if ($endTime.length) initializeTimeSelectors($endTime);

            // Add validation and constraints
            $startDate.on('change', function() {
                const startVal = $(this).val();
                if (startVal) {
                    $endDate.datepicker('option', 'minDate', startVal);
                }
            });

            $endDate.on('change', function() {
                const endVal = $(this).val();
                if (endVal) {
                    $startDate.datepicker('option', 'maxDate', endVal);
                }
            });
        });
    }

    // Initialize all date/time components when document is ready
    $(document).ready(function() {
        initializeDatePickers();
        initializeTimeSelectors();
        initializeDateTimeRangePickers();
    });

})(jQuery);