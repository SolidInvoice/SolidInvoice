export default class UriFormatter {
    static format(value) {
        return `<a href="${value}" target="_blank" rel="noopener">${value}</a>`;
    }
}