import Backbone from 'backbone';
import Router from 'router';

export default Backbone.Model.extend({
    url() {
        return Router.generate('_xhr_clients_credit_update', { 'client': this.id })
    },
    defaults: {
        credit: 0
    },
    validate(values) {
        const credit = parseFloat(values.credit);

        if (credit === 0) {
            // @TODO: Add translation for this text
            return 'Credit value cannot be 0'
        }
    }
});
