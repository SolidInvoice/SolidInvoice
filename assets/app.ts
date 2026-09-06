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

import PasswordStrength from './controllers/password-strength-controller';

// Reuse the Stimulus application bootstrapped by the platform's `_platform_ui`
// entry instead of starting a second one, which would register every
// controller twice. Required at runtime so `core.ts` is not pulled into
// TypeScript's program, as it does not type-check under `noImplicitAny`.
// eslint-disable-next-line @typescript-eslint/no-require-imports
const { getApp, registerControllers } = require('@solidworx/platform/core');

export const app = getApp();

registerControllers(require.context(
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
