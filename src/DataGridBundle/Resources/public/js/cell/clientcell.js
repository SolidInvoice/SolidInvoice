/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import { forEach, isUndefined } from 'lodash';
import Template from '../../templates/client_link.hbs';

Backgrid.Extension.ClientCell = Backgrid.Cell.extend({
    template: Template,
    _setRouteParams (action) {
        action.routeParams = {};

        forEach(action.route_params, (value, key) => {
            action.routeParams[key] = this.model.get(value);
        });
    },
    render () {
        this.$el.empty();

        const client = this.model.get('client');

        if (!isUndefined(client)) {
            this.$el.append(this.template({ 'client': client }));
        }

        this.delegateEvents();
        return this;
    }
});

export default Backgrid.Extension.ClientCell;
