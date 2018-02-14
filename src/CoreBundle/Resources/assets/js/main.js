import Vue from 'vue';
import PortalVue from 'portal-vue';
import {config} from '@SolidInvoiceUIBundle/Resources/assets/Config';
import Accounting from 'accounting';
import Bootstrap from 'bootstrap';
import AdminLTE from 'admin-lte';
import DropDownButton from '@SolidInvoiceCoreBundle/Resources/assets/components/DropdownButton.vue';
import ContactInfo from '@SolidInvoiceClientBundle/Resources/assets/components/ContactInfo.vue';

Vue.use(PortalVue);

config.load();

Accounting.settings = config.get('accounting');

Vue.component('grid', () => import('@SolidInvoiceDataGridBundle/Resources/assets/components/DataGrid.vue'));
Vue.component('form-collection', () => import('@SolidInvoiceCoreBundle/Resources/assets/components/FormCollection.vue'));
Vue.component('tax-validator', () => import('@SolidInvoiceTaxBundle/Resources/assets/components/TaxValidator.vue'));
Vue.component('box', () => import('@SolidInvoiceCoreBundle/Resources/assets/components/Box.vue'));
Vue.component('contact-info', ContactInfo);
Vue.component('dropdown', DropDownButton);

let App = new Vue({
    'el': '#app',
    methods: {
        addRow: function(ref) {
            this.$refs[ref].addRow();
        }
    }
});
{}