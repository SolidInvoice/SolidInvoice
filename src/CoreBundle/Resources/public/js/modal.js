import $ from 'jquery';
import { triggerMethod } from 'backbone.marionette';
import Template from '../templates/modal.hbs';
import View from './view';
import { each, extend, functionsIn, indexOf, isFunction, result } from 'lodash';
import './extend/modal'

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

        options.el = $('#modal-container').clone();
        View.call(this, options);

        const modal = result(this, 'modal'),
            defaults = {
                'titleClose': true
            };

        if (modal) {
            this.templateContext = extend(defaults, modal);
        }

        this._bindModalEvents(modal);
        this._attachListeners();
    },
    getTemplate () {
        this.templateContext = extend(this.templateContext, { "modalContent": this.getOption('template') });
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
        each(result(modal, 'events'), (action, event) => {
            if (isFunction(action)) {
                this.listenTo(this, event, action);
            } else if (-1 !== indexOf(functionsIn(this), action)) {
                this.listenTo(this, event, this[action])
            } else {
                throw "Callback specified for event " + event + " is not a valid callback"
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
