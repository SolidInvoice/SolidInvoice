/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash'], function(Backgrid, _) {

    Backgrid.Extension.ActionCell = Backgrid.Cell.extend({
        template: require('../../templates/line_actions.hbs'),
        _setRouteParams: function(action) {
            action.routeParams = {};

            _.each(action.route_params, _.bind(function(value, key) {
                action.routeParams[key] = this.model.get(value);
            }, this));
        },
        render: function() {
            this.$el.empty();

            _.each(this.lineActions, _.bind(this._setRouteParams, this));

            this.$el.append(this.template({'actions': this.lineActions}));

            this.delegateEvents();
            return this;
        }
    });
});