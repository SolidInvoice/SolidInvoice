var Encore = require('@symfony/webpack-encore'),
    glob = require("glob")
    path = require("path");

Encore
    // directory where all compiled assets will be stored
    .setOutputPath('web/assets')

    // what's the public path to this directory (relative to your project's document root dir)
    .setPublicPath('/assets')

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // will output as web/build/app.js
    .addEntry('js/grid', glob.sync('./web/bundles/csbilldatagrid/js/cell/*.js'))
    .addEntry('js/app', './web/bundles/csbillcore/js/app.js')
    .addEntry('js/client/view', './web/bundles/csbillclient/js/view.js')
    //.addEntry('hbs-templates', glob.sync('./web/bundles/csbill*/templates/**/*.hbs'))

    // add styles
    .addStyleEntry('css/app', glob.sync('./web/bundles/csbill*/less/*.less', {'ignore': ['./web/bundles/csbill*/less/email.less']}))
    .addStyleEntry('css/email', glob.sync('./web/bundles/csbill*/less/email.less'))

    // allow less files to be processedmeansPayNo
    .enableLessLoader()

    // allows legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

// export the final configuration
var webpackConfig = Encore.getWebpackConfig();
//console.log(webpackConfig);

webpackConfig.resolve = {
    alias: {
        core: path.resolve(__dirname, './web/bundles/csbillcore/js/'),
        marionette: 'backbone.marionette',
        material: 'bootstrap-material-design',
        'bootstrap.bootbox': 'bootbox',
        'handlebars.runtime': 'handlebars-runtime',
        'bootstrap.modal': 'bootstrap-modal',
        'bootstrap.modalmanager': path.resolve(__dirname, './web/bundles/csbillcore/js/lib/bootstrap/modalmanager.js'),
        'routing': path.resolve(__dirname, './web/bundles/fosjsrouting/js/router.js'),
        'translator': path.resolve(__dirname, './web/bundles/bazingajstranslation/js/translator.min.js'),
    }
};

//webpackConfig.module.loaders = [{ test: /\.hbs/, loader: "handlebars-loader" }];
webpackConfig.module.rules.push({ test: /\.hbs$/, loader: "handlebars-loader?helperDirs[]=" + path.resolve(__dirname, "./web/bundles/csbillcore/js/extend/handlebars/helpers/") });

module.exports = webpackConfig;

