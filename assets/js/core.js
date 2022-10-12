import { registerVueControllerComponents } from '@symfony/ux-vue';
import { configureCompat } from 'vue';

import '../scss/app.scss';

// default everything to Vue 3 behavior, and only enable compat
// for certain features
configureCompat({
    MODE: 2,
    /*COMPONENT_FUNCTIONAL: true,
    CUSTOM_DIR: true,
    GLOBAL_EXTEND: true,
    GLOBAL_MOUNT: true,
    GLOBAL_SET: true,
    INSTANCE_EVENT_EMITTER: true,
    INSTANCE_LISTENERS: true,
    RENDER_FUNCTION: true,*/
});

registerVueControllerComponents(require.context('./vue/components', true, /\.vue$/));

// start the Stimulus application
import './bootstrap';

// @TODO: Remove everything below this line when all JS is converted to Vue
import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';

App(Module);
