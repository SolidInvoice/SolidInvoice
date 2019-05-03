define(
    ['core/modal', 'accounting', 'template', 'lodash', 'translator', 'parsley'],
    function(Modal, Accounting, Template, _, __, Parsley) {
        "use strict";

        return Modal.extend({
            'template': Template.client.add_credit,
            'modal': {
                'title': __('client.modal.add_credit'),
                'buttons': {
                    'close': {
                        'close': true,
                        'class': 'warning',
                        'flat': true
                    },
                    'save': {
                        'class': 'success',
                        'save': true
                    }
                },
                'events': {
                    'modal:save': 'saveCredit'
                }
            },
            ui: _.extend({
                'creditAmount': '#credit_amount'
            }, Modal.prototype.ui),
            onBeforeModalSave: Parsley.validate,
            'saveCredit': function() {

                this.ui.creditAmount.parsley().removeError("creditError");

                this.showLoader();

                var view = this;

                this.model.on('invalid', _.bind(this.hideLoader, this));

                this.model.set('credit', Accounting.toFixed(this.ui.creditAmount.val(), 2));

                this.model.save({}, {
                    'success': function() {
                        view.$el.modal('hide');
                    },
                    'error': function() {
                        view.hideLoader();
                    }
                });
            }
        });
    });