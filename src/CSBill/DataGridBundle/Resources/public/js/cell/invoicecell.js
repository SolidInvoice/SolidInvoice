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

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'backbone', 'lodash', 'template', 'jquery.jqcron'], function(Backgrid, Backbone, _, Template) {
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

    Backgrid.Extension.RecurringInvoiceEndCell = Backgrid.DateCell.extend({
	initialize: function() {
	    Backgrid.DateCell.__super__.initialize.apply(this, arguments);

	    var recurringInfo = this.model.get('recurringInfo');

	    this.model = new Backbone.Model(recurringInfo);
	}
    });

    Backgrid.Extension.RecurringInvoiceStartCell = Backgrid.DateCell.extend({
	initialize: function() {
	    Backgrid.DateCell.__super__.initialize.apply(this, arguments);

	    var recurringInfo = this.model.get('recurringInfo');

	    this.model = new Backbone.Model(recurringInfo);
	}
    });

    Backgrid.Extension.RecurringInvoiceFrequencyCell = Backgrid.StringCell.extend({
	initialize: function() {
	    Backgrid.StringCell.__super__.initialize.apply(this, arguments);

	    var recurringInfo = this.model.get('recurringInfo');

	    this.model = new Backbone.Model(recurringInfo);
	},
	render: function() {
	    var value = this.model.get(this.column.get("name"));

	    this.$el.jqCron({
		enabled_minute: false,
		enabled_hour: false,
		no_reset_button: true,
		numeric_zero_pad: true,
		default_value: value
	    });

	    setTimeout(function() {
		$('.jqCron-selector-list').remove();
	    }, 0);

	    return this;
	}
    });
});