import './scss/app.scss';
import './webmcp';

import { getApp, registerControllers } from '@solidworx/platform';

import PasswordStrength from './controllers/password-strength-controller';

// Reuse the Stimulus application the platform's `_platform_ui` entry bootstraps,
// rather than starting a second one that would register every controller twice.
export const app = getApp();

registerControllers(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('password-strength', PasswordStrength);

export default app
