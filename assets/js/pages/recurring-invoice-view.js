import $ from 'jquery'
import 'SolidInvoiceCore/js/lib/jquery/jqcron';

$(() => {
    import(/* webpackMode: "eager" */ '~/config').then((config) => {
        const instance =  $('#recurring').jqCron({
            enabled_minute: true,
            enabled_hour: true,
            no_reset_button: true,
            numeric_zero_pad: true,
            multiple_dom: true,
            multiple_month: true,
            multiple_mins: false,
            multiple_dow: true,
            multiple_time_hours: false,
            multiple_time_minutes: false,
            default_period: 'month',
            default_value: config.default.module.data.frequency,
        }).jqCronGetInstance();

        instance.disable();
    });
});
