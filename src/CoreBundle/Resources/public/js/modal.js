define(['jquery', 'marionette', 'handlebars.runtime', 'template', 'lodash'], function($, Mn, Handlebars, Template, _) {
    "use strict";

    return Mn.View.extend({
        el: $('#modal-container').clone(),
        ui: {
            'save': '.btn-save'
        },
        triggers: {
            'click @ui.save': 'save'
        },
        constructor: function(options) {
            this.listenTo(this, 'render', this.listeners.render);
            this.listenTo(this, 'save', this.listeners.save);

            Mn.View.call(this, options);

            var modal = _.result(this, 'modal'),
                defaults = {
                    'titleClose': true
                };

            if (modal) {
                this.templateContext = _.extend(defaults, modal);
            }

            this._bindModalEvents(modal);
            this._attachListeners();
        },
        templateContext: function () {
            return {
                "modalContent": this.getOption('template')
            };
        },
        getTemplate: function() {
            this.templateContext = _.extend(this.templateContext, {"modalContent": this.getOption('template')});
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

                Mn.triggerMethod(this, 'modal:save', context)
            }
        },
        _bindModalEvents: function(modal) {
            _.each(_.result(modal, 'events'), _.bind(function(action, event) {
                if (_.isFunction(action)) {
                    this.listenTo(this, event, action);
                } else if (-1 !== _.indexOf(_.functions(this), action)) {
                    this.listenTo(this, event, this[action])
                } else {
                    throw "Callback specified for event " + event + " is not a valid callback"
                }
            }, this));
        },
        _attachListeners: function() {
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