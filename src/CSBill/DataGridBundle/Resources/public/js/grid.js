define([
	'marionette',
	'backbone',
	'jquery',
	'lodash',
	'backgrid',
	'grid/model/grid_collection',
	'grid/extension/paginate',
	'grid/extension/search',
	'grid/view/action',

	'grid/backgrid-select-all',
	'grid/cell/actioncell'
    ],
    function(Mn,
	     Backbone,
	     $,
	     _,
	     Backgrid,
	     GridCollection,
	     Paginate,
	     Search,
	     ActionView) {
	return Mn.Object.extend({
	    initialize: function(options, element) {
		var collection = new GridCollection(options.name);

		collection.fetch();

		var gridOptions = {
		    collection: collection,
		    className: 'backgrid table table-bordered table-striped table-hover'
		};

		if (_.size(options.line_actions) > 0) {
		    options.columns.push({
			// name is a required parameter, but you don't really want one on a select all column
			name: "Actions",
			// Backgrid.Extension.SelectRowCell lets you select individual rows
			cell: Backgrid.Extension.ActionCell.extend({'lineActions': options.line_actions}),
			editable: false,
			sortable: false
		    });
		}

		if (_.size(options.actions) > 0) {
		    options.columns.unshift({
			// name is a required parameter, but you don't really want one on a select all column
			name: "",
			// Backgrid.Extension.SelectRowCell lets you select individual rows
			cell: "select-row",
			// Backgrid.Extension.SelectAllHeaderCell lets you select all the row on a page
			headerCell: "select-all",
			editable: false,
			sortable: false
		    });
		}

		var grid = new Backgrid.Grid(_.extend(options, gridOptions));

		$(element + '-grid').html(grid.render().el);

		if (options.properties.paginate) {
		    var paginator = new Paginate({collection: collection});

		    $(element + '-grid').append(paginator.render().el);
		}

		if (options.properties.search) {
		    var serverSideFilter = new Search({
			collection: collection,
		    });

		    $(element + '-search').append(serverSideFilter.render().el);
		}

		if (_.size(options.line_actions) > 0) {
		    var actionElement = $(element + '-actions');

		    _.each(options.actions, function(action) {
			var view = new ActionView(
			    {
				model: new Backbone.Model(action),
				grid: grid
			    }
			);

			actionElement.append(view.render().el);
		    });
		}
	    }
	});
    });