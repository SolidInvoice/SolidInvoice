/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash'], function(Backgrid, _) {
    var ObjectFormatter = Backgrid.ObjectFormatter = function() {
    };
    ObjectFormatter.prototype = new Backgrid.CellFormatter();
    _.extend(ObjectFormatter.prototype, {
	fromRaw: function(rawData, model) {
	    if (!_.isUndefined(rawData)) {
		return rawData.name;
	    }
	},
	toRaw: function(formattedData, model) {
	    return formattedData;
	}
    });
});