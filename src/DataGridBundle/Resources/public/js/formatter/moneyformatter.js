/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { isObject, isUndefined } from 'lodash';
import Accounting from 'accounting';

Backgrid.MoneyFormatter = {
    fromRaw (rawData) {
        if (!isUndefined(rawData)) {
            if (isObject(rawData)) {
                return Accounting.formatMoney(parseInt(rawData.value, 10) / 100, rawData.currency);
            }

            return Accounting.formatMoney(parseInt(rawData, 10) / 100);
        }
    },
    toRaw (formattedData) {
        return Accounting.unformat(formattedData);
    }
};

export default Backgrid.MoneyFormatter;
