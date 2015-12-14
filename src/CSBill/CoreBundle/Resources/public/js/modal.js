define(['jquery', 'marionette', 'handlebars.runtime', 'template', 'lodash'], function($, Mn, Handlebars, Template, _) {
    "use strict";

    return Mn.ItemView.extend({
        'el': '#modal-container',
        'triggers': {
            'click .btn-save': 'modal:save'
        },
        'constructor': function(options) {
            $.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
                '<div class="loading-spinner">' +
                    '<div class="progress progress-striped active">' +
                        '<div class="progress-bar"></div>' +
                    '</div>' +
                '</div>';

            $.fn.modal.defaults.maxHeight = function() {
                // subtract the height of the modal header and footer
                return $(window).height() - 165;
            };

            this.listenTo(this, 'before:render', this.listeners.beforeRender);
            this.listenTo(this, 'render', this.listeners.render);

            Mn.ItemView.call(this, options);

        },
        'getTemplate': function() {
            Handlebars.registerPartial('modalContent', this.getOption('template'));

            return Template['modal'];
        },
        listeners: {
            beforeRender: function() {
                var modal = _.result(this, 'modal');

                var defaults = {
                    'titleClose': true
                };

                if (modal) {
                    this.templateHelpers = _.extend(_.extend(defaults, modal), this.templateHelpers);
                }

                var view = this;

                _.each(_.result(modal, 'events'), function(action, event) {
                    view.listenTo(view, event, view[action]);
                });
            },
            render: function() {
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
            }
        },
        _removeElement: function() {
            this.$el.empty();
        },
        showLoader: function() {
            this.$el.modal('loading');
        },
        hideLoader: function() {
            this.$el.modal('removeLoading');
        }
    });
});