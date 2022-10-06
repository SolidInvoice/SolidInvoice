import { registerVueControllerComponents } from '@symfony/ux-vue';

import '../scss/app.scss';

// By putting only controller components in `vue/controllers`, you ensure that
// internal components won't be automatically included in your JS built file if
// they are not necessary.
registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));

// start the Stimulus application
import './bootstrap';

// @TODO: Remove everything below this line when all JS is converted to Vue
import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';

App(Module);
