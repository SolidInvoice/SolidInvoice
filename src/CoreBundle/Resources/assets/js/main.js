import Vue from 'vue';
import {config} from '@SolidInvoiceUIBundle/Resources/assets/Config';
import Accounting from 'accounting';
import Bootstrap from 'bootstrap';
import AdminLTE from 'admin-lte';

config.load();

Accounting.settings = config.get('accounting');

let App = new Vue({
    'el': '#app'
});
