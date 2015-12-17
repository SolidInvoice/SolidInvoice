define(
    ['core/view', 'marionette', 'client/view/credit_modal', 'client/model/credit', 'template', 'translator'],
    function(ItemView, Mn, CreditModal, ClientCreditModel, Template, __) {
        "use strict";

        return ItemView.extend({
            template: Template['client/credit'],

            ui: {
                "addCredit": "#add-credit-button"
            },

            events: {
                "click @ui.addCredit": "addCredit"
            },

            initialize: function() {
                this.listenTo(this.model, "sync", this.modelSynced);
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