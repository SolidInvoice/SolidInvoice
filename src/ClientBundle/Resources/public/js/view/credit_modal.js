define(
    ['core/modal', 'accounting', 'translator', 'parsley'],
    function(Modal, Accounting, __, Parsley) {
        "use strict";

        return Modal.extend({
            'template': require('../../templates/add_credit.hbs'),
            'modal': {
                'title': 'client.modal.add_credit', //__('client.modal.add_credit'),
                'buttons': {
                    'save': {
                        'class': 'success',
                        'save': true
                    },
                    'close': {
                        'close': true,
                        'class': 'warning',
                        'flat': true
                    }
                },
                'events': {
                    'modal:save': 'saveCredit'
                }
            },
            ui: {
                'creditAmount': '#credit_amount'
            },
            onBeforeModalSave: Parsley.validate,
            'saveCredit': function() {

                window.ParsleyUI.removeError(this.ui.creditAmount.parsley(), "creditError");

                this.showLoader();

                var view = this;

                this.model.on('invalid', function (model, error) {
                    view.hideLoader();
                    //window.ParsleyUI.addError(view.ui.creditAmount.parsley(), "creditError", error);
                });

                this.model.set('credit', Accounting.toFixed(this.ui.creditAmount.val(), 2));

                this.model.save({}, {
                    'success': function() {
                        view.$el.modal('hide');
                    },
                    'error': function () {
                        view.hideLoader();
                    }
                });
            }
        });
    });