define(['jquery', 'marionette', 'handlebars.runtime', 'template', 'lodash'], function($, Mn, Handlebars, Template, _) {
    "use strict";

    return Mn.View.extend({
        'el': '#modal-container',
        'triggers': {
            'click .btn-save': 'save'
        },
        'constructor': function(options) {
            this.listenTo(this, 'render', this.listeners.render);
            this.listenTo(this, 'save', this.listeners.save);

            Mn.View.call(this, options);

            var modal = _.result(this, 'modal');

            var defaults = {
                'titleClose': true
            };

            if (modal) {
                this.templateContext = _.extend(_.extend(defaults, modal), this.templateContext);
            }

            this._bindModalEvents(modal);

            this._attachListeners();

        },
        'getTemplate': function() {
            var template = this.getOption('template');

            Handlebars.registerPartial('modalContent', template);

            return Template.core.modal;
        },
        listeners: {
            render: function() {
                this.$el.modal();
            },
            save: function(context) {
                if (false === this.triggerMethod('before:modal:save', context)) {
                    return;
                }

                Mn._triggerMethod(this, 'modal:save', context)
            }
        },
        _bindModalEvents: function(modal) {
            _.each(_.result(modal, 'events'), function(action, event) {
                if (_.isFunction(action)) {
                    this.listenTo(this, event, action);
                } else if (-1 !== _.indexOf(_.functions(this), action)) {
                    this.listenTo(this, event, this[action])
                } else {
                    throw "Callback specified for event " + event + " is not a valid callback"
                }
            }, this);
        },
        _attachListeners: function () {
            var view = this;

            this.$el.on('show.bs.modal', function () {
                view.trigger('modal:show');
            });

            this.$el.on('hide.bs.modal', function () {
                view.trigger('modal:hide');
            });

            this.$el.on('hidden.bs.modal', function () {
                view.destroy();
            });
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