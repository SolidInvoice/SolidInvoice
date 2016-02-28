define(
    ['core/view', './credit_modal', 'template'],
    function(ItemView, CreditModal, Template) {
        'use strict';

        return ItemView.extend({
            template: Template['client/credit'],

            ui: {
                "addCredit": '#add-credit-button'
            },

            events: {
                "click @ui.addCredit": 'addCredit'
            },

            initialize: function() {
                this.listenTo(this.model, 'sync', this.modelSynced);
            },

            modelSynced: function() {
                this.render();
            },

            addCredit: function(event) {
                event.preventDefault();

                var modal = new CreditModal({
                    model: this.model
                });

                modal.render();
            }
        });
    });