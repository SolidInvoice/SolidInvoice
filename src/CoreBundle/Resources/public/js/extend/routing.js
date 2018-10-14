define(['lodash', 'fos_router', 'fos_routing_data'], function(_, Router, data) {

    if (!_.isUndefined(data)) {
        Router.setRoutingData(data);
    }

    return Router;
});