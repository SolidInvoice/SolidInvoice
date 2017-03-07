define(['backbone', 'routing'], function(Backbone, Routing) {
    "use strict";

    return Backbone.Model.extend({
	url: function() {
	    return Routing.generate('_clients_address', {'id': this.id})
	}
    });
});