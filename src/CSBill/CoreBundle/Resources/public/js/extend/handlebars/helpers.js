define(['handlebars.runtime', 'routing'], function (Handlebars, Routing) {
    "use strict";

    /**
     * Routing Helper
     */
    Handlebars.registerHelper('path', function(route, parameters) {
        return Routing.generate(route, parameters);
    });

    return Handlebars;
});