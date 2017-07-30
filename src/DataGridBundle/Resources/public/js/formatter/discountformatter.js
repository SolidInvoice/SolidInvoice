/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash', 'accounting'], function(Backgrid, _, Accounting) {
    let DiscountFormatter = Backgrid.DiscountFormatter = function() {
    };
    DiscountFormatter.prototype = new Backgrid.CellFormatter();
    _.extend(DiscountFormatter.prototype, {
        fromRaw: function(rawData, model) {
            if (!_.isUndefined(model.get('discount.type'))) {
                let discountType = model.get('discount.type');

                if ('money' === discountType.toLowerCase()) {
                    let discountAmount = parseInt(model.get('discount.valueMoney.value'), 10);

                    if (discountAmount > 0) {
                        return Accounting.formatMoney(discountAmount / 100, model.get('client').currency);
                    }

                    return '';
                }

                let discountPercentage = model.get('discount.valuePercentage');

                if (parseInt(discountPercentage, 10) > 0) {
                    return discountPercentage + '%';
                }
            }
        },
        toRaw: function(formattedData, model) {
            console.log(formattedData, model);
            return formattedData;
        }
    });
});