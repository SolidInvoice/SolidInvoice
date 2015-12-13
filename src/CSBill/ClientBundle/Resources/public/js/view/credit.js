define(
    ['core/view', 'marionette', 'underscore', 'client/model/credit', 'accounting'],
    function(ItemView, Mn, _, ClientCreditModel, Accounting) {
        "use strict";

        var CreditView = ItemView.extend({
            template: _.template('\
                <div class="text-center text-info"> \
                    <h3 id="client-credit-value">\
                        <%= credit %>\
                    </h3>\
                    <a href="/app_dev.php/clients/ajax/info/2" rel="tooltip" title="HELLO" id="add-credit-button">\
                        Client Credit\
                    </a>\
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
                this.render();
            },

            ui: {
                "addCredit": "#add-credit-button"
            },

            events: {
                "click @ui.addCredit": "ajaxModel"
            }

            /*addCredit: function(e) {

                var ajaxModel = new AjaxModel({
                    model: this.model
                });

                e.preventDefault();

                this.$el.find(e.target).ajaxModal('#credit-ajax-modal', function() {
                    var modal = $(this.$modal),
                        addCredit = function(evt) {
                            var form = $(this);

                            evt.preventDefault();

                            modal.modal('loading');

                            $.ajax({
                                "url": form.attr('action'),
                                "dataType": "json",
                                "data": form.serialize(),
                                "method": "post",
                                "success": function(data) {
                                    if ('success' === data.status) {
                                        modal.modal('hide');
                                        $('#client-credit-value').text(accounting.formatMoney(data.amount));
                                    } else {
                                        modal.html(data.content);
                                        $('form', modal).on('submit', addCredit);
                                    }
                                }
                            });
                        };

                    $('form', modal).on('submit', addCredit);
                });
            }*/
        });

        return Mn.Object.extend({
            view: null,
            initialize: function(options) {
                this.view = new CreditView({
                    model: new ClientCreditModel({credit: options.credit}),
                    el: "#client-credit"
                });
            }
        });
    });