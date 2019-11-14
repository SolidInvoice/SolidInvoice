import { isUndefined } from 'lodash';
import Accounting from 'accounting';

export default function(amount, context) {
    if (1 < arguments.length) {
        if (!isUndefined(context.hash.symbol) && false === context.hash.symbol) {
            return Accounting.formatNumber(amount, Accounting.settings.currency.precision, Accounting.settings.currency.thousand, Accounting.settings.currency.decimal);
        }

        return Accounting.formatMoney(amount);
    }

    return Accounting.settings.currency.symbol;
}
