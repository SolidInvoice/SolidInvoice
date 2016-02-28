define(['jquery', 'marionette', 'lodash'], function($, Mn, _) {

    return Mn.Object.extend({
        gmailConfig     : null,
        smtpSettings    : null,
        initialize      : function(prefix, value) {
            this.prefix = prefix;
            this.value = value;

            $(_.bind(this._init, this));
        },
        _init: function () {
            var transport = $('#' + this.prefix + '_transport');

            this.smtpSettings = $('#' + this.prefix + '_host, #' + this.prefix + '_port, #' + this.prefix + '_encryption, #' + this.prefix + '_user, #' + this.prefix + '_password').parent('.form-group');
            this.gmailConfig = $('#' + this.prefix + '_user, #' + this.prefix + '_password').parent('.form-group');

            transport.on('change', _.bind(function(event) {
                var value = $(event.target).val();
                this._setSettings(value);
            }, this));

            this._setSettings(this.value);
        },
        _setSettings: function (value) {
            if ('smtp' === value) {
                this._showSmtpSettings();
            } else if ('gmail' === value) {
                this._showGmailSettings();
            } else {
                this._hideSettings();
            }
        },
        _showSmtpSettings: function() {
            this.smtpSettings.show();
        },
        _showGmailSettings: function() {
            this.smtpSettings.show();
            this.gmailConfig.show();
        },
        _hideSettings: function() {
            this.smtpSettings.hide();
            this.gmailConfig.hide();
        }
    });
});