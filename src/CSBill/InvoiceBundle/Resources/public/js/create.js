define(
    ['core/module', 'jquery', 'marionette', 'backbone', 'lodash', 'template', 'accounting', 'client/view/client_select', 'core/billing/model/row_model', 'core/billing/model/billing_model'],
    function(Module, $, Mn, Backbone, _, Template, Accounting, ClientSelectView, ViewModel, InvoiceModel) {
        "use strict";

        var invoiceModel = new InvoiceModel(),
            viewModel = new ViewModel();

        var Collection = Backbone.Collection.extend({
            initialize: function() {
                this.listenTo(this, 'change reset add remove', this.updateTotals);
            },
            updateTotals: function() {
                var total = 0,
                    tax = 0,
                    subTotal = 0;

                _.each(this.models, function(model) {
                    var rowTotal = model.get('total'),
                        rowTax = model.get('tax');

                    total += rowTotal;
                    subTotal += rowTotal;

                    if (!_.isEmpty(rowTax)) {
                        var taxAmount = rowTotal * parseFloat(rowTax.rate);
                        tax += taxAmount;

                        if ('inclusive' === rowTax.type) {
                            subTotal -= taxAmount;
                        } else {
                            total += taxAmount;
                        }
                    }
                });

                var discount = (total * invoiceModel.get('total')) / 100;

                viewModel.set('subTotal', subTotal);
                viewModel.set('total', total - discount);
                viewModel.set('discount', discount);
                viewModel.set('tax', tax);

                this.trigger('update:totals');
            }
        });

        return Module.extend({
            regions: {
                'clientInfo': '#client-info',
                'invoiceRows': '#invoice-items'
            },
            _renderClientSelect: function(options) {
                var model = new Backbone.Model(options.client),
                    viewOptions = {type: 'invoice', model: model};

                this.app.getRegion('clientInfo').show(new ClientSelectView(_.merge(options, viewOptions)));
            },
            initialize: function(options) {
                viewModel.set('hasTax', options.tax);

                var recurring = $('#invoice_recurring'),
                    recurringInfo = $('.recurring-info');

                recurring.on('change', function () {
                   recurringInfo.toggleClass('hidden');
                });

                if (recurring.is(':checked')) {
                    recurringInfo.removeClass('hidden');
                }

                var Model = Backbone.Model.extend({
                    defaults: {
                        fields: options.fieldData,
                        description: '',
                        price: 0,
                        qty: 0,
                        total: 0
                    }
                });

                this._renderClientSelect(options);

                var model = new Model({
                    id: 0
                });

                var collection = new Collection([model]),
                    counter = collection.size();

                new (Mn.ItemView.extend({
                    el: '#discount',
                    ui: {
                        discount: '#invoice_discount'
                    },
                    events: {
                        'keyup @ui.discount': 'setDiscount'
                    },
                    setDiscount: function (event) {
                        this.model.set('total', $(event.target).val());

                        collection.trigger('change');
                    }
                }))({model: invoiceModel});

                var childView = Mn.ItemView.extend({
                    template: Template['invoice/row'],
                    tagName: 'tr',
                    ui: {
                        removeItem: '.remove-item',
                        input: ':input'
                    },
                    events: {
                        'click @ui.removeItem': 'removeItem',
                        'keyup @ui.input': 'calcPrice',
                        'change @ui.input': 'calcPrice'
                    },
                    removeItem: function(event) {
                        event.preventDefault();

                        this.model.collection.remove(this.model);
                    },
                    calcPrice: function() {
                        this.$(':input').each(_.bind(function(index, input) {
                            var $this = $(input),
                                type = $this.closest('td')[0].className.split('-')[1],
                                val = $this.val();

                            if ('price' === type) {
                                val = Accounting.unformat(val);
                            }

                            if ('tax' === type) {
                                val = $this.find(':selected').data();
                            }

                            this.model.set(type, val);
                        }, this));

                        var amount = this.model.get('qty') * this.model.get('price');

                        this.model.set('total', amount);
                        this.$('.column-total').html(Accounting.formatMoney(this.model.get('total')));
                    }
                });

                var FooterView = Mn.ItemView.extend({
                    template: Template['invoice/footer']
                });

                var view = Mn.CompositeView.extend({
                    regions: {
                        'invoiceFooter': '#invoice-footer'
                    },
                    template: Template['invoice/table'],
                    childView: childView,
                    collection: collection,
                    childViewContainer: "#invoice-rows",
                    footerView: null,
                    ui: {
                        'addItem': '.add-item'
                    },
                    events: {
                        'click @ui.addItem': 'addItem'
                    },
                    collectionEvents: {
                        "update:totals": "renderTotals"
                    },
                    renderTotals: function() {
                        var footer = this.$('#invoice-footer');

                        setTimeout(_.bind(function() {
                            footer.empty();
                            this.footerView.render().$el.find('tr').appendTo(footer);
                        }, this), 0);
                    },
                    onRender: function() {
                        this.footerView = new FooterView({model: viewModel});

                        this.footerView.render().$el.find('tr').appendTo(this.$('#invoice-footer'));
                    },
                    addItem: function(event) {
                        event.preventDefault();

                        this.collection.add(new Model({
                            id: counter++
                        }));
                    }
                });

                this.app.getRegion('invoiceRows').show(new view());
            }
        });
    }
);


