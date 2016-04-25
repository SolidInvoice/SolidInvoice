/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['grid/grid', 'jquery', 'backbone', 'marionette', 'core/view', 'template', 'lodash'],
    function(Grid, $, Backbone, Mn, ItemView, Template, _) {
	return ItemView.extend({
	    activeGrid: null,
	    gridOptions: null,
	    template: Template['grid/multiple_grid_selector'],
	    ui: {
		'gridSelector': '.grid-selection a'
	    },
	    events: {
		'click @ui.gridSelector': 'setGrid'
	    },
	    onBeforeRender: function() {
		_.forEach(this.model.get('grids'), function(object) {
		    object.disabled = false;
		});

		this.gridOptions.disabled = true;
	    },
	    setGrid: function(event) {
		event.preventDefault();
		var grid = $(event.target).data('grid');

		this.gridOptions = this.model.get('grids')[grid];

		this.render();
	    },
	    onRender: function() {
		this.activeGrid = new Grid(this.gridOptions, '#' + this.model.get('gridId'));
	    },
	    initialize: function(grids) {
		this.gridOptions = _.first(_.values(this.model.get('grids')));

		this.render();
	    }
	});
    }
);