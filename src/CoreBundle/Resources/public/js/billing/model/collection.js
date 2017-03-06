define(['backbone', 'lodash'], function (Backbone, _) {
    return Backbone.Collection.extend({
	footerModel  : null,
	discountModel: null,
	initialize   : function(models, options) {
	    this.footerModel = options.footerModel;
	    this.discountModel = options.discountModel;
	    this.listenTo(this, 'change reset add remove', this.updateTotals);
	},
	updateTotals : function() {
	    var total = 0,
		tax = 0,
		subTotal = 0;

	    _.each(this.models, function(model) {
		var rowTotal = model.get('total'),
		    rowTax = model.get('tax');

		total += rowTotal;
		subTotal += rowTotal;

		if (!_.isEmpty(rowTax)) {
		    var taxAmount = rowTotal * parseFloat(rowTax.rate);
		    tax += taxAmount;

		    if ('inclusive' === rowTax.type) {
			subTotal -= taxAmount;
		    } else {
			total += taxAmount;
		    }
		}
	    });

	    var discount = (total * this.discountModel.get('total')) / 100,
		footerModel = this.footerModel;

	    footerModel.set('subTotal', subTotal);
	    footerModel.set('total', total - discount);
	    footerModel.set('discount', discount);
	    footerModel.set('tax', tax);

	    this.trigger('update:totals');
	}
    });
});