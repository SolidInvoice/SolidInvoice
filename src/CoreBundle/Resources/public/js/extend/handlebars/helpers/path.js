import { isObject, isUndefined } from 'lodash';
import Routing from 'fos-router';

export default function(route, context) {
    let params = {};

    if (isObject(context)) {
        params = context;
    }

    if (!isUndefined(context.hash)) {
        params = context.hash;
    }

    return Routing.generate(route, params);
}
