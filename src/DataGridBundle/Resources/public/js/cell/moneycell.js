/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backgrid', 'lodash'], function(Backgrid, _) {
    Backgrid.Extension.MoneyCell = Backgrid.StringCell.extend({
        render: function() {
            this.$el.empty();

            var name = this.column.get('name'),
                value = this.model.get(name + '.value'),
                currency = this.model.get(name + '.currency');

            if (currency) {
                this.$el.text(this.formatter.fromRaw({"value": value, "currency": currency}, this.model));
            } else {
                // We don't have a currency set, just display the raw value
                this.$el.text((parseInt(this.model.get(name), 10) / 100).toFixed(2));
            }

            this.updateStateClassesMaybe();
            this.delegateEvents();
            return this;
        }
    });
});