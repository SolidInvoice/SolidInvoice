import Accounting from 'accounting';

export function formatDate (date: string) {
    let obj = new Date(date);

    return obj.getFullYear() + '-' + (obj.getMonth() + 1) + '-' + obj.getDate();
}

export function formatMoney (value: any) {
    return Accounting.formatMoney(value.value / 100, value.symbol);
}

export function formatDiscount (value: { type: string | null, valuePercentage: number, valueMoney: number }) {
    if (null === value.type) {
        return null;
    }

    if ('percentage' === value.type) {
        return value.valuePercentage + '%';
    }

    return Accounting.formatMoney(value.valueMoney / 100);
}
