/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['jquery', 'backbone', 'marionette', 'core/itemview', 'lodash', 'grid/grid'],
    function($, Backbone, Mn, ItemView, _, Grid) {
        return ItemView.extend({
            activeGrid: null,
            gridOptions: null,
            template: require('../templates/multiple_grid_selector.hbs'),
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
                console.log(grids);
                this.gridOptions = _.first(_.values(this.model.get('grids')));

                this.render();
            }
        });
    }
);