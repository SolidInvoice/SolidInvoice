import Backbone from 'backbone';
import Router from 'router';
import { assignIn } from 'lodash';

export default Backbone.Model.extend({
    defaults: {},
    url () {
        return Router.generate('_xhr_clients_address', { 'id': this.id })
    },
    destroy (options) {
        const opts = assignIn({ url: Router.generate('_xhr_clients_delete_address', { 'id': this.id }) }, options || {});
        return Backbone.Model.prototype.destroy.call(this, opts);
    }
});
