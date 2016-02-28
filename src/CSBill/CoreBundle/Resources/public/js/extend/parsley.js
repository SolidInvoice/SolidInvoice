define(['marionette', 'lodash', 'parsley'], function(Mn, _) {
    "use strict";

    var Parsley = Mn.Object.extend({
        validate: function (context) {
            var valid = true;

            _.each(context.view.$(':input'), function (el) {
                var p = context.view.$(el).parsley();

                p.validate();

                valid = valid && p.isValid();
            });

            return valid;
        }
    });

    return new Parsley();
});