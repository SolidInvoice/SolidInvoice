import $ from 'jquery';
import { MnObject } from 'backbone.marionette';
import { indexOf } from 'lodash';

export default MnObject.extend({
    prefix: null,
    initialize (prefix, value) {
        this.prefix = prefix;

        const $transport = $(`#${this.prefix}_transport`);

        $transport.on('change', (event) => {
            const value = $(event.target).val();

            this._showSettings(this._getValue(value));
        });

        this._showSettings(this._getValue(value));
    },
    _getValue (value) {
        const index = indexOf(value, '+');
        return value.substr(0, index > -1 ? index : value.length);
    },
    _showSettings (value) {
        this._hideSettings();
        $(`#${this.prefix}_${value}Config`).removeClass('d-none')
    },
    _hideSettings () {
        $('div[id^="config_step_email_settings_transport_"]')
            .addClass('d-none')
    }
});
