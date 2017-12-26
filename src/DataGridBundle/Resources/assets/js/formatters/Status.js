import {config} from '@SolidInvoiceUIBundle/Resources/assets/Config'

export default class DateFormatter {
    static format(value) {
        return config.get('status_labels').client[value];
    }
}