/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash', 'template'], function(Backgrid, _, Template) {
    Backgrid.Extension.ClientCell = Backgrid.Cell.extend({
        template: Template.datagrid.client_link,
        _setRouteParams: function(action) {
            action.routeParams = {};

            _.each(action.route_params, _.bind(function(value, key) {
                action.routeParams[key] = this.model.get(value);
            }, this));
        },
        render: function() {
            this.$el.empty();

            var client = this.model.get('client');

            if (!_.isUndefined(client)) {
                this.$el.append(this.template({'client': client}));
            }

            this.delegateEvents();
            return this;
        }
    });
});