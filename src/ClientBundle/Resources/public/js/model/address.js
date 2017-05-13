define(['backbone', 'routing'], function(Backbone, Routing) {
    "use strict";

    return Backbone.Model.extend({
        url: function() {
            return Routing.generate('_xhr_clients_address', {'id': this.id})
        },
        destroy: function(options) {
            var opts = _.extend({url: Routing.generate('_xhr_clients_delete_address', {'id': this.id})}, options || {});
            return Backbone.Model.prototype.destroy.call(this, opts);
        }
    });
});