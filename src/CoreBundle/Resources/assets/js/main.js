import Vue from 'vue';
import {config} from '@SolidInvoiceUIBundle/Resources/assets/Config';
import Accounting from 'accounting';
import Bootstrap from 'bootstrap';
import AdminLTE from 'admin-lte';

config.load();

Accounting.settings = config.get('accounting');

Vue.component('grid', () => import('@SolidInvoiceDataGridBundle/Resources/assets/components/DataGrid.vue'));
Vue.component('tax-validator', () => import('@SolidInvoiceTaxBundle/Resources/assets/components/TaxValidator.vue'));

let App = new Vue({
    'el': '#app'
});
