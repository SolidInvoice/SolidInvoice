import $ from 'jquery';
import { has } from 'lodash';
import Modal from './modal'
import Handlebars from 'handlebars/runtime'

export default Modal.extend({
    /**
     * @type {Backbone.Model}
     */
    model: null,

    template: null,

    route: null,

    /**
     * @param {{model: Backbone.Model}} options
     */
    constructor(options) {
        if (has(options, 'model')) {
            this.model = options.model;
        }

        this.route = options.route;

        if (!this.route) {
            throw 'A "route" must be specified for an Ajax Modal.';
        }

        Modal.call(this, options);

        this._loadContent();
    },

    _loadContent() {
        const route = this.getOption('route'),
            $body = $('body');

        $body.modalmanager('loading');

        $.get(route, (data) => {
            this.options.template = route;
            Handlebars.registerPartial(route, () => data);
            this.options.template = route;

            $body.modalmanager('loading');

            this.render();
        });
    }
});
