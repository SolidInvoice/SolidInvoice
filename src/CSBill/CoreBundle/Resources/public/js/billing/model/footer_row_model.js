define(['backbone'], function (Backbone) {
    return Backbone.Model.extend({
        defaults: {
            subTotal: 0,
            discount: 0,
            tax: {},
            total: 0,
            hasTax: false
        }
    });
});