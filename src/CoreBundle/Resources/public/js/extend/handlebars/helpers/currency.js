import accounting from 'accounting';

export default function(amount, context) {
    if (arguments.length > 1) {
        if (!_.isUndefined(context.hash.symbol) && context.hash.symbol === false) {
            return accounting.formatNumber(amount, accounting.settings.currency.precision, accounting.settings.currency.thousand, accounting.settings.currency.decimal);
        }

        return accounting.formatMoney(amount);
    }

    return accounting.settings.currency.symbol;
};
