/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { assignIn, isUndefined, noop } from 'lodash';

const ObjectFormatter = Backgrid.ObjectFormatter = noop;

ObjectFormatter.prototype = new Backgrid.CellFormatter();

assignIn(ObjectFormatter.prototype, {
    fromRaw (rawData) {
        if (!isUndefined(rawData)) {
            return rawData.name;
        }
    },
    toRaw (formattedData) {
        return formattedData;
    }
});

export default ObjectFormatter;
