import $ from 'jquery';
import ItemView from 'SolidInvoiceCore/js/view';
import Template from 'SolidInvoiceInvoice/templates/row.hbs';
import Accounting from 'accounting';
import { split } from 'lodash';

export default ItemView.extend({
    template: Template,
    tagName: 'tr',
    ui: {
        removeItem: '.remove-item',
        input: ':input'
    },
    events: {
        'click @ui.removeItem': 'removeItem',
        'keyup @ui.input': 'calcPrice',
        'change @ui.input': 'calcPrice'
    },
    removeItem (event) {
        event.preventDefault();

        this.model.collection.remove(this.model);
    },
    async onRender () {
        await this.setModel();
        await this.calcPrice();
    },
    async setModel () {
        this.$(':input').each((index, input) => {
            const $this = $(input),
                type = split($this.closest('td')[0].className, '-')[1];

            if ('qty' === type && !this.model.get(type)) {
                this.model.set(type, 1);
            }

            if (this.model.get(type)) {
                $this.val(this.model.get(type));
            }
        });
    },
    async calcPrice () {
        this.$(':input').each((index, input) => {
            const $this = $(input),
                type = split($this.closest('td')[0].className, '-')[1];

            let val = $this.val();

            if ('price' === type) {
                val = Accounting.unformat(val);
            }

            if ('tax' === type) {
                // eslint-disable-next-line
                val = $this.find(':selected').data();
            }

            this.model.set(type, val);
        });

        let amount = parseFloat(this.model.get('qty')) * this.model.get('price');

        this.model.set('total', amount);
        this.$('.column-total').html(Accounting.formatMoney(this.model.get('total')));
    }
});
