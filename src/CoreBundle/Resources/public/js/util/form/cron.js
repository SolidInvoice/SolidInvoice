import $ from 'jquery';
import '../../lib/jquery/jqcron';
import { merge } from 'lodash';
import '../../../css/jqcron.css';

export default function(id, options) {
    const d = new Date();

    return $(id).jqCron(merge({
        enabled_minute: false,
        enabled_hour: false,
        multiple_dom: true,
        multiple_month: true,
        multiple_mins: false,
        multiple_dow: true,
        multiple_time_hours: false,
        multiple_time_minutes: false,
        default_period: 'month',
        no_reset_button: false,
        numeric_zero_pad: true,
        default_value: ( Math.floor(d.getMinutes() / 5) * 5 ) + ' ' + d.getHours() + ' ' + d.getDate() + ' ' + ( d.getMonth() + 1 ) + ' *'
    }, options))
    .jqCronGetInstance();
}
