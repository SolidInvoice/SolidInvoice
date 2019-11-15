import Moment from 'moment';
import { functions, has, includes } from 'lodash';

export default function(dateString, context) {
    const date = Moment(dateString);

    if (has(context.hash, 'type') && includes(functions(date), context.hash.type)) {
        return date[context.hash.type]();
    }

    return date.calendar();
}
