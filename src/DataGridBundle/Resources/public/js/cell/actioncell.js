/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { each } from 'lodash';
import Template from '../../templates/line_actions.hbs';

Backgrid.Extension.ActionCell = Backgrid.Cell.extend({
    template: Template,
    className: 'action-cell',
    _setRouteParams (action) {
        action.routeParams = {};

        each(action.route_params, (value, key) => {
            action.routeParams[key] = this.model.get(value);
        });
    },
    render () {
        this.$el.empty();

        each(this.lineActions, (value) => this._setRouteParams(value));

        this.$el.append(this.template({ 'actions': this.lineActions }));

        this.delegateEvents();
        return this;
    }
});

export default Backgrid.Extension.ActionCell;
