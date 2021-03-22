import Backbone from 'backbone';
import { isEmpty, isUndefined, toLower, forEach } from 'lodash';
import RowModel from 'SolidInvoiceCore/js/billing/model/row_model';

export default Backbone.Collection.extend({
    async initialize (models, options) {
        this.footerModel = options.footerModel;
        this.discountModel = options.discountModel;
        this.listenTo(this, 'change reset add remove', await this.updateTotals);
    },
    footerModel: null,
    discountModel: null,
    model: RowModel,
    async updateTotals () {
        let total = 0,
            tax = 0,
            subTotal = 0;

        forEach(this.models,(model) => {
            let rowTotal = model.get('total'),
                rowTax = model.get('tax');

            if (isUndefined(rowTotal)) {
                return;
            }

            total += rowTotal;
            subTotal += rowTotal;

            if (!isEmpty(rowTax) && !isUndefined(rowTax.type)) {
                let taxAmount = 0;

                if ('inclusive' === toLower(rowTax.type)) {
                    taxAmount = ( rowTotal / ( parseFloat(rowTax.rate / 100) + 1 ) - rowTotal ) * -1;
                    subTotal -= taxAmount;
                } else {
                    taxAmount = rowTotal * parseFloat(rowTax.rate / 100);
                    total += taxAmount;
                }

                tax += taxAmount;
            }
        });

        let discount = 0,
            footerModel = this.footerModel;

        if (0 < this.discountModel.get('value')) {
            if ('percentage' === this.discountModel.get('type')) {
                discount = ( total * this.discountModel.get('value') ) / 100;
            } else {
                discount = this.discountModel.get('value');
            }
        }

        footerModel.set('subTotal', subTotal);
        footerModel.set('total', total - discount);
        footerModel.set('discount', discount);
        footerModel.set('tax', tax);

        this.trigger('update:totals');
    }
});
