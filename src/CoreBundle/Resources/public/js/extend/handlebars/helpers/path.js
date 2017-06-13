export default function (route, context) {
    var params = {};

    if (_.isObject(context)) {
        params = context;
    }

    if (!_.isUndefined(context.hash)) {
        params = context.hash;
    }

    return ''; //Routing.generate(route, params);
};
