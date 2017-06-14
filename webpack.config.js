let Encore = require('@symfony/webpack-encore'),
    glob = require("glob"),
    path = require("path"),
    _ = require('lodash'),
    webpack = require('webpack');

let entries = {
    'js/grid': './web/bundles/csbilldatagrid/js/grid.js',
    'js/multiple_grid': './web/bundles/csbilldatagrid/js/multiple_grid.js',
    'js/app': './web/bundles/csbillcore/js/app.js',
    'js/client/view': './web/bundles/csbillclient/js/view.js',
    'js/client/index': './web/bundles/csbillclient/js/index.js',
};


_.each(entries, function (src, name) {
    Encore.addEntry(name, src);
});

Encore
    // directory where all compiled assets will be stored
    .setOutputPath('web/assets')

    // what's the public path to this directory (relative to your project's document root dir)
    .setPublicPath('/assets')

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // will output as web/build/app.js
    /*.addEntry('js/grid', './web/bundles/csbilldatagrid/js/grid.js')
    .addEntry('js/multiple_grid', './web/bundles/csbilldatagrid/js/multiple_grid.js')
    .addEntry('js/app', './web/bundles/csbillcore/js/app.js')
    .addEntry('js/client/view', './web/bundles/csbillclient/js/view.js')*/
    //.addEntry(...entries)
    //.addEntry('hbs-templates', glob.sync('./web/bundles/csbill*/templates/**/*.hbs'))

    // add styles
    .addStyleEntry('css/app', glob.sync('./web/bundles/csbill*/less/*.less', {'ignore': ['./web/bundles/csbill*/less/email.less']}))
    .addStyleEntry('css/email', glob.sync('./web/bundles/csbill*/less/email.less'))

    // allow less files to be processedmeansPayNo
    .enableLessLoader()

    // allows legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    .createSharedEntry('vendor', ['jquery', 'marionette', 'backbone'])

    /*.addRule(
        Encore.Rule()
            .setTest(/\.hbs$/)
            .addLoader('handlebars-loader', {'helperDirs' : [path.resolve(__dirname, "./web/bundles/csbillcore/js/extend/handlebars/helpers/")]})
    )*/

    //.addLoader('\/.hbs$/', {'loader' : 'handlebars-loader', 'options': {'helperDirs' : [path.resolve(__dirname, "./web/bundles/csbillcore/js/extend/handlebars/helpers/")]}})

    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

// export the final configuration
let webpackConfig = Encore.getWebpackConfig();
//console.log(webpackConfig);



webpackConfig.resolve = {
    alias: {
        core: path.resolve(__dirname, './web/bundles/csbillcore/js/'),
        client: path.resolve(__dirname, './web/bundles/csbillclient/js/'),
        grid: path.resolve(__dirname, './web/bundles/csbilldatagrid/js/'),

        marionette: 'backbone.marionette',
        material: 'bootstrap-material-design',
        'bootstrap.bootbox': 'bootbox',
        'handlebars.runtime': 'handlebars-runtime',
        'bootstrap.modal': path.resolve(__dirname, './web/bundles/csbillcore/js/lib/bootstrap/modal.js'),
        'bootstrap.modalmanager': path.resolve(__dirname, './web/bundles/csbillcore/js/lib/bootstrap/modalmanager.js'),
        'routing': path.resolve(__dirname, './web/bundles/csbillcore/js/extend/routing.js'),
        'translator': path.resolve(__dirname, './web/bundles/bazingajstranslation/js/translator.min.js'),
    }
};

//webpackConfig.module.loaders = [{ test: /\.hbs/, loader: "handlebars-loader" }];
webpackConfig.module.rules.push({ test: /\.hbs$/, loader: "handlebars-loader?helperDirs[]=" + path.resolve(__dirname, "./web/bundles/csbillcore/js/extend/handlebars/helpers/") });


module.exports = webpackConfig;

