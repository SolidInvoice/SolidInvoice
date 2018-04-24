import Vue from 'vue';
import Vuetify from 'vuetify';
import Vuex from 'vuex';
import VuetifyBundle from '@SolidWorxVuetifyBundle/Resources/public/js/vuetify-bundle.min';
import {config} from '@SolidInvoiceUIBundle/Resources/assets/Config';
import Accounting from 'accounting';
import DropDownButton from '@SolidInvoiceCoreBundle/Resources/assets/components/DropdownButton.vue';
import storeConfig from './store';
import Http from './http';

import 'vuetify/dist/vuetify.min.css' // Ensure you are using css-loader

Vue.use(Vuetify);
Vue.use(Vuex);
Vue.use(VuetifyBundle);

config.load();

Accounting.settings = config.get('accounting');

Vue.component('grid', () => import(/* webpackChunkName: "grid" */ '@SolidInvoiceDataGridBundle/Resources/assets/components/DataGrid.vue'));
Vue.component('tax-validator', () => import(/* webpackChunkName: "tax-validator" */ '@SolidInvoiceTaxBundle/Resources/assets/components/TaxValidator.vue'));
Vue.component('box', () => import(/* webpackChunkName: "box" */ '@SolidInvoiceCoreBundle/Resources/assets/components/Box.vue'));
Vue.component('dropdown', DropDownButton);

const store = new Vuex.Store(storeConfig);

/*const original = Vue.prototype.$emit;

Vue.prototype.$emit = function (...args) {
    const res = original.apply(this, args);

    store.dispatch('event', args);

    return res
};*/

export default new Vue({
    'el': '#app',
    store,
    provide: {
        http: new Http()
    }
})