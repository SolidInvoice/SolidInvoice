define(['backbone', 'routing', 'lodash'], function (Backbone, Routing, _) {
    "use strict";

    return Backbone.Model.extend({
        urlRoot: Routing.generate('_clients_add_credit'),
        defaults: {
            credit: 0
        },
        validate: function (values) {
            var credit = parseFloat(values.credit);

            if (credit === 0) {
                return 'Credit value cannot be 0'
            }
        }
    });
});