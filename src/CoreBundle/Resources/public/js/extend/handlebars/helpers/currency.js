import { isUndefined } from "lodash";
import Accounting from "accounting";

export default function(amount, context) {
    if (arguments.length > 1) {
        if (!isUndefined(context.hash.symbol) && context.hash.symbol === false) {
            return Accounting.formatNumber(amount, Accounting.settings.currency.precision, Accounting.settings.currency.thousand, Accounting.settings.currency.decimal);
        }

        return Accounting.formatMoney(amount);
    }

    return Accounting.settings.currency.symbol;
}
