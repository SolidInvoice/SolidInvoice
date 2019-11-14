import Moment from "moment";
import { functions, has, indexOf } from "lodash";

export default function(dateString, context) {
    const date = Moment(dateString);

    if (has(context.hash, 'type') && indexOf(functions(date), context.hash.type) > -1) {
        return date[context.hash.type]();
    }

    return date.calendar();
}
