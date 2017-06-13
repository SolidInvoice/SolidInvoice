import _ from "lodash";

export default function(message) {
    if (_.isObject(message) && !_.isUndefined(_.get(message, 'data.root.title'))) {
        return message.data.root.title;
    }

    return message
        .replace(/[-_]/g, ' ')
        .toLowerCase()
        .replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        })
        ;
};
