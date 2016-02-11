define(
    ['core/module', 'jquery', 'marionette', 'backbone', 'lodash', 'template', 'accounting', 'client/view/client_select'],
    function(Module, $, Mn, Backbone, _, Template, Accounting, ClientSelectView) {
        "use strict";

        var ViewModel = Backbone.Model.extend({
            defaults: {
                subTotal: 0,
                discount: 0,
                tax: 0,
                total: 0
            }
        });
        
        var InvoiceModel = new (Backbone.Model.extend({
                defaults: {
                    total: 0
                }
            }));

        var viewModel = new ViewModel();

        var Collection = Backbone.Collection.extend({
            initialize: function() {
                this.listenTo(this, 'change reset add remove', this.updateTotals);
            },
            updateTotals: function() {
                var total = 0;

                _.each(this.models, function(model) {
                    total += model.get('total');
                });
                
                var discount = (total * InvoiceModel.get('total')) / 100;

                viewModel.set('subTotal', total);
                viewModel.set('total', total - discount);
                viewModel.set('discount', discount);

                this.trigger('update:totals');
            }
        });

        return Module.extend({
            regions: {
                'clientInfo': '#client-info',
                'invoiceRows': '#invoice-items'
            },
            _renderClientSelect: function(options) {
                var model = new Backbone.Model(options.client);
                var viewOptions = {type: 'invoice', model: model};

                this.app.getRegion('clientInfo').show(new ClientSelectView(_.merge(options, viewOptions)));
            },
            initialize: function(options) {
                
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

                var collection = new Collection([model]);
                var counter = collection.size();

                var InvoiceView = new (Mn.ItemView.extend({
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
                }))({model: InvoiceModel});

                var childView = Mn.ItemView.extend({
                    template: Template['invoice/row'],
                    tagName: 'tr',
                    ui: {
                        removeItem: '.remove-item',
                        input: ':input'
                    },
                    events: {
                        'click @ui.removeItem': 'removeItem',
                        'keyup @ui.input': 'calcPrice'
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

                            this.model.set(type, val);
                        }, this));

                        var amount = this.model.get('qty') * this.model.get('price');

                        this.model.set('total', amount);
                        this.$('.column-total').html(Accounting.formatMoney(this.model.get('total')));
                        
                        console.log(InvoiceModel);
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


