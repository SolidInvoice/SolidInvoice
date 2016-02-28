define(['backbone', './contact'], function (Backbone, Contact) {
    "use strict";

    return Backbone.Collection.extend({
        model: Contact
    });
});