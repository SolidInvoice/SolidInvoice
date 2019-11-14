/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
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
        credit: value > 0 ? value / 100 : value,
        id: options.id
    });

    return new CreditView({
        model: model
    });
};
