define(['marionette', 'backbone', 'jquery', 'lodash', 'routing', 'backgrid', 'grid/backgrid-select-all'], function (Mn, Backbone, $, _, Routing, Backgrid) {
    return Mn.Object.extend({
	initialize: function (options, element) {
	    var GridCollection = Backbone.Collection.extend({
		model: Backbone.Model,
		url  : Routing.generate('_grid_data', {'name' : options.name})
	    });

	    var collection = new GridCollection();

	    collection.fetch();

	    var gridOptions = {
		collection: collection,
		className: 'table'
	    };

	    var grid = new Backgrid.Grid(_.extend(options, gridOptions));

	    $(element).html(grid.render().el);
	}
    });
});