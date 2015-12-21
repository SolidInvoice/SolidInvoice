define(['jquery', 'core/modal', 'bootstrap.modal', 'bootstrap.modalmanager'], function($, Modal) {
    "use strict";

    return Modal.extend({
        /**
         * @type {Backbone.Model}
         */
        model: null,

        template: null,

        route: null,

        /**
         * @param {{model: Backbone.Model}} options
         */
        constructor: function(options) {
            this.model = options.model;

            if (!this.model) {
                throw 'A "model" must be specified for an Ajax Modal.';
            }

            this.route = options.route;

            if (!this.route) {
                throw 'A "route" must be specified for an Ajax Modal.';
            }

            Modal.call(this, options);

            this._loadContent();
        },

        _loadContent: function () {
            var route = this.getOption('route');

            var view = this;

            $('body').modalmanager('loading');

            $.getJSON(route, function (data) {
                view.options.template = data.content;

                $('body').modalmanager('loading');

                view.render();
            });
        }
    });
});