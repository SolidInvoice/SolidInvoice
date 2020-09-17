/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Backgrid from 'backgrid';
import Backbone from 'backbone';
import { forEach, isUndefined } from 'lodash';
import Template from '../../templates/invoice_link.hbs';
import Cron from 'SolidInvoiceCore/js/util/form/cron';

Backgrid.Extension.InvoiceCell = Backgrid.Cell.extend({
    template: Template,
    _setRouteParams (action) {
        action.routeParams = {};

        forEach(action.route_params, (value, key) => {
            action.routeParams[key] = this.model.get(value);
        });
    },
    render () {
        this.$el.empty();

        const invoice = this.model.get('invoice');

        if (!isUndefined(invoice)) {
            this.$el.append(this.template({ 'invoice': invoice }));
        }

        this.delegateEvents();
        return this;
    }
});

Backgrid.Extension.RecurringInvoiceEndCell = Backgrid.DateCell.extend({
    initialize () {
        Backgrid.DateCell.__super__.initialize.apply(this, arguments);
    }
});

Backgrid.Extension.RecurringInvoiceStartCell = Backgrid.DateCell.extend({
    initialize () {
        Backgrid.DateCell.__super__.initialize.apply(this, arguments);
    }
});

Backgrid.Extension.RecurringInvoiceFrequencyCell = Backgrid.StringCell.extend({
    initialize () {
        Backgrid.StringCell.__super__.initialize.apply(this, arguments);
    },
    render () {
        const value = this.model.get(this.column.get('name'));

        Cron(this.$el, {
            no_reset_button: true,
            default_value: value
        }).disable();

        return this;
    }
});

export default Backgrid;
