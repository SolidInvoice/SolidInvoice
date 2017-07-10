'use strict';

define(['jquery', 'marionette', 'lodash', 'core/modal'], function($, Mn, _, Modal) {
    return {
        alert: function(message) {
            let m = Modal.extend({
                'template': message,
                'modal': {
                    'buttons': {
                        'Close': {
                            'close': true,
                            'class': 'info',
                            'flat': true
                        },
                    }
                }
            });

            let v = new m();

            v.render();

            return v;
        },
        confirm: function(message, callback) {
            let m = Modal.extend({
                'template': message,
                'modal': {
                    'buttons': {
                        'Cancel': {
                            'close': true,
                            'class': 'warning',
                            'flat': true
                        },
                        'OK': {
                            'class': 'success',
                            'flat': true,
                            'save': true
                        }
                    },
                    'events': {
                        'modal:save': 'confirm'
                    }
                },
                confirm: function() {
                    this.showLoader();

                    let close = _.bind(function() {
                        this.$el.modal('hide');
                        this.hideLoader();
                    }, this);

                    if (_.isFunction(callback)) {
                        let response = callback.call(this, true);

                        if (response instanceof Promise || response instanceof $.Deferred || _.result(response, 'then')) {
                            response.then(close);
                        } else {
                            close.apply(this)
                        }
                    } else {
                        close.apply(this);
                    }
                }
            });

            let v = new m();

            v.render();

            return v;
        }
    };
});