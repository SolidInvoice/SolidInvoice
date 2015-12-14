define(['handlebars.runtime', 'routing', 'accounting', 'translator'], function (Handlebars, Routing, Accounting, __) {
    "use strict";

    /**
     * Routing Helper
     */
    Handlebars.registerHelper('path', function(route, context) {
        return Routing.generate(route, context.hash);
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

    /**
     * Translation Helper
     */
    Handlebars.registerHelper('trans', function(message, context) {
        return __(message, context.hash);
    });

    return Handlebars;
});