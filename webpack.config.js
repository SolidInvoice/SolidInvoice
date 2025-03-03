const Encore = require('@symfony/webpack-encore'),
    { codecovWebpackPlugin } = require('@codecov/webpack-plugin'),
    ESLintPlugin = require('eslint-webpack-plugin')
;

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/static/')
    // public path used by the web server to access the output path
    .setPublicPath('/static')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    .addEntry('core', './assets/core.ts')

    .addStyleEntry('app', './assets/scss/app.scss')
    .addStyleEntry('email', './assets/scss/email.scss')
    .addStyleEntry('pdf', './assets/scss/pdf.scss')

    .enableSingleRuntimeChunk()
    .splitEntryChunks()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .enableSassLoader()
    .autoProvidejQuery()

    .enableStimulusBridge('./assets/controllers.json')
    .enableTypeScriptLoader()

    .addPlugin(codecovWebpackPlugin({
        enableBundleAnalysis: Encore.isProduction() && process.env.CODECOV_TOKEN !== undefined,
        bundleName: 'solidinvoice-webpack-bundle',
        uploadToken: process.env.CODECOV_TOKEN,
    }))

    .addPlugin(new ESLintPlugin())

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
//.enableIntegrityHashes(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
