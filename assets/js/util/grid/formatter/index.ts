import client from './client';
import {
    formatDate as date,
    formatMoney as money,
    formatDiscount as discount,
} from './basic';

export default {
    client,
    date,
    money,
    discount,
} as {
    [key: string]: (value: any) => string;
};
