const Encore = require('@symfony/webpack-encore'),
    path = require('path'),
    { exec } = require('child_process');

Encore
    .setOutputPath('web/static/')
    .setPublicPath('/static')

    .addEntry('core', './assets/js/core.js')
    .addEntry('installation-config', './assets/js/pages/installation-config.js')
    .addEntry('installation-install', './assets/js/pages/installation-install.js')

    .addStyleEntry('app', './assets/less/app.less')
    .addStyleEntry('email', './assets/less/email.less')
    .addStyleEntry('pdf', './assets/less/pdf.less')

    .enableSingleRuntimeChunk()
    .splitEntryChunks()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .enableLessLoader()
    .autoProvidejQuery()

    .addAliases({
        '~': path.resolve(__dirname, 'assets/js'),
        'SolidInvoiceCore': path.resolve(__dirname, 'src/CoreBundle/Resources/public'),
        'SolidInvoiceDataGrid': path.resolve(__dirname, 'src/DataGridBundle/Resources/public'),
        'jos_js': path.resolve(__dirname, 'web/bundles/fosjsrouting/js'),
        'router': path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/routing'),
        'translator': path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/translator'),
    })

    .addPlugin(
        {
            apply: (compiler) => {
                compiler.hooks.beforeRun.tap('SetAssets', (compilation) => {
                    const output = (err, stdout, stderr) => {
                        if (stdout) {
                            process.stdout.write(stdout);
                        }

                        if (stderr) {
                            process.stderr.write(stderr);
                        }

                        if (err) {
                            process.stderr.write(err);
                        }
                    };

                    exec(path.resolve(__dirname, 'bin/console assets:install web'), output);
                    exec(path.resolve(__dirname, 'bin/console fos:js-routing:dump --format=json --target=assets/js/js_routes.json'), output);
                    exec(path.resolve(__dirname, 'bin/console bazinga:js-translation:dump assets/js --merge-domains --format=json'), output);
                });
            }
        }
    )
;

module.exports = Encore.getWebpackConfig();
