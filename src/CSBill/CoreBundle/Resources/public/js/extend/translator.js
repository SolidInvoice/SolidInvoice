define(['translation_data'], function (Translator) {
    "use strict";

    return function (message, parameters, domain, locale) {
        return Translator.trans(message, parameters, domain, locale);
    };
});