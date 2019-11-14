import { get, isObject, isUndefined } from "lodash";

export default function(message) {
    if (isObject(message) && !isUndefined(get(message, 'data.root.title'))) {
        return message.data.root.title;
    }

    return message
        .replace(/[-_]/g, ' ')
        .toLowerCase()
        .replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        })
        ;
}
