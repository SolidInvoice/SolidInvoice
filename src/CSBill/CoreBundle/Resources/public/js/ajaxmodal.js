define(['jquery', 'core/modal', 'bootstrap.modal', 'bootstrap.modalmanager'], function($, Modal) {
    "use strict";

    return Modal.extend({
        /**
         * @type {Backbone.Model}
         */
        model: null,

        /**
         * @param {{model: Backbone.Model}} options
         */
        constructor: function(options) {
            this.model = options.model;

            if (!this.model) {
                throw 'A "model" must be specified for a view.';
            }

            Modal.call(this, options);
        },

        initialize: function() {
            this.listenTo(this, "modal:show", this.hideLoader);
        },

        showLoader: function() {
            this.$el.modalmanager('loading');
        },

        hideLoader: function() {
            this.$el.modalmanager('removeLoading');
        },

        /**
         * @param {HTMLElement|String} route
         */
        load: function(route) {
            var view = this;

            this.showLoader();

            this.$el.on('show.bs.modal', function() {
                view.trigger('modal:show');
            });

            this.$el.on('hide.bs.modal', function() {
                view.trigger('modal:hide');
            });

            $.getJSON(route, function(data) {
                if (data.content !== undefined) {
                    view.$el.html(data.content);
                }

                view.$el.modal();
            });

            return this;
        }
    });
});