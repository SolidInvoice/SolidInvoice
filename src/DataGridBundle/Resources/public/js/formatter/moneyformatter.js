/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { assignIn, isObject, isUndefined, noop } from 'lodash';
import Accounting from 'accounting';

const MoneyFormatter = Backgrid.MoneyFormatter = noop;

MoneyFormatter.prototype = new Backgrid.CellFormatter();
assignIn(MoneyFormatter.prototype, {
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
});

export default MoneyFormatter;
