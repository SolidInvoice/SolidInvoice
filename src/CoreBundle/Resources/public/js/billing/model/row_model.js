define(['backbone'], function (Backbone) {
    return Backbone.Model.extend({
	defaults: {
	    fields: {},
	    description: '',
	    price: 0,
	    qty: 1,
	    total: 0
	}
    });
});