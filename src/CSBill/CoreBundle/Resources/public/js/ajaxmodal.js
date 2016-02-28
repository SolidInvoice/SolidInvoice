define(['jquery', 'lodash', 'core/modal', 'bootstrap.modal', 'bootstrap.modalmanager'], function($, _, Modal) {
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
            if (_.has(options, 'model')) {
                this.model = options.model;
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