const Encore = require('@symfony/webpack-encore'),
    path = require('path'),
    { execSync } = require('child_process'),
    fs = require('fs'),
    { codecovWebpackPlugin } = require('@codecov/webpack-plugin')
;

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/static/')
    .setPublicPath('/static')

    .addEntry('core', './assets/core.js')

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

    .addAliases({
        '~': path.resolve(__dirname, 'assets/js'),
        'SolidInvoiceClient': path.resolve(__dirname, 'src/ClientBundle/Resources/public'),
        'SolidInvoiceCore': path.resolve(__dirname, 'src/CoreBundle/Resources/public'),
        'SolidInvoiceDataGrid': path.resolve(__dirname, 'src/DataGridBundle/Resources/public'),
        'SolidInvoiceInvoice': path.resolve(__dirname, 'src/InvoiceBundle/Resources/public'),
        'SolidInvoiceMailer': path.resolve(__dirname, 'src/MailerBundle/Resources/public'),
        'SolidInvoicePayment': path.resolve(__dirname, 'src/PaymentBundle/Resources/public'),
        'SolidInvoiceQuote': path.resolve(__dirname, 'src/QuoteBundle/Resources/public'),
        'SolidInvoiceTax': path.resolve(__dirname, 'src/TaxBundle/Resources/public'),
        'SolidInvoiceUser': path.resolve(__dirname, 'src/UserBundle/Resources/public'),
        'fos_js': path.resolve(__dirname, 'public/bundles/fosjsrouting/js'),
        'router': path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/routing'),
        'translator': path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/translator'),
    })

    .enableHandlebarsLoader((options) => {
        options.helperDirs = [
            path.resolve(__dirname, 'src/CoreBundle/Resources/public/js/extend/handlebars/helpers'),
        ];

        options.partialDirs = [
            path.resolve(__dirname, 'src/ClientBundle/Resources/public/templates/partials'),
        ];
    })

    .enableEslintPlugin((config) => {
        config.overrideConfig = {
            rules: {
                'lodash/import-scope': [2, 'member'],
                'no-else-return': 'error',
                'no-extra-bind': 'error',
                'no-lone-blocks': 'error',
                'no-loop-func': 'error',
                'no-useless-call': 'error',
                'no-useless-concat': 'error',
                'no-useless-return': 'error',
                'radix': 'error',
                'yoda': ['error', 'always'],
                'no-shadow': 'error',
                'no-use-before-define': 'error',
                'quotes': ['error', 'single']
            }
        };
    })

    .configureDevServerOptions(options => {
         options.server = {
             type: 'https',
             options: {
                 pfx: path.join(process.env.HOME, '.symfony5/certs/default.p12'),
             }
         };
     })

    .enableStimulusBridge('./assets/controllers.json')
    .enableTypeScriptLoader()

    .addPlugin(codecovWebpackPlugin({
        enableBundleAnalysis: process.env.CODECOV_TOKEN !== undefined,
        bundleName: 'solidinvoice-webpack-bundle',
        uploadToken: process.env.CODECOV_TOKEN,
    }))
;

const pagesDir = path.resolve(__dirname, 'assets/js/pages');

try {
    const files = fs.readdirSync(pagesDir);

    files.forEach(function(file) {
        if ('.js' === path.extname(file)) {
            Encore.addEntry(file.substr(0, file.length - 3), path.join(pagesDir, file));
        }
    });
} catch (err) {
    console.error('Could not list the directory.', err);
    process.exit(1);
}

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

execSync('bin/console assets:install public', output);
execSync('bin/console fos:js-routing:dump --format=json --target=assets/js/js_routes.json', output);
execSync('bin/console bazinga:js-translation:dump assets/js --merge-domains --format=json', output);

module.exports = Encore.getWebpackConfig();
