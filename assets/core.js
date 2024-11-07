import App from 'SolidInvoiceCore/js/app';
import Module from 'SolidInvoiceCore/js/module';
import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';

import { startStimulusApp } from '@symfony/stimulus-bridge';
import PasswordVisibility from '@stimulus-components/password-visibility';
import Clipboard from '@stimulus-components/clipboard';

App(Module);
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('checkbox-select-all', CheckboxSelectAll);
app.register('password-visibility', PasswordVisibility);
app.register('clipboard', Clipboard);
