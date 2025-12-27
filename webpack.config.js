import Encore from '@solidworx/platform/webpack.config.js';

import { codecovWebpackPlugin } from '@codecov/webpack-plugin';

export default Encore
    .addStyleEntry('login', './assets/scss/login.scss')
    .enableStimulusBridge('./assets/controllers.json')
    .addPlugin(codecovWebpackPlugin({
        enableBundleAnalysis: Encore.isProduction() && process.env.CODECOV_TOKEN !== undefined,
        bundleName: 'solidinvoice-webpack-bundle',
        uploadToken: process.env.CODECOV_TOKEN,
    }))
    .getWebpackConfig();
