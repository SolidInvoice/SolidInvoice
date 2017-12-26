import Uri from './formatters/Uri';
import Date from './formatters/Date';
import Status from './formatters/Status';

class CellFormatter {
    _formatters = {};

    constructor() {
        this.register('text', {
            format: value => value
        });

        this.register('uri', Uri);
        this.register('date', Date);
        this.register('client_status', Status);
    }

    register(name, formatter) {
        this._formatters[name] = formatter;
    }

    get(name) {
        return this._formatters[name] || this._formatters['text'];
    }
}

export let formatter = new CellFormatter();