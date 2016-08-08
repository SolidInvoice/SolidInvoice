/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash', 'accounting'], function(Backgrid, _, Accounting) {
    var MoneyFormatter = Backgrid.MoneyFormatter = function() {
    };
    MoneyFormatter.prototype = new Backgrid.CellFormatter();
    _.extend(MoneyFormatter.prototype, {
	fromRaw: function(rawData, model) {
	    if (!_.isUndefined(rawData)) {
		return Accounting.formatMoney(parseInt(rawData, 10) / 1000);
	    }
	},
	toRaw: function(formattedData, model) {
	    return Accounting.unformat(formattedData);
	}
    });
});