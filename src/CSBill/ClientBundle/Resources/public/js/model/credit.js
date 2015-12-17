define(['backbone', 'routing', 'lodash'], function (Backbone, Routing, _) {
    "use strict";

    return Backbone.Model.extend({
        url: function () {
            return Routing.generate('_clients_credit', {'client': this.id})
        },
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