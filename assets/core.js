import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';

import { startStimulusApp } from '@symfony/stimulus-bridge';

App(Module);
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('checkbox-select-all', CheckboxSelectAll);
