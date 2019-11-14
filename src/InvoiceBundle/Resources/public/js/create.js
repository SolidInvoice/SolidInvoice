import Module from 'SolidInvoiceCore/js/module';
import $ from 'jquery';
import Backbone from 'backbone';
import { each, isEmpty, merge } from 'lodash';
import ClientSelectView from 'SolidInvoiceClient/js/view/client_select';
import FooterRowModel from 'SolidInvoiceCore/js/billing/model/footer_row_model';
import RowModel from 'SolidInvoiceCore/js/billing/model/row_model';
import DiscountModel from 'SolidInvoiceCore/js/billing/model/discount';
import Collection from 'SolidInvoiceCore/js/billing/model/collection';
import FooterView from 'SolidInvoiceCore/js/billing/view/footer';
import InvoiceView from './view';
import Discount from 'SolidInvoiceCore/js/billing/view/discount';
import Router from 'router';
import Accounting from 'accounting';

export default Module.extend({
    collection: null,
    footerRowModel: null,
    discount: null,
    regions: {
        'clientInfo': '#client-info',
        'invoiceRows': '#invoice-items',
        'invoiceForm': '#invoice-create-form'
    },
    _renderClientSelect (options) {
        const model = new Backbone.Model(options.client),
            viewOptions = { type: 'invoice', model: model, 'hideLoader': false },
            module = this,
            clientSelectView = new ClientSelectView(merge(options, viewOptions));

        clientSelectView.on('currency:update', (clientOptions) => {
            Accounting.settings.currency.symbol = clientOptions.currency_format;

            $.getJSON(
                Router.generate('_invoices_get_fields', { 'currency': clientOptions.currency })
            ).done((fieldData) => {
                module.collection.each((model) => {
                    model.set('fields', fieldData);
                });

                const invoiceView = module._getInvoiceView(fieldData);

                this.hideLoader();

                module.app.showChildView('invoiceRows', invoiceView);

                this.$el.find(this.regions.invoiceForm).attr('action', Router.generate('_invoices_create', { 'client': clientOptions.client }));
                $('.currency-view').html(clientOptions.currency);

                module.app.initialize(module.app.options);
            });
        });

        this.app.showChildView('clientInfo', clientSelectView);
    },
    _getInvoiceView (fieldData) {
        return new InvoiceView(
            {
                'collection': this.collection,
                'footerView': new FooterView({ model: this.footerRowModel }),
                'selector': '#invoice-footer',
                'fieldData': fieldData,
                'hasTax': this.options.tax
            }
        );

    },
    initialize (options) {
        const discountModel = new DiscountModel(),
            recurring = $('#invoice_recurring'),
            recurringInfo = $('.recurring-info');

        this.footerRowModel = new FooterRowModel();

        this.footerRowModel.set('hasTax', options.tax);

        recurring.on('change', () => {
            recurringInfo.toggleClass('hidden');
        });

        if (recurring.is(':checked')) {
            recurringInfo.removeClass('hidden');
        }

        this._renderClientSelect(options);

        const models = [];

        if (!isEmpty(options.formData)) {
            let counter = 0;

            each(options.formData, (item) => {
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
        this.collection = new Collection(models, { "discountModel": discountModel, 'footerModel': this.footerRowModel });

        /* DISCOUNT */
        this.discount = new Discount({ model: discountModel, collection: this.collection });

        this.app.showChildView('invoiceRows', this._getInvoiceView(options.fieldData));
    }
});
