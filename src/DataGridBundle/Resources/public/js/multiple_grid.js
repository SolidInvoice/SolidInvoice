/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import ItemView from 'SolidInvoiceCore/js/view';
import Template from '../templates/multiple_grid_selector.hbs';
import { head, forEach, values } from 'lodash';
import Grid from './grid';

export default ItemView.extend({
    activeGrid: null,
    gridOptions: { },
    template: Template,
    ui: {
        'gridSelector': '.grid-selection a'
    },
    events: {
        'click @ui.gridSelector': 'setGrid'
    },
    onBeforeRender() {
        forEach(this.model.get('grids'), (object) => {
            object.disabled = false;
        });

        this.model.set('grid', this.gridOptions)

        this.gridOptions.disabled = true;
    },
    async setGrid(event) {
        event.preventDefault();

        let target = $(event.target);

        if (target.hasClass('fas')) {
            target = target.closest('a');
        }

        const grid = target.data('grid');

        this.gridOptions = this.model.get('grids')[grid];

        await this.render();
    },
    async onRender() {
        this.activeGrid = new Grid(this.model.get('gridId'), this.gridOptions);
    },
    async initialize() {
        this.gridOptions = head(values(this.model.get('grids')));

        await this.render();
    }
});
