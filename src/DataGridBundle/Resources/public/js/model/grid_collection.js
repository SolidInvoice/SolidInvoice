import Routing from 'fos-router';
import PageableCollection from 'backbone.paginator';

export default PageableCollection.extend({
    name: null,
    parameters: {},
    initialize (name, parameters) {
        this.name = name;
        this.parameters = parameters;
    },
    url () {
        return Routing.generate('_grid_data', { 'name': this.name, 'parameters': this.parameters });
    },

    // Initial pagination states
    state: {
        pageSize: 15,
        sortKey: 'created',
        order: 1
    },

    // You can remap the query parameters from `state` keys from
    // the default to those your server supports
    queryParams: {
        totalPages: null,
        totalRecords: null,
        sortKey: 'sort'
    },

    parseState (resp) {
        return {
            totalRecords: resp.count
        };
    },

    parseRecords (resp) {
        return resp.items;
    }
});
