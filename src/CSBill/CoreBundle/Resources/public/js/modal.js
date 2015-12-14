define(['marionette', 'handlebars.runtime', 'template', 'lodash'], function(Mn, Handlebars, Template, _) {
    "use strict";

    return Mn.ItemView.extend({
        'el': '#modal-container',
        'modal' : {},
        'getTemplate': function () {
            Handlebars.registerPartial('modalContent', this.getOption('template'));

            return Template['modal'];
        },
        onBeforeRender: function () {
            var modal = _.result(this.options, 'modal');

            var defaults = {
                'titleClose' : true
            };

            if (modal) {
                this.options.templateHelpers = _.extend(_.extend(defaults, modal), this.options.templateHelpers);
            }
        },
        onRender: function () {
            var view = this;

            this.$el.on('show.bs.modal', function() {
                view.trigger('modal:show');
            });

            this.$el.on('hide.bs.modal', function() {
                view.trigger('modal:hide');
            });

            this.$el.modal();
        }
    });
});