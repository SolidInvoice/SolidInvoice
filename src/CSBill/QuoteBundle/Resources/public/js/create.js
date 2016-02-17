define(
    [
        'core/module',
        'backbone',
        'lodash',
        'client/view/client_select',
        'core/billing/model/footer_row_model',
        'core/billing/model/row_model',
        'core/billing/model/discount',
        'core/billing/model/collection',
        'core/billing/view/footer',
        'quote/view',
        'quote/discount'
    ],
    function(
        Module,
        Backbone,
        _,
        ClientSelectView,
        FooterRowModel,
        RowModel,
        DiscountModel,
        Collection,
        FooterView,
        QuoteView,
        Discount
    ) {
        "use strict";

        return Module.extend({
            regions: {
                'clientInfo': '#client-info',
                'quoteRows': '#quote-items'
            },
            _renderClientSelect: function(options) {
                var model = new Backbone.Model(options.client),
                    viewOptions = {type: 'quote', model: model};

                this.app.getRegion('clientInfo').show(new ClientSelectView(_.merge(options, viewOptions)));
            },
            initialize: function(options) {
                var discountModel = new DiscountModel(),
                    footerRowModel = new FooterRowModel();

                footerRowModel.set('hasTax', options.tax);

                this._renderClientSelect(options);

                var models = [];

                if (!_.isEmpty(options.formData)) {
                    var counter = 0;

                    _.each(options.formData, function (item) {
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
                var collection = new Collection(models, {"discountModel": discountModel, 'footerModel': footerRowModel});

                console.log(collection);

                /* DISCOUNT */
                new Discount({model: discountModel, collection: collection});

                this.app
                    .getRegion('quoteRows')
                    .show(
                        new QuoteView(
                            {
                                'collection': collection,
                                'footerView': new FooterView({model: footerRowModel}),
                                'selector': '#quote-footer',
                                'fieldData': options.fieldData,
                                'hasTax': options.tax
                            }
                        )
                    );
            }
        });
    }
);