define([
	'marionette',
	'backbone',
	'jquery',
	'lodash',
	'backgrid',
	'core/view',
	'template',
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
	     ItemView,
	     Template,
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

		var container;

		if (_.size(options.line_actions) > 0) {
		    var ActionContainer = Mn.CompositeView.extend({
			template: Template['grid/grid_container'],
			childView: ActionView,
			childViewContainer: '.actions'
		    });

		    container = new ActionContainer({
			collection: new Backbone.Collection(_.values(options.actions))
		    });
		} else {
		    container = new ItemView({
			template: Template['grid/grid_container_no_actions']
		    });
		}

		var gridContainer = $(container.render().el);

		$(element).append(container.render().el);

		var grid = new Backgrid.Grid(_.extend(options, gridOptions));

		$('.grid', gridContainer).html(grid.render().el);

		if (options.properties.paginate) {
		    var paginator = new Paginate({collection: collection});

		    $('.grid', gridContainer).append(paginator.render().el);
		}

		if (options.properties.search) {
		    var serverSideFilter = new Search({
			collection: collection
		    });

		    $('.search', gridContainer).append(serverSideFilter.render().el);
		}
	    }
	});
    });