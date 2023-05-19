/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { isUndefined, toLower } from 'lodash';
import Accounting from 'accounting';
import Backgrid from 'backgrid';

Backgrid.DiscountFormatter = {
    fromRaw (rawData, model) {
        if (!isUndefined(model.get('discount.type'))) {
            let discountType = model.get('discount.type');

            if (null === discountType) {
                return '';
            }

            if ('money' === toLower(discountType)) {
                let discountAmount = parseInt(model.get('discount.valueMoney.value'), 10);

                if (0 < discountAmount) {
                    return Accounting.formatMoney(discountAmount / 100, model.get('client').currency);
                }

                return '';
            }

            let discountPercentage = model.get('discount.valuePercentage');

            if (0 < parseInt(discountPercentage, 10)) {
                return discountPercentage + '%';
            }
        }
    },
    toRaw (formattedData) {
        return formattedData;
    }
};

export default Backgrid.DiscountFormatter;
