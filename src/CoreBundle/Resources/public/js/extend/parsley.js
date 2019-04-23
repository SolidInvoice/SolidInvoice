define(['marionette', 'lodash', 'parsley'], function(Mn, _) {
    "use strict";

    var Parsley = Mn.MnObject.extend({
        validate: function (context) {
            var valid = true;
            _.each(context.$(':input'), function (el) {
                var p = context.$(el).parsley();

                p.validate();

                valid = valid && p.isValid();
            });

            return valid;
        }
    });

    return new Parsley();
});