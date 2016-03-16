define([
	'marionette',
	'backbone',
	'jquery',
	'lodash',
	'routing',
	'backgrid',
	'template',
	'bootstrap.bootbox',
	'core/view',

	'backbone.paginator',
	'grid/backgrid-select-all',
	'grid/backgrid-paginator',
	'grid/backgrid-filter',
	'grid/cell/actioncell'
    ],
    function (
	Mn,
	Backbone,
	$,
	_,
	Routing,
	Backgrid,
	Template,
	Bootbox,
	ItemView
    ) {
    return Mn.Object.extend({
	initialize: function (options, element) {
	    var GridCollection = Backbone.PageableCollection.extend({
		model: Backbone.Model,
		url  : Routing.generate('_grid_data', {'name' : options.name}),

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

		parseState: function (resp, queryParams, state, options) {
		    return {totalRecords: resp.count};
		},

		parseRecords: function (resp, options) {
		    return resp.items;
		}
	    });

	    var collection = new GridCollection();

	    collection.fetch();

	    var gridOptions = {
		collection: collection,
		className: 'backgrid table table-bordered table-striped table-hover'
	    };

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

	    options.columns.push({
		// name is a required parameter, but you don't really want one on a select all column
		name: "Actions",
		// Backgrid.Extension.SelectRowCell lets you select individual rows
		cell: Backgrid.Extension.ActionCell.extend({'lineActions': options.line_actions}),
		editable: false,
		sortable: false
	    });

	    var grid = new Backgrid.Grid(_.extend(options, gridOptions));

	    $(element+'-grid').html(grid.render().el);

	    var paginator = new Backgrid.Extension.Paginator({

		// If you anticipate a large number of pages, you can adjust
		// the number of page handles to show. The sliding window
		// will automatically show the next set of page handles when
		// you click next at the end of a window.
		windowSize: 20, // Default is 10

		// Used to multiple windowSize to yield a number of pages to slide,
		// in the case the number is 5
		slideScale: 0.25, // Default is 0.5

		// Whether sorting should go back to the first page
		goBackFirstOnSort: false, // Default is true

		collection: collection
	    });

	    $(element+'-grid').append(paginator.render().el);


	    var filter = Backgrid.Extension.ServerSideFilter.extend({
		template: Template['grid/search']
	    });

	    var serverSideFilter = new filter({
		collection: collection,
		// the name of the URL query parameter
		name: "q"
	    });

	    $(element+'-search').append(serverSideFilter.render().el);



	    var ActionView = ItemView.extend({
		tagName: 'span',
		template: _.template('<button type="submit" class="btn btn-<%=className%> btn-xs"><i class="fa fa-<%=icon%>"></i><%=label%></button>&nbsp;'),
		ui: {
		    button: ".btn"
		},
		events: {
		    'click @ui.button': 'confirm'
		},
		confirm: function () {
		    if (_.isEmpty(grid.getSelectedModels())) {
			return Bootbox.alert('You need to select at least one row');
		    }

		    if (!_.isEmpty(this.model.get('confirm'))) {
			var view = this;
			Bootbox.confirm(this.model.get('confirm'), function (response) {
			    if (true === response) {
				view._executeAction();
			    }
			});
		    } else {
			this._executeAction();
		    }
		},
		_executeAction: function () {
		    this.showLoader();

		    var models = _.map(this.getOption('grid').getSelectedModels(), function (model) {
			return model.id;
		    }),

		    view = this,
			promise = $.ajax({
			url: Routing.generate(this.model.get('action')),
			data: {'data': models},
			method: 'POST'
		    });

		    promise.done(function () {
			collection.fetch({
			    success: function () {
				view.getOption('grid').clearSelectedModels();
				view.hideLoader();
			    }
			});
		    });
		}
	    }),

	    actionElement = $(element+'-actions');

	    _.each(options.actions, function (action) {
		var view = new ActionView(
		    {
			model: new Backbone.Model(action),
			grid: grid
		    }
		);

		actionElement.append(view.render().el);
	    });
	}
    });
});