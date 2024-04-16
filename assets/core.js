import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';

import { startStimulusApp } from '@symfony/stimulus-bridge';

App(Module);

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
