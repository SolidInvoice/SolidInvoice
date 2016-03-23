define(['backbone', 'routing', 'backbone.paginator'], function(Backbone, Routing) {
    return Backbone.PageableCollection.extend({
	name: null,
	model: Backbone.Model,
	initialize: function(name) {
	    this.name = name;
	},
	url: function() {
	    return Routing.generate('_grid_data', {'name': this.name});
	},

	// Initial pagination states
	state: {
	    pageSize: 15,
	    sortKey: "created",
	    order: 1
	},

	// You can remap the query parameters from `state` keys from
	// the default to those your server supports
	queryParams: {
	    totalPages: null,
	    totalRecords: null,
	    sortKey: "sort"
	},

	parseState: function(resp, queryParams, state, options) {
	    return {
		totalRecords: resp.count
	    };
	},

	parseRecords: function(resp, options) {
	    return resp.items;
	}
    });
});