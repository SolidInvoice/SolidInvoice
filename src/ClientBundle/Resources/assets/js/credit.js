/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import CreditView from './view/credit';
import CreditModel from './model/credit';
import { result } from 'lodash';

export default function(options) {
    const value = result(options, 'credit', 0);
    const model = new CreditModel({
        credit: 0 < value ? value / 100 : value,
        id: options.id
    });

    return new CreditView({
        model: model
    });
}
