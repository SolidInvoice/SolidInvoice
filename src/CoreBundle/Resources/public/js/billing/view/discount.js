define(['core/view'], function(ItemView) {
    return ItemView.extend({
        el: '#discount',
        ui: {
            'value': '.discount-value',
            'type': '.discount-type'
        },
        events: {
            'keyup @ui.value': 'setDiscount',
            'change @ui.type': 'setDiscount',
        },
        setDiscount: function() {
            this.model.set('value', parseFloat(this.ui.value.val()));
            this.model.set('type', this.ui.type.val());

            this.getOption('collection').trigger('change');
        },
        initialize: function() {
            this.setDiscount();
        }
    });
});