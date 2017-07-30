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
        'quote/view',
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
             QuoteView,
             Discount,
             Routing,
             Accounting) {

        "use strict";

        return Module.extend({
            collection: null,
            footerRowModel: null,
            regions: {
                'clientInfo': '#client-info',
                'quoteRows': '#quote-items',
                'quoteForm': '#quote-create-form'
            },
            _renderClientSelect: function(options) {
                var model = new Backbone.Model(options.client),
                    viewOptions = {type: 'quote', model: model, 'hideLoader': false},
                    module = this,
                    clientSelectView = new ClientSelectView(_.merge(options, viewOptions));

                clientSelectView.on('currency:update', function(clientOptions) {
                    Accounting.settings.currency.symbol = clientOptions.currency_format;

                    $.getJSON(
                        Routing.generate('_quotes_get_fields', {'currency': clientOptions.currency})
                    ).done(_.bind(function(fieldData) {
                        module.collection.each(function(model) {
                            model.set('fields', fieldData);
                        });

                        var quoteView = module._getQuoteView(fieldData);

                        this.hideLoader();

                        module.app.showChildView('quoteRows', quoteView);

                        this.$el.find(this.regions.quoteForm).attr('action', Routing.generate('_quotes_create', {'client': clientOptions.client}));
                        $('.currency-view').html(clientOptions.currency);

                        module.app.initialize(module.app.options);
                    }, this));
                });

                this.app.showChildView('clientInfo', clientSelectView);
            },
            _getQuoteView: function(fieldData) {
                return new QuoteView(
                    {
                        'collection': this.collection,
                        'footerView': new FooterView({model: this.footerRowModel}),
                        'selector': '#quote-footer',
                        'fieldData': fieldData,
                        'hasTax': this.options.tax
                    }
                );
            },
            initialize: function(options) {
                var discountModel = new DiscountModel();

                this.footerRowModel = new FooterRowModel();
                this.footerRowModel.set('hasTax', options.tax);

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

                this.app.showChildView('quoteRows', this._getQuoteView(options.fieldData));
            }
        });
    }
);