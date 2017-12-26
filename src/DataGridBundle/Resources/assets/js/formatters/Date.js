import moment from 'moment';

export default class DateFormatter {
    static format(value) {
        // @TODO: Get date format from config
        return moment(value).format('DD MMM YYYY');
    }
}