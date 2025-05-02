import 'admin-lte';
import 'bootstrap';

import { startStimulusApp } from '@symfony/stimulus-bridge';

import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import PasswordVisibility from '@stimulus-components/password-visibility';
import Clipboard from '@stimulus-components/clipboard';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('checkbox-select-all', CheckboxSelectAll);
app.register('password-visibility', PasswordVisibility);
app.register('clipboard', Clipboard);
