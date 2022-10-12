import Modal from 'SolidInvoiceCore/js/modal';
import Accounting from 'accounting';
import Template from '../../templates/partials/add_credit.hbs';
import Handlebars from 'handlebars/runtime';
import { assignIn } from 'lodash';
import Translator from 'translator';

// Work around handlebars-loader not supporting dynamic partials
Handlebars.registerPartial('add_credit', Template);

export default Modal.extend({
    'template': 'add_credit',
    'modal': {
        'title': Translator.trans('client.modal.add_credit'),
        'buttons': {
            'Close': {
                'close': true,
                'class': 'warning',
                'flat': true
            },
            'Save': {
                'class': 'success',
                'save': true
            }
        },
        'events': {
            'modal:save': 'saveCredit'
        }
    },
    ui: assignIn({
        'creditAmount': '#credit_amount',
        'creditForm': '#credit-form',
    }, Modal.prototype.ui),
    constructor (options) {
        Modal.call(this, options);
        this.model.on('invalid', (object, validationError) => {
            this.templateContext.error = validationError;
            this.hideLoader();
            this.render();
        });
    },
    saveCredit() {
        this.showLoader();

        this.model.set('credit', Accounting.toFixed(this.ui.creditAmount.val(), 2));

        this.model.save({}, {
            success: () => {
                this.$el.modal('hide');
            },
            error: () => {
                this.hideLoader();
            }
        });
    }
});
