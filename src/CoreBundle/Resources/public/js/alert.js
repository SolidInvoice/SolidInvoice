import $ from 'jquery';
import { isFunction, result } from 'lodash';
import Modal from './modal';
import Handlebars from 'handlebars/runtime';

export default {
    alert (message) {
        Handlebars.registerPartial(message, () => message);

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

        let v = new m({});

        v.render();

        Handlebars.unregisterPartial(message);

        return v;
    },
    confirm (message, callback) {
        Handlebars.registerPartial(message, () => message);

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
            confirm () {
                this.showLoader();

                let close = () => {
                    this.$el.modal('hide');
                    this.hideLoader();
                    Handlebars.unregisterPartial(message);
                };

                if (isFunction(callback)) {
                    let response = callback.call(this, true);

                    if (response instanceof Promise || response instanceof $.Deferred || result(response, 'then')) {
                        response.then(close).catch(() => {
                            this.hideLoader();
                        });
                    } else {
                        close.apply(this)
                    }
                } else {
                    close.apply(this);
                }
            }
        });

        let v = new m({});

        v.render();

        return v;
    }
};
