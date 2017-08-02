define(['backbone'], function (Backbone) {
    return Backbone.Model.extend({
        defaults: {
            value: 0,
            type: 0
        }
    });
});