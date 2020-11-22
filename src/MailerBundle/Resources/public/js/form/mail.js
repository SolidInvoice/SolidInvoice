import $ from 'jquery';
import { MnObject } from 'backbone.marionette';

export default MnObject.extend({
    prefix: null,
    initialize (prefix, value) {
        value = JSON.parse(value || '{"provider": ""}');

        this.prefix = prefix;

        const $transport = $(`#${this.prefix}_provider_provider`);

        $transport.on('change', (event) => {
            const val = $(event.target).val();

            this._showSettings(val.replace(' ', '-'));
        });

        this._showSettings(value.provider.replace(' ', '-'));
    },
    _showSettings (value) {
        this._hideSettings();
        $(`#${this.prefix}_provider_${value}`).removeClass('d-none').addClass('settings-shown')
    },
    _hideSettings () {
        $('.settings-shown').addClass('d-none')
    }
});
