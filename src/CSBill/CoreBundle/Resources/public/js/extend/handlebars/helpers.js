define(['handlebars.runtime', 'routing', 'accounting'], function (Handlebars, Routing, Accounting) {
    "use strict";

    /**
     * Routing Helper
     */
    Handlebars.registerHelper('path', function(route, parameters) {
        return Routing.generate(route, parameters);
    });

    /**
     * Currency Helper
     */
    Handlebars.registerHelper('currency', function(amount, context) {
        if (arguments.length > 1) {
            return Accounting.formatMoney(amount);
        }

        return Accounting.settings.currency.symbol;
    });

    return Handlebars;
});