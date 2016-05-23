define(['jquery', 'marionette', 'lodash'], function($, Mn, _) {

    var MailSettings = Mn.Object.extend({
	gmailConfig: null,
	smtpConfig: null,
	initialize: function(prefix, value) {
            this.prefix = prefix;
            this.value = value;

	    this._init();
        },
	_init: function() {
	    var transport = $('#' + this.prefix + '_transport'),

		smtpConfig = [
		    'host', 'port', 'encryption', 'user', 'password'
		],
		gmailConfig = [
		    'user', 'password'
		],
		that = this;

	    _.forEach({"smtpConfig": smtpConfig, 'gmailConfig': gmailConfig}, function(values, type) {
		that[type] = $(_.map(values, _.bind(function(value) {
		    return '#' + that.prefix + '_' + value;
		}, this)).join(',')).parent('.form-group');
	    });

            transport.on('change', _.bind(function(event) {
                var value = $(event.target).val();
                this._setSettings(value);
            }, this));

            this._setSettings(this.value);
        },
	_setSettings: function(value) {
            if ('smtp' === value) {
                this._showSmtpSettings();
            } else if ('gmail' === value) {
                this._showGmailSettings();
            } else {
                this._hideSettings();
            }
        },
        _showSmtpSettings: function() {
	    this.smtpConfig.show();
        },
        _showGmailSettings: function() {
	    this.smtpConfig.hide();
            this.gmailConfig.show();
        },
        _hideSettings: function() {
	    this.smtpConfig.hide();
            this.gmailConfig.hide();
        }
    });

    return function(prefix, value) {
	return new MailSettings(prefix, value);
    };
});