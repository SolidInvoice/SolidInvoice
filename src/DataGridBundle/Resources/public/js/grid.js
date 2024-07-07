/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { CollectionView, MnObject } from 'backbone.marionette';
import Backbone from 'backbone';
import $ from 'jquery';
import { clone, assignIn, find, isUndefined, size, values } from 'lodash';
import Backgrid from 'backgrid';
import ItemView from 'SolidInvoiceCore/js/view';
import GridContainerTemplate from '../templates/grid_container.hbs';
import GridContainerNoActionsTemplate from '../templates/grid_container_no_actions.hbs';
import GridCollection from 'SolidInvoiceDataGrid/js/model/grid_collection';
import Paginate from 'SolidInvoiceDataGrid/js/extension/paginate';
import Search from 'SolidInvoiceDataGrid/js/extension/search';
import Redirect from 'SolidInvoiceDataGrid/js/extension/rowredirect';
import ActionView from 'SolidInvoiceDataGrid/js/view/action';

//import 'SolidInvoiceCore/js/extend/modal';
import 'backgrid-select-all';
import 'SolidInvoiceDataGrid/js/cell/actioncell';
import 'SolidInvoiceDataGrid/js/cell/clientcell';
import 'SolidInvoiceDataGrid/js/cell/invoicecell';
import 'SolidInvoiceDataGrid/js/cell/moneycell';
import 'SolidInvoiceDataGrid/js/cell/statuscell';
import 'SolidInvoiceDataGrid/js/formatter/objectformatter';
import 'SolidInvoiceDataGrid/js/formatter/discountformatter';
import 'SolidInvoiceDataGrid/js/formatter/moneyformatter';

export default MnObject.extend({
    async initialize (element, options) {
        const collection = new GridCollection(options.name, options.parameters);

        await collection.fetch();

        const gridOptions = {
            collection: collection,
            className: 'backgrid table table-bordered table-hover',
            emptyText: 'no data'
        };

        if (!isUndefined(options.properties.route)) {
            gridOptions.row = Redirect.row(options.properties.route);
        }

        if (0 < size(options.line_actions) && isUndefined(find(options.columns, { 'name': 'Actions' }))) {
            options.columns.push({
                // name is a required parameter, but you don't really want one on a select all column
                name: 'Actions',
                // Backgrid.Extension.SelectRowCell lets you select individual rows
                cell: Backgrid.Extension.ActionCell.extend({ 'lineActions': options.line_actions }),
                editable: false,
                sortable: false
            });
        }

        let container;

        if (0 < size(options.actions)) {
            if (isUndefined(find(options.columns, { 'cell': 'select-row' }))) {
                options.columns.unshift({
                    // name is a required parameter, but you don't really want one on a select all column
                    name: '',
                    // Backgrid.Extension.SelectRowCell lets you select individual rows
                    cell: 'select-row',
                    // Backgrid.Extension.SelectAllHeaderCell lets you select all the row on a page
                    headerCell: 'select-all',
                    editable: false,
                    sortable: false
                });
            }
        }

        const grid = new Backgrid.Grid(assignIn(clone(options), gridOptions));

        if (0 < size(options.actions)) {
            const ActionContainer = CollectionView.extend({
                template: GridContainerTemplate,
                childView: ActionView.extend({ 'grid': grid }),
                childViewContainer: '.actions'
            });

            container = new ActionContainer({
                collection: new Backbone.Collection(values(options.actions))
            });
        } else {
            container = new ItemView({
                template: GridContainerNoActionsTemplate
            });
        }

        const $gridContainer = $(container.render().el);

        $(element).append($gridContainer);

        $('.grid', $gridContainer).html(grid.render().el);

        if (options.properties.paginate) {
            const paginator = new Paginate({ collection: collection });

            $('.grid', $gridContainer).append(paginator.render().el);
        }

        if (options.properties.search) {
            const serverSideFilter = new Search({
                collection: collection
            });

            $('.search', $gridContainer).append(serverSideFilter.render().el);
        }
    }
});
