define(
    [
        'core/module',
        'jquery',
        'backbone',
        'lodash',
        'client/view/client_select',
        'core/billing/model/footer_row_model',
        'core/billing/model/row_model',
        'core/billing/model/discount',
        'core/billing/model/collection',
        'core/billing/view/footer',
        'invoice/view',
        'core/billing/view/discount',
        'routing',
        'accounting'
    ],
    function(Module,
             $,
             Backbone,
             _,
             ClientSelectView,
             FooterRowModel,
             RowModel,
             DiscountModel,
             Collection,
             FooterView,
             InvoiceView,
             Discount,
             Routing,
             Accounting) {
        "use strict";

        return Module.extend({
            collection: null,
            footerRowModel: null,
            regions: {
                'clientInfo': '#client-info',
                'invoiceRows': '#invoice-items',
                'invoiceForm': '#invoice-create-form'
            },
            _renderClientSelect: function(options) {
                var model = new Backbone.Model(options.client),
                    viewOptions = {type: 'invoice', model: model, 'hideLoader': false},
                    module = this,
                    clientSelectView = new ClientSelectView(_.merge(options, viewOptions));

                clientSelectView.on('currency:update', function(clientOptions) {
                    Accounting.settings.currency.symbol = clientOptions.currency_format;

                    $.getJSON(
                        Routing.generate('_invoices_get_fields', {'currency': clientOptions.currency})
                    ).done(_.bind(function(fieldData) {
                        module.collection.each(function(model) {
                            model.set('fields', fieldData);
                        });

                        var invoiceView = module._getInvoiceView(fieldData);

                        this.hideLoader();

                        module.app.showChildView('invoiceRows', invoiceView);

                        this.$el.find(this.regions.invoiceForm).attr('action', Routing.generate('_invoices_create', {'client': clientOptions.client}));
                        $('.currency-view').html(clientOptions.currency);

                        module.app.initialize(module.app.options);
                    }, this));
                });

                this.app.showChildView('clientInfo', clientSelectView);
            },
            _getInvoiceView: function(fieldData) {
                return new InvoiceView(
                    {
                        'collection': this.collection,
                        'footerView': new FooterView({model: this.footerRowModel}),
                        'selector': '#invoice-footer',
                        'fieldData': fieldData,
                        'hasTax': this.options.tax
                    }
                );

            },
            initialize: function(options) {
                var discountModel = new DiscountModel(),
                    recurring = $('#invoice_recurring'),
                    recurringInfo = $('.recurring-info');

                this.footerRowModel = new FooterRowModel();

                this.footerRowModel.set('hasTax', options.tax);

                recurring.on('change', function() {
                    recurringInfo.toggleClass('hidden');
                });

                if (recurring.is(':checked')) {
                    recurringInfo.removeClass('hidden');
                }

                this._renderClientSelect(options);

                var models = [];

                if (!_.isEmpty(options.formData)) {
                    var counter = 0;

                    _.each(options.formData, function(item) {
                        models.push(new RowModel({
                            id: counter++,
                            fields: item
                        }));
                    });
                } else {
                    models.push(new RowModel({
                        id: 0,
                        fields: options.fieldData
                    }));
                }

                /* COLLECTION */
                this.collection = new Collection(models, {"discountModel": discountModel, 'footerModel': this.footerRowModel});

                /* DISCOUNT */
                new Discount({model: discountModel, collection: this.collection});

                this.app.showChildView('invoiceRows', this._getInvoiceView(options.fieldData));
            }
        });
    }
);