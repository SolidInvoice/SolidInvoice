define(['backbone', 'routing'], function (Backbone, Routing) {
    "use strict";

    return Backbone.Model.extend({
        "url": Routing.generate('_xhr_payments_method_list')
    })
});