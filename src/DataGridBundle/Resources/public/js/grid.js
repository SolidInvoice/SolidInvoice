/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
        'marionette',
        'backbone',
        'jquery',
        'lodash',
        'backgrid',
        'core/itemview',

        './model/grid_collection',
        './extension/paginate',
        './extension/search',
        './view/action',

        'bootstrap.modalmanager',
        'backgrid-select-all',
        './cell/actioncell',
        './cell/clientcell',
        './cell/invoicecell',
        './cell/moneycell',
        './cell/statuscell',
        './formatter/objectformatter',
        './formatter/discountformatter',
        './formatter/moneyformatter'
    ],
    function(Mn,
             Backbone,
             $,
             _,
             Backgrid,
             ItemView,
             GridCollection,
             Paginate,
             Search,
             ActionView) {

        //window.customElements.define('grid', AppDrawer);

        return Mn.Object.extend({
            element: null,
            initialize: function(name, element) {
                this.element = element;

                var collection = new GridCollection(name, {});

                collection.fetch({'success': _.bind(function (collection, resp) {
                    this.renderGrid(collection, resp.options);
                }, this)});
            },
            renderGrid: function (collection, options) {
                var gridOptions = {
                    collection: collection,
                    className: 'backgrid table table-bordered table-striped table-hover'
                };

                if (_.size(options.line_actions) > 0 && _.isUndefined(_.find(options.columns, {'name': 'Actions'}))) {
                    options.columns.push({
                        // name is a required parameter, but you don't really want one on a select all column
                        name: "Actions",
                        // Backgrid.Extension.SelectRowCell lets you select individual rows
                        cell: Backgrid.Extension.ActionCell.extend({'lineActions': options.line_actions}),
                        editable: false,
                        sortable: false
                    });
                }

                var container;

                if (_.size(options.actions) > 0) {
                    if (_.isUndefined(_.find(options.columns, {'cell': 'select-row'}))) {
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
                }

                var grid = new Backgrid.Grid(_.extend(_.clone(options), gridOptions));

                if (_.size(options.actions) > 0) {
                    var ActionContainer = Mn.View.extend({
                        template: require('../templates/grid_container.hbs'),
                        childView: ActionView.extend({'grid': grid}),
                        childViewContainer: '.actions'
                    });

                    container = new ActionContainer({
                        collection: new Backbone.Collection(_.values(options.actions))
                    });
                } else {
                    container = new ItemView({
                        template: require('../templates/grid_container_no_actions.hbs')
                    });
                }

                var gridContainer = $(container.render().el);

                $(document.getElementById(this.element)).append(container.render().el);
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