define(['marionette', 'template', 'lodash', 'accounting'], function(Mn, Template, _, Accounting) {
    return Mn.View.extend({
        template: Template.invoice.row,
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
        initialize: function() {
            setTimeout(_.bind(this.setModel, this), 0);
            setTimeout(_.bind(this.calcPrice, this), 0);
        },
        setModel: function() {
            this.$(':input').each(_.bind(function(index, input) {
                var $this = $(input),
                    type = $this.closest('td')[0].className.split('-')[1];

                if (this.model.get(type)) {
                    if ('tax' === type) {
                        $this.select2('val', this.model.get(type).id);
                    } else {
                        $this.val(this.model.get(type));
                    }
                }
            }, this));
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

            let qty = this.model.get('qty');
            let amount = parseFloat(qty || 0) * this.model.get('price');

            this.model.set('total', amount);
            this.$('.column-total').html(Accounting.formatMoney(this.model.get('total')));
        }
    });
});