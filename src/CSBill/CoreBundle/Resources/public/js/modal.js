define(['marionette', 'handlebars.runtime', 'template', 'lodash'], function(Mn, Handlebars, Template, _) {
    "use strict";

    return Mn.ItemView.extend({
        'el': '#modal-container',
        'modal' : {},
        'triggers' : {
            'click .btn-save': 'modal:save'
        },
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

            var view = this;

            _.each(_.result(modal, 'events'), function (action, event) {
                view.listenTo(view, event, view.options[action]);
            });
        },
        onRender: function () {
            var view = this;

            this.$el.on('show.bs.modal', function() {
                view.trigger('modal:show');
            });

            this.$el.on('hide.bs.modal', function() {
                view.trigger('modal:hide');
            });

            this.$el.on('hidden.bs.modal', function() {
                view.destroy();
            });

            this.$el.modal();
        },
        _removeElement: function () {
            this.$el.empty();
        }
    });
});