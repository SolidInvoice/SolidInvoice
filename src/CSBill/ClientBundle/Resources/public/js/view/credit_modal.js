define(
    ['core/modal', 'accounting', 'template', 'translator'],
    function(Modal, Accounting, Template, __) {
        "use strict";

        return Modal.extend({
            'template': Template['client/add_credit'],
            'modal': {
                'title': __('client.modal.add_credit'),
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
            'saveCredit': function() {
                this.showLoader();

                var view = this;

                this.model.on('invalid', function (model, error) {
                    view.hideLoader();
                    alert(error);
                });

                this.model.set('credit', Accounting.toFixed(this.ui.creditAmount.val(), 2));

                this.model.save({}, {
                    'success': function() {
                        view.$el.modal('hide');
                    }
                });
            }
        });
    });