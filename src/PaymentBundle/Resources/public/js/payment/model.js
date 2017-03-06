define(['backbone', 'routing'], function (Backbone, Routing) {
    "use strict";

    return Backbone.Model.extend({
	"url": Routing.generate('_payment_settings_index')
    })
});