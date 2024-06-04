import Backbone from 'backbone';
import Routing from 'fos-router';
import { assignIn } from 'lodash';

export default Backbone.Model.extend({
    defaults: {},
    url () {
        return Routing.generate('_xhr_clients_address', { 'id': this.id })
    },
    destroy (options) {
        const opts = assignIn({ url: Routing.generate('_xhr_clients_delete_address', { 'id': this.id }) }, options || {});
        return Backbone.Model.prototype.destroy.call(this, opts);
    }
});
