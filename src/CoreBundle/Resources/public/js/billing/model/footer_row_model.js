import Backbone from 'backbone';

export default Backbone.Model.extend({
    defaults: {
        subTotal: 0,
        discount: 0,
        tax: {},
        total: 0,
        hasTax: false
    }
});
