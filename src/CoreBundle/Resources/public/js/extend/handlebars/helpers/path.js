import { isObject, isUndefined } from 'lodash';
import Router from 'router';

export default function(route, context) {
    let params = {};

    if (isObject(context)) {
        params = context;
    }

    if (!isUndefined(context.hash)) {
        params = context.hash;
    }

    return Router.generate(route, params);
}
