define(['backbone', 'csbillclient/js/model/contact'], function (Backbone, Contact) {
    "use strict";

    return Backbone.Collection.extend({
        model: Contact
    });
});