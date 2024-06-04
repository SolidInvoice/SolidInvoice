import Module from 'SolidInvoiceCore/js/module';
import $ from 'jquery';
import Backbone from 'backbone';
import { isEmpty, merge, map } from 'lodash';
import ClientSelectView from 'SolidInvoiceClient/js/view/client_select';
import FooterRowModel from 'SolidInvoiceCore/js/billing/model/footer_row_model';
import RowModel from 'SolidInvoiceCore/js/billing/model/row_model';
import DiscountModel from 'SolidInvoiceCore/js/billing/model/discount';
import Collection from 'SolidInvoiceCore/js/billing/model/collection';
import FooterView from 'SolidInvoiceCore/js/billing/view/footer';
import QuoteView from './view';
import Discount from 'SolidInvoiceCore/js/billing/view/discount';
import Routing from 'fos-router';
import Accounting from 'accounting';

export default Module.extend({
    collection: null,
    discount: null,
    footerRowModel: null,
    regions: {
        'clientInfo': '#client-info',
        'quoteRows': '#quote-items',
        'quoteForm': '#quote-create-form'
    },
    _renderClientSelect (options) {
        const model = new Backbone.Model(options.client),
            viewOptions = { type: 'quote', model: model, 'hideLoader': false },
            module = this,
            clientSelectView = new ClientSelectView(merge(options, viewOptions));

        clientSelectView.on('currency:update', (clientOptions) => {
            Accounting.settings.currency.symbol = clientOptions.currency_format;

            $.getJSON(
                Routing.generate('_quotes_get_fields', { 'currency': clientOptions.currency })
            ).done((fieldData) => {
                module.collection.each((m) => {
                    m.set('fields', fieldData);
                });

                const quoteView = module._getQuoteView(fieldData);

                clientSelectView.hideLoader();

                module.app.showChildView('quoteRows', quoteView);

                // eslint-disable-next-line
                clientSelectView.$el.find(module.regions.quoteForm).attr('action', Routing.generate('_quotes_create', { 'client': clientOptions.client }));
                $('.currency-view').html(clientOptions.currency);

                module.app.initialize(module.app.options);
            });
        });

        this.app.showChildView('clientInfo', clientSelectView);
    },
    _getQuoteView (fieldData) {
        return new QuoteView(
            {
                'collection': this.collection,
                'footerView': new FooterView({ model: this.footerRowModel }),
                'selector': '#quote-footer',
                'fieldData': fieldData,
                'hasTax': this.options.tax
            }
        );
    },
    initialize (options) {
        const discountModel = new DiscountModel();

        this.footerRowModel = new FooterRowModel();
        this.footerRowModel.set('hasTax', options.tax);

        this._renderClientSelect(options);

        let models = [];

        if (!isEmpty(options.formData)) {
            let counter = 0;

            models = map(options.formData, (item) => new RowModel({
                id: counter++,
                fields: item
            }));
        } else {
            models.push(new RowModel({
                id: 0,
                fields: options.fieldData
            }));
        }

        /* COLLECTION */
        this.collection = new Collection(models, { 'discountModel': discountModel, 'footerModel': this.footerRowModel });

        /* DISCOUNT */
        this.discount = new Discount({ model: discountModel, collection: this.collection });

        this.listenTo(this.collection, 'update:total', () => this.footerRowModel.render());

        this.app.showChildView('quoteRows', this._getQuoteView(options.fieldData));
    }
});
