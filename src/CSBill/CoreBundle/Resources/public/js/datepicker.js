define(['jquery', 'bootstrap.material.datetimepicker'], function ($) {
    /**
     * Datepicker
     */
    $(':input.datepicker').each(function() {
        var el = $(this),
            minDate = el.data('min-date') ? new Date(el.data('min-date')) : null,
            time = el.data('min-date') || false,
            format = el.data('min-date') || 'YYYY-MM-DD';

        var options = {
            'time'   : time,
            'format' : format,
            'minDate': minDate
        };

        el.bootstrapMaterialDatePicker(options);

        if (el.data('depends')) {
            var dependecy = $('#' + el.data('depends'));

            dependecy.on('change', function(e, date) {
                el.bootstrapMaterialDatePicker('setMinDate', date);
            });
        }
    });
});
