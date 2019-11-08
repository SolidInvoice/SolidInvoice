const Encore = require('@symfony/webpack-encore'),
    path = require('path'),
    { exec } = require('child_process');

Encore
    .configureRuntimeEnvironment()
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
    .addEntry('core', './assets/js/core.js')
    .addEntry('installation-config', './assets/js/pages/installation-config.js')
    .addEntry('installation-install', './assets/js/pages/installation-install.js')

    .addStyleEntry('app', './assets/less/app.less')
    .addStyleEntry('email', './assets/less/email.less')
    .addStyleEntry('pdf', './assets/less/pdf.less')

    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .splitEntryChunks()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .enableLessLoader()

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    .addAliases({
        '~': path.resolve(__dirname, 'assets/js'),
        'SolidInvoiceCore': path.resolve(__dirname, 'src/CoreBundle/Resources/public'),
        'SolidInvoiceDataGrid': path.resolve(__dirname, 'src/DataGridBundle/Resources/public'),
        'jos_js': path.resolve(__dirname, 'web/bundles/fosjsrouting/js'),
        'router': path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/routing'),
    })

    .addPlugin(
        {
            apply: (compiler) => {
                let hooks = ['entryOption', 'afterPlugins', 'afterResolvers', 'environment', 'afterEnvironment', 'beforeRun', 'run', 'watchRun', 'normalModuleFactory', 'contextModuleFactory', 'beforeCompile', 'compile', 'thisCompilation', 'compilation', 'make', 'afterCompile', 'shouldEmit', 'emit', 'afterEmit', 'assetEmitted', 'done', 'failed', 'invalid', 'watchClose', 'infrastructureLog', 'log'];
                for (i in hooks) {
                    var hook = hooks[i];
                    console.log(hook)
                    //compiler.hooks[hook].tap(hook, (compilation) => {console.log(hook);})
                }

                compiler.hooks.beforeRun.tap('DumpJsRoutes', (compilation) => {
                    const output = (err, stdout, stderr) => {

                        process.stdout.write(stdout);
                        process.stderr.write(stdout);
                        process.stderr.write(err);
                    };

                    exec(path.resolve(__dirname, 'bin/console assets:install web'), output);
                    exec(path.resolve(__dirname, 'bin/console fos:js-routing:dump --format=json --target=assets/js/js_routes.json'), output);
                });
            }
        }
    )
;

module.exports = Encore.getWebpackConfig();
