import $ from 'jquery';
import { triggerMethod } from 'backbone.marionette';
import Template from '../templates/modal.hbs';
import View from './view';
import { forEach, assignIn, functionsIn, includes, isFunction, result } from 'lodash';

export default View.extend({
    ui: {
        'save': '.btn-save'
    },
    triggers: {
        'click @ui.save': 'save'
    },
    constructor (options) {
        this.listenTo(this, 'render', this.listeners.render);
        this.listenTo(this, 'save', this.listeners.save);

        options.el = $('#modal-container').clone().get( 0 );
        View.call(this, options);

        const modal = result(this, 'modal'),
            defaults = {
                'titleClose': true
            };

        if (modal) {
            this.templateContext = assignIn(defaults, modal);
        }

        this._bindModalEvents(modal);
        this._attachListeners();
    },
    getTemplate () {
        this.templateContext = assignIn(this.templateContext, { 'modalContent': this.getOption('template') });
        return Template;
    },
    listeners: {
        render () {
            this.$el.modal({ backdrop: 'static' });
        },
        save (context) {
            if (false === this.triggerMethod('before:modal:save', context)) {
                return;
            }

            triggerMethod(this, 'modal:save', context)
        }
    },
    _bindModalEvents (modal) {
        forEach(result(modal, 'events'), (action, event) => {
            if (isFunction(action)) {
                this.listenTo(this, event, action);
            } else if (includes(functionsIn(this), action)) {
                this.listenTo(this, event, this[action])
            } else {
                throw `Callback specified for event ${event} is not a valid callback`
            }
        });
    },
    _attachListeners () {
        this.$el.on('show.bs.modal', () => {
            this.trigger('modal:show');
        });

        this.$el.on('hide.bs.modal', () => {
            this.trigger('modal:hide');
        });

        this.$el.on('hidden.bs.modal', () => {
            this.destroy();
        });
    },
    _removeElement () {
        this.$el.empty();
    }
});
