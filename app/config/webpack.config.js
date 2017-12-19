'use strict';

const webpack = require('webpack');
const path = require('path');
const fs = require('fs');
const autoprefixer = require('autoprefixer');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const AssetsPlugin = require('assets-webpack-plugin');
const ExtractFilePlugin = require('extract-file-loader/Plugin');
const DashboardPlugin = require('webpack-dashboard/plugin');

module.exports = function makeWebpackConfig(options) {
    /**
     * Whether we are generating minified assets for production
     */
    const BUILD = options.environment === 'prod';

    /**
     * Whether we are running in dev-server mode (versus simple compile)
     */
    const DEV_SERVER = process.env.WEBPACK_MODE === 'watch';

    /**
     * Whether we are running inside webpack-dashboard
     */
    const DASHBOARD = process.env.WEBPACK_DASHBOARD === 'enabled';

    let publicPath;
    if (options.parameters.dev_server_public_path && DEV_SERVER) {
        publicPath = options.parameters.dev_server_public_path;

    } else if (options.parameters.public_path) {
        publicPath = options.parameters.public_path;
    } else {
        publicPath = DEV_SERVER ? 'http://localhost:8080/compiled/' : '/compiled/';
    }

    let outputPath;
    if (options.parameters.path) {
        outputPath = options.parameters.path;
    } else {
        const findPublicDirectory = function(currentDirectory, fallback) {
            var parentDirectory = path.dirname(currentDirectory);
            if (parentDirectory === currentDirectory) {
                return fallback;
            }

            var publicDirectory = parentDirectory + '/public';
            if (fs.existsSync(publicDirectory)) {
                return publicDirectory;
            }

            var webDirectory = parentDirectory + '/web';
            if (fs.existsSync(webDirectory)) {
                return webDirectory;
            }

            return findPublicDirectory(parentDirectory, fallback);
        };
        outputPath = findPublicDirectory(__dirname, __dirname + '../../web') + '/compiled/';
    }

    options.alias['vue$'] = 'vue/dist/vue.esm.js';

    /**
     * Config
     * Reference: https://webpack.js.org/concepts/
     * This is the object where all configuration gets set
     */
    var config = {
        entry: options.entry,
        resolve: {
            alias: options.alias,
            extensions: ['.js', '.jsx'],
            modules: ['node_modules']
        },

        output: {
            // Absolute output directory
            path: outputPath,

            // Output path from the view of the page
            publicPath: publicPath,

            // Filename for entry points
            // Only adds hash in build mode
            filename: BUILD ? '[name].[chunkhash].js' : '[name].bundle.js',

            // Filename for non-entry points
            // Only adds hash in build mode
            chunkFilename: BUILD ? '[name].[chunkhash].js' : '[name].bundle.js'
        },

        /**
         * Options for webpack-dev-server.
         * Enables overlay inside the page if any error occurs when compiling.
         * Enables CORS headers to allow hot reload from other domain / port.
         * Reference: https://webpack.js.org/configuration/dev-server/
         */
        devServer: Object.assign({
            overlay: {
                warnings: false,
                errors: true
            },
            disableHostCheck: true,
            headers: { "Access-Control-Allow-Origin": "*" }
        }, options.parameters.dev_server || {})
    };


    /**
     * Loaders
     * Reference: https://webpack.js.org/concepts/loaders/
     * List: https://webpack.js.org/loaders/
     * This handles most of the magic responsible for converting modules
     */
    config.module = {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    loaders: {
                        // Since sass-loader (weirdly) has SCSS as its default parse mode, we map
                        // the "scss" and "sass" values for the lang attribute to the right configs here.
                        // other preprocessors should work out of the box, no loader config like this necessary.
                        'scss': 'vue-style-loader!css-loader!sass-loader',
                        'sass': 'vue-style-loader!css-loader!sass-loader?indentedSyntax'
                    }
                }
            },
            /**
             * Compiles ES6 and ES7 into ES5 code
             * Reference: https://github.com/babel/babel-loader
             */
            {
                test: /\.jsx?$/i,
                loader: 'babel-loader',
                exclude: /node_modules/,
                options: {
                    presets: [['env', {
                        "modules": false
                    }]]
                }
            },

            /**
             * Minify PNG, JPEG, GIF and SVG images with imagemin
             * Reference: https://github.com/tcoopman/image-webpack-loader
             *
             * See `config.imageWebpackLoader` for configuration options
             *
             * Query string is needed for URLs inside css files, like bootstrap
             */
            {
                test: /\.(gif|png|jpe?g|svg)(\?.*)?$/i,
                enforce: 'pre',
                loader: 'image-webpack-loader',
                options: options.parameters.image_loader_options || {
                    optipng: {
                        optimizationLevel: 7,
                        progressive: true
                    }
                }
            },
            /**
             * Copy files to output directory
             * Rename the file using the asset hash
             * Pass along the updated reference to your code
             *
             * Reference: https://github.com/webpack/file-loader
             *
             * Query string is needed for URLs inside css files, like bootstrap
             * Overwrites name parameter to put original name in the destination filename, too
             */
            {
                test: /\.(png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)(\?.*)?$/i,
                loader: 'file-loader',
                options: {
                    name: '[name].[hash].[ext]'
                }
            },

            /**
             * Loads HTML files as strings inside JavaScript - can be used for templates
             *
             * Reference: https://github.com/webpack/raw-loader
             */
            {
                test: /\.html$/i,
                loader: 'raw-loader'
            },

            /**
             * Allow loading CSS through JS
             * Reference: https://github.com/webpack/css-loader
             *
             * postcss: Postprocess your CSS with PostCSS plugins (add vendor prefixes to CSS)
             * Reference: https://github.com/postcss/postcss-loader
             * Reference: https://github.com/postcss/autoprefixer
             *
             * ExtractTextPlugin: Extract CSS files into separate ones to load directly
             * Reference: https://github.com/webpack/extract-text-webpack-plugin
             *
             * If ExtractTextPlugin is disabled, use style loader
             * Reference: https://github.com/webpack/style-loader
             */
            {
                test: /\.(css|less|scss)$/i,
                loader: ExtractTextPlugin.extract({
                    'fallback': 'style-loader',
                    use: [
                        'css-loader?sourceMap&url=false',
                        {
                            loader: 'postcss-loader',
                            options: {
                                plugins: function () {
                                    return [
                                        autoprefixer({
                                            browsers: ['last 2 version']
                                        })
                                    ];
                                }
                            }
                        }
                    ]
                })
            },

            /**
             * Compile LESS to CSS, then use same rules
             * Reference: https://github.com/webpack-contrib/less-loader
             */
            {
                test: /\.less$/i,
                loader: 'less-loader?sourceMap',
                enforce: 'pre'
            },

            /**
             * Compile SASS to CSS, then use same rules
             * Reference: https://github.com/webpack-contrib/sass-loader
             */
            {
                test: /\.scss$/i,
                loader: 'sass-loader?sourceMap',
                enforce: 'pre'
            },

            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['env'],
                        plugins: [
                            "transform-class-properties",
                            "transform-object-rest-spread",
                            "syntax-dynamic-import"
                        ]
                    }
                }
            }
        ]
    };

    /**
     * Plugins
     * Reference: https://webpack.js.org/configuration/plugins/
     * List: https://webpack.js.org/plugins/
     */
    config.plugins = [
        /**
         * Used for CSS files to extract from JavaScript
         * Reference: https://github.com/webpack/extract-text-webpack-plugin
         */
        new ExtractTextPlugin(
            {
                filename: BUILD ? '[name].[hash].css' : '[name].bundle.css',
                disable: options.parameters.extract_css === false
            }
        ),

        /**
         * Webpack plugin that emits a json file with assets paths - used by the bundle
         * Reference: https://github.com/kossnocorp/assets-webpack-plugin
         */
        new AssetsPlugin({
            filename: path.basename(options.manifest_path),
            path: path.dirname(options.manifest_path)
        }),

        /**
         * Adds assets loaded with extract-file-loader as chunk files to be available in generated manifest
         * Used by the bundle to use binary files (like images) as entry-points
         * Reference: https://github.com/mariusbalcytis/extract-file-loader
         */
        new ExtractFilePlugin(),

        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery"
        })
    ];

    /**
     * Adds CLI dashboard when compiling assets instead of the standard output
     * Reference: https://github.com/FormidableLabs/webpack-dashboard
     */
    if (DASHBOARD) {
        config.plugins.push(new DashboardPlugin());
    }

    /**
     * Build specific plugins - used only in production environment
     */
    if (BUILD) {
        config.plugins.push(
            /**
             * Only emit files when there are no errors
             * Reference: https://github.com/webpack/docs/wiki/list-of-plugins#noerrorsplugin
             */
            new webpack.NoEmitOnErrorsPlugin(),

            /**
             * Minify all javascript, switch loaders to minimizing mode
             * Reference: https://webpack.js.org/plugins/uglifyjs-webpack-plugin/
             */
            new webpack.optimize.UglifyJsPlugin()
        );
    }

    /**
     * Devtool - type of sourcemap to use per build type
     * Reference: https://webpack.js.org/configuration/devtool/
     */
    if (BUILD) {
        config.devtool = 'source-map';
    } else {
        config.devtool = 'eval';
    }

    return config;
};
