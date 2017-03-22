/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash'], function(Backgrid, _) {
    var DiscountFormatter = Backgrid.DiscountFormatter = function() {
    };
    DiscountFormatter.prototype = new Backgrid.CellFormatter();
    _.extend(DiscountFormatter.prototype, {
        fromRaw: function(rawData, model) {
            if (!_.isUndefined(rawData)) {
                rawData = parseFloat(rawData);

                if (rawData < 1) {
                    rawData *= 100;
                }

                return rawData + '%';
            }
        },
        toRaw: function(formattedData, model) {
            return formattedData;
        }
    });
});