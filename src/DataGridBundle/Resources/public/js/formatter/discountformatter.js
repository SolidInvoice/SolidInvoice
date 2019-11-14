/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { extend, isUndefined } from 'lodash';
import Accounting from 'accounting';
import Backgrid from 'backgrid';

const DiscountFormatter = Backgrid.DiscountFormatter = () => {
};

DiscountFormatter.prototype = new Backgrid.CellFormatter();

extend(DiscountFormatter.prototype, {
    fromRaw (rawData, model) {
        if (!isUndefined(model.get('discount.type'))) {
            let discountType = model.get('discount.type');

            if (null === discountType) {
                return '';
            }

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
    toRaw (formattedData, model) {
        return formattedData;
    }
});

export default DiscountFormatter;
