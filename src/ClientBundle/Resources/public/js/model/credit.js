define(['backbone', 'routing'], function (Backbone, Routing) {
    "use strict";

    return Backbone.Model.extend({
        url: function () {
            return Routing.generate('_xhr_clients_credit_update', {'client': this.id})
        },
        defaults: {
            credit: 0
        },
        validate: function (values) {
            var credit = parseFloat(values.credit);

            if (credit === 0) {
                // @TODO: Add translation for this text
                return 'Credit value cannot be 0'
            }
        }
    });
});