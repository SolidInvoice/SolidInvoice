define(['jquery', 'lodash', 'jquery.jqcron'], function ($, _) {
    "use strict";

    return {
        jqcron: function (id, options) {
            var d = new Date();

            $(id).jqCron(_.merge({
                enabled_minute: false,
                enabled_hour: false,
                multiple_dom: true,
                multiple_month: true,
                multiple_mins: false,
                multiple_dow: true,
                multiple_time_hours: false,
                multiple_time_minutes: false,
                default_period: 'week',
                no_reset_button: false,
                numeric_zero_pad: true,
                default_value: (Math.floor(d.getMinutes() / 5) * 5) + ' ' + d.getHours() + ' ' + d.getDate() + ' ' + (d.getMonth() + 1) + ' *'
            }, options));
        }
    }
});