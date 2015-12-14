define(
    ['core/view', 'marionette', 'core/modal', 'client/model/credit', 'template', 'translator'],
    function(ItemView, Mn, Modal, ClientCreditModel, Template, __) {
        "use strict";

        var CreditView = ItemView.extend({
            template: Template['client/credit'],

            ui: {
                "addCredit": "#add-credit-button"
            },

            events: {
                "click @ui.addCredit": "addCredit"
            },

            initialize: function() {
                this.listenTo(this.model, "change", this.modelChanged);
            },

            modelChanged: function() {
                this.render();
            },

            addCredit: function (event) {
                event.preventDefault();

                var modal = new Modal({
                    'template' : Template['client/add_credit'],
                    'modal': {
                        'title' : __('client.modal.add_credit'),
                        'buttons': {
                            /*'save' : {
                                'class': 'success'
                            },*/
                            'close' : {
                                'close': true,
                                'class': 'warning',
                                'flat': true
                            }
                        }
                    },
                    'events': {
                        'modal:save': 'saveCredit'
                    },
                    'saveCredit': function () {

                    }
                });

                modal.render();
            }

            /*addCredit: function () {
                var modal = new Modal({
                    'template' : Template('client.credit')
                });

                var view = this;

                modal.on('modal:close', function () {
                    view.model.save();
                });
            }*/

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
                    el: '#client-credit'
                });

                this.view.render();
            }
        });
    });