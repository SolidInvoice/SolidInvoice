import $ from 'jquery';
import { MnObject } from 'backbone.marionette';
import { forEach, map } from 'lodash';

export default MnObject.extend({
    gmailConfig: null,
    smtpConfig: null,
    initialize (prefix, value) {
        this.prefix = prefix;
        this.value = value;

        const transport = $('#' + this.prefix + '_transport'),
            smtpConfig = [
                'host', 'port', 'encryption', 'user', 'password'
            ],
            gmailConfig = [
                'user', 'password'
            ];

        forEach({ 'smtpConfig': smtpConfig, 'gmailConfig': gmailConfig }, (values, type) => {
            this[type] = $(map(values, (val) => `#{this.prefix}_${val}`).join(',')).parent('.form-group');
        });

        transport.on('change', (event) => {
            this._setSettings($(event.target).val());
        });

        this._setSettings(this.value);
    },
    _setSettings (value) {
        if ('smtp' === value) {
            this._showSmtpSettings();
        } else if ('gmail' === value) {
            this._showGmailSettings();
        } else {
            this._hideSettings();
        }
    },
    _showSmtpSettings () {
        this.smtpConfig.show();
    },
    _showGmailSettings () {
        this.smtpConfig.hide();
        this.gmailConfig.show();
    },
    _hideSettings () {
        this.smtpConfig.hide();
        this.gmailConfig.hide();
    }
});
