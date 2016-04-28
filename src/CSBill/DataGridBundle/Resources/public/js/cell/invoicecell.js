/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash', 'template'], function(Backgrid, _, Template) {
    Backgrid.Extension.InvoiceCell = Backgrid.Cell.extend({
	template: Template['grid/invoice_link'],
	_setRouteParams: function(action) {
	    action.routeParams = {};

	    _.each(action.route_params, _.bind(function(value, key) {
		action.routeParams[key] = this.model.get(value);
	    }, this));
	},
	render: function() {
	    this.$el.empty();

	    var invoice = this.model.get('invoice');

	    if (!_.isUndefined(invoice)) {
		this.$el.append(this.template({'invoice': invoice}));
	    }

	    this.delegateEvents();
	    return this;
	}
    });
});