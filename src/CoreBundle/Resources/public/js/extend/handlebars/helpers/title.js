import { get, isObject, isUndefined, replace, lowerCase, upperCase } from 'lodash';

export default function(message) {
    if (isObject(message) && !isUndefined(get(message, 'data.root.title'))) {
        return message.data.root.title;
    }

    return replace(lowerCase(replace(message, /[-_]/g, ' ')), /\b[a-z]/g, upperCase);
}
