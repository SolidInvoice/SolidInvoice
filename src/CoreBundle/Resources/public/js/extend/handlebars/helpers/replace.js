import { replace as _replace } from 'lodash';

export default function(string, search, replace) {
    const regexp = new RegExp(search, 'g');

    return _replace(string, regexp, replace);
}
