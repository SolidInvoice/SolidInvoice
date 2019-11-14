import $ from 'jquery';
import ItemView from 'SolidInvoiceCore/js/view';

export default ItemView.extend({
    el: '#discount',
    ui: {
        'value': '.discount-value',
        'type': '.discount-type',
        'discount_types': '.discount-types',
        'discount_display': '#discount-display',
    },
    events: {
        'keyup @ui.value': 'setDiscount',
        'change @ui.type': 'setDiscount',
    },
    setDiscount () {
        console.log('FOOBAR@!@');
        this.model.set('value', parseFloat(this.ui.value.val()));
        this.model.set('type', this.ui.type.val());

        this.getOption('collection').trigger('change');
    },
    initialize () {
        this.ui.discount_types.on('click', (event) => {
            event.preventDefault();
            const $this = $(event.target);
            this.ui.discount_display.html($this.html());
            // eslint-disable-next-line
            this.ui.discount_types.filter('.hidden').removeClass('hidden');
            $this.addClass('hidden');
            this.ui.type.val($this.data('name')).trigger('change');
        });

        this.setDiscount();
    }
});
