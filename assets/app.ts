import './scss/app.scss';
import './webmcp';

// @ts-expect-error - No types available
import CsrfProtection from '@solidworx/platform/controllers/csrf_protection';
// @ts-expect-error - No types available
import Loading from '@solidworx/platform/controllers/loading';
// @ts-expect-error - No types available
import Modal from '@solidworx/platform/controllers/modal';

import CheckboxSelectAll from '@stimulus-components/checkbox-select-all';
import PasswordVisibility from '@stimulus-components/password-visibility';
import Clipboard from '@stimulus-components/clipboard';

import { startStimulusApp } from '@symfony/stimulus-bridge';
import PasswordStrength from './controllers/password-strength-controller';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('checkbox-select-all', CheckboxSelectAll);
app.register('password-visibility', PasswordVisibility);
app.register('clipboard', Clipboard);
app.register('password-strength', PasswordStrength);

app.register('csrf-protection', CsrfProtection);
app.register('loading', Loading);
app.register('modal', Modal);

export default app
