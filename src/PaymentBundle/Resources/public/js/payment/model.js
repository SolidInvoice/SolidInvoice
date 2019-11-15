import Backbone from 'backbone';
import Router from 'router';

export default Backbone.Model.extend({
    defaults: {},
    url: Router.generate('_xhr_payments_method_list')
});
