define(
    ['csbillcore/js/app', 'core/view', 'marionette', 'underscore', 'client/model/credit', 'accounting'],
    function(App, ItemView, Mn, _, ClientCreditModel, Accounting) {
        App.addRegions({
            clientCreditRegion: "#client-credit"
        });

        var CreditView = ItemView.extend({
            template: _.template('\
                <div class="text-center text-info"> \
                    <h3 id="client-credit-value">\
                        <%= credit %>\
                    </h3>\
                    <a href="#" rel="tooltip" title="HELLO" id="add-credit-button">\
                        Client Credit\
                    </a>\
                    <div id="credit-ajax-modal" class="modal fade" tabindex="-1"></div>\
                </div>\
            '),
            templateHelpers: function() {
                return {
                    credit: Accounting.formatMoney(this.model.get('credit'))
                }
            },

            initialize: function() {
                this.listenTo(this.model, "change", this.modelChanged);
            },

            modelChanged: function() {
                App.clientCreditRegion.show(this);
            }
        });

        return Mn.Object.extend({
            initialize: function(options) {
                var view = new CreditView({
                    model: new ClientCreditModel({credit: options.credit})
                });

                App.clientCreditRegion.show(view);
            }
        });
    });