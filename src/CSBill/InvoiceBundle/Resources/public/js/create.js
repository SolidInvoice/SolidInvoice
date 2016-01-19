define(
    ['core/module', 'backbone', 'lodash', 'invoice/view/client'],
    function(Module, Backbone, _, ClientView) {
        "use strict";

        return Module.extend({
            regions: {
                'clientInfo': '#client-info'
            },
            initialize: function(options) {
                var model = new Backbone.Model(options.client);
                var viewOptions = {type: 'invoice', model : model};

                this.app.getRegion('clientInfo').show(new ClientView(_.merge(options, viewOptions)));
            }
        });
    });