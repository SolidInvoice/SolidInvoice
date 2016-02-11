define(
    ['handlebars.runtime', 'lodash', 'routing', 'accounting', 'moment', 'translator'],
    function (Handlebars, _, Routing, Accounting, Moment, __) {
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
                if (!_.isUndefined(context.hash.symbol) && context.hash.symbol === false) {
                    return Accounting.formatNumber(amount, Accounting.settings.currency.precision, Accounting.settings.currency.thousand, Accounting.settings.currency.decimal);
                }

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

        /**
         * Title Helper
         */
        Handlebars.registerHelper('title', function(message) {
            return message
                .replace(/[-_]/g, ' ')
                .toLowerCase()
                .replace(/\b[a-z]/g, function(letter) {
                    return letter.toUpperCase();
                })
                ;
        });

        /**
         * Date Helper
         */
        Handlebars.registerHelper('date', function(dateString, context) {
            var date = Moment(dateString);

            if (_.has(context.hash, 'type') && _.indexOf(_.functions(date), context.hash.type) > -1) {
                return date[context.hash.type]();
            }

            return date.calendar();
        });

        /**
         * Replace Helper
         */
        Handlebars.registerHelper('replace', function(string, search, replace) {
            var regexp = new RegExp(search, 'g');

            return string.replace(regexp, replace);
        });

        /**
         * Ternary Helper
         */
        Handlebars.registerHelper('ternary', function(test, yes, no) {
            return test ? yes : no;
        });

        return Handlebars;
});