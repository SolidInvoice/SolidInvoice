/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { extend, isUndefined } from 'lodash';

const ObjectFormatter = Backgrid.ObjectFormatter = () => {
};

ObjectFormatter.prototype = new Backgrid.CellFormatter();
extend(ObjectFormatter.prototype, {
    fromRaw (rawData, model) {
        if (!isUndefined(rawData)) {
            return rawData.name;
        }
    },
    toRaw (formattedData, model) {
        return formattedData;
    }
});

export default ObjectFormatter;
