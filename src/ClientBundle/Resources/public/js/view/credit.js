define(
    ['core/itemview', './credit_modal'],
    function(ItemView, CreditModal) {
        'use strict';

        return ItemView.extend({
            template: require('../../templates/credit.hbs'),

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