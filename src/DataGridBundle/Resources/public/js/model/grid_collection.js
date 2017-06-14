define(['backbone', 'routing', 'backbone.paginator'], function (Backbone, Routing) {
    return Backbone.PageableCollection.extend({
        name: null,
        parameters: {},
        options: {},
        model: Backbone.Model,
        initialize: function (name, parameters) {
            this.name = name;
            this.parameters = parameters;
        },
        url: function () {
            return Routing.generate('_grid_data', {'name': this.name, 'parameters': this.parameters});
        },

        getOptions: function () {
            return this.options;
        },

        // Initial pagination states
        state: {
            pageSize: 15,
            sortKey: "created",
            order: 1,
            options: {}
        },

        // You can remap the query parameters from `state` keys from
        // the default to those your server supports
        queryParams: {
            totalPages: null,
            totalRecords: null,
            sortKey: "sort",
            options: {}
        },

        parseState: function (resp, queryParams, state, options) {
            return {
                totalRecords: resp.data.count,
            };
        },

        parseRecords: function (resp, options) {
            return resp.data.items;
        }
    });
});