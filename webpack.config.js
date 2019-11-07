const Encore = require('@symfony/webpack-encore'),
      path = require('path');

Encore
    // directory where compiled assets will be stored
    .setOutputPath('web/static/')
    // public path used by the web server to access the output path
    .setPublicPath('/static')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addStyleEntry('app', './assets/less/app.less')
    .addStyleEntry('email', './assets/less/email.less')
    .addStyleEntry('pdf', './assets/less/pdf.less')
    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .enableLessLoader()

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    .addAliases({
        'SolidInvoiceCore': path.resolve(__dirname, 'src/CoreBundle/Resources/public'),
        'SolidInvoiceDataGrid': path.resolve(__dirname, 'src/DataGridBundle/Resources/public'),
    })
;

module.exports = Encore.getWebpackConfig();
