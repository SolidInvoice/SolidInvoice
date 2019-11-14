export default function(string, search, replace) {
    const regexp = new RegExp(search, 'g');

    return string.replace(regexp, replace);
}
