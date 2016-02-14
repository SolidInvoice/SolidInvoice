define(['marionette', 'template', 'accounting'], function (Mn, Template, Accounting) {
    return Mn.ItemView.extend({
        template: Template['invoice/row'],
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
        removeItem: function(event) {
            event.preventDefault();

            this.model.collection.remove(this.model);
        },
        calcPrice: function() {
            this.$(':input').each(_.bind(function(index, input) {
                var $this = $(input),
                    type = $this.closest('td')[0].className.split('-')[1],
                    val = $this.val();

                if ('price' === type) {
                    val = Accounting.unformat(val);
                }

                if ('tax' === type) {
                    val = $this.find(':selected').data();
                }

                this.model.set(type, val);
            }, this));

            var amount = this.model.get('qty') * this.model.get('price');

            this.model.set('total', amount);
            this.$('.column-total').html(Accounting.formatMoney(this.model.get('total')));
        }
    });
});