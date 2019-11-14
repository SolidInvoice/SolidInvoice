import ItemView from 'SolidInvoiceCore/js/view';
import CreditModal from './credit_modal';
import Template from '../../templates/credit.hbs';

export default ItemView.extend({
    template: Template,

    ui: {
        'addCredit': '#add-credit-button'
    },

    events: {
        'click @ui.addCredit': 'addCredit'
    },

    modal: null,

    initialize () {
        this.listenTo(this.model, 'sync', this.modelSynced);
    },

    modelSynced () {
        this.render();
    },

    addCredit (event) {
        event.preventDefault();

        ( new CreditModal({ model: this.model }) ).render();
    }
});

