const gulp = require('gulp'),
    filter = require('gulp-filter'),
    flatten = require('gulp-flatten'),
    concat = require('gulp-concat'),
    less = require('gulp-less'),
    cssmin = require('gulp-cssmin'),
    wrap = require('gulp-wrap'),
    declare = require('gulp-declare'),
    handlebars = require('gulp-handlebars'),
    sourcemap = require('gulp-sourcemaps'),
    gIf = require('gulp-if'),
    plumber = require('gulp-plumber'),
    util = require('gulp-util'),
    babel = require("gulp-babel"),
    uglify = require('gulp-uglify'),
    rev = require('gulp-rev'),
    packageJson = require('./package.json'),
    del = require('del'),
    glob = require("glob"),
    vinylPaths = require('vinyl-paths'),
    lessNpmImportPlugin = require("less-plugin-npm-import"),
    options = {
        less: 'web/bundles/solidinvoice*/less',
        css: 'web/bundles/solidinvoice*/css',
        images: 'web/bundles/solidinvoice*/img/*',
        js: 'web/bundles/**/{lib,js}/**/*.js',
        templates: 'web/bundles/**/templates/**/*.hbs',
        prod: !!util.env.prod
    };

// Clean Tasks
function cleanFonts() {
    return del(['web/fonts/**', '!web/fonts', '!web/fonts/.gitkeep']);
}

function cleanImages() {
    return del(['web/img/**', '!web/img', '!web/img/.gitkeep']);
}

function cleanCss() {
    return del(['web/css/**', '!web/css', '!web/css/.gitkeep']);
}

function cleanJs() {
    return del(['web/js/**', '!web/js', '!web/js/.gitkeep']);
}

function cleanAssets() {
    return del('./web/assets');
}

exports.clean = gulp.parallel(cleanCss, cleanJs, cleanFonts, cleanImages, cleanAssets);

// Fonts Tasks
function fonts() {
    return gulp.src(['web/bundles/**/fonts/*', 'node_modules/font-awesome/fonts/*'])
        .pipe(filter('**/*.{eot,svg,ttf,woff,woff2}'))
        .pipe(flatten())
        .pipe(gulp.dest('web/fonts/'));
}

exports.fonts = gulp.series(cleanFonts, fonts);

// Images
function images() {
    return gulp.src(options.images)
        .pipe(filter('**/*.{png,gif,jpg,jpeg}'))
        .pipe(filter('**/*.{png,gif,jpg,jpeg}'))
        .pipe(flatten())
        .pipe(gulp.dest('web/img/'));
}

exports.images = gulp.series(cleanImages, images);

// CSS
function cssApp() {
    const lessOptions = {
            'paths': glob.sync(options.less),
            'plugins': [new lessNpmImportPlugin({prefix: '~'})]
        },

        files = [
            options.less + '/*.less',
            options.css + '/*.css',
            '!' + options.less + '/email.less',
            '!' + options.less + '/pdf.less'
        ];

    return gulp.src(files)
        .pipe(filter('**/*.{css,less}'))
        //.pipe(flatten())
        .pipe(gIf(!options.prod, plumber(function(error) {
            console.log(error.toString());
            this.emit('end');
        })))
        .pipe(gIf(!options.prod, sourcemap.init()))
        .pipe(less(lessOptions))
        .pipe(concat('app.css'))
        .pipe(gIf(!options.prod, sourcemap.write()))
        .pipe(gIf(options.prod, cssmin()))
        .pipe(gulp.dest('web/css/'))
        ;
}

function cssEmail() {
    const lessOptions = {
            'paths': glob.sync(options.less),
            'plugins': [new lessNpmImportPlugin({prefix: '~'})]
        },

        files = [options.less + '/email.less'];

    return gulp.src(files)
        .pipe(filter('**/*.{css,less}'))
        //.pipe(flatten())
        .pipe(gIf(!options.prod, plumber(function(error) {
            console.log(error.toString());
            this.emit('end');
        })))
        .pipe(less(lessOptions))
        .pipe(cssmin())
        .pipe(concat('email.css'))
        .pipe(gulp.dest('web/css/'))
        ;
}

function cssPdf() {
    const lessOptions = {
            'paths': glob.sync(options.less),
            'plugins': [new lessNpmImportPlugin({prefix: '~'})]
        },

        files = [options.less + '/pdf.less'];

    return gulp.src(files)
        .pipe(filter('**/*.{css,less}'))
        //.pipe(flatten())
        .pipe(gIf(!options.prod, plumber(function(error) {
            console.log(error.toString());
            this.emit('end');
        })))
        .pipe(less(lessOptions))
        .pipe(cssmin())
        .pipe(concat('pdf.css'))
        .pipe(gulp.dest('web/css/'))
        ;
}

exports.css = gulp.series(cleanCss, gulp.parallel(cssApp, cssEmail, cssPdf));

// Templates
function templates() {
    return gulp.src(options.templates)
        .pipe(gIf(!options.prod, plumber(function(error) {
            console.log(error.toString());
            this.emit('end');
        })))
        .pipe(handlebars({
            handlebars: require('handlebars')
        }))
        .pipe(wrap('template(<%= contents %>)'))
        .pipe(declare({
            root: 'templates',
            noRedeclare: true,
            processName: (filePath) => {
                // Allow nesting based on path using gulp-declare's processNameByPath()
                return declare.processNameByPath(filePath.replace('web/bundles/solidinvoice', '').replace('templates/', ''));
            }
        }))
        .pipe(concat('hbs-templates.js'))
        .pipe(wrap('define([\'handlebars.runtime\'], function(Handlebars) {\nHandlebars = Handlebars["default"]; var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};\n<%= contents %> return templates; });'))
        .pipe(gulp.dest('web/js'));
}

exports.templates = gulp.series(cleanJs, templates);

// JS
function jsVendor() {
    let libs = [];

    Object.keys(packageJson.dependencies).forEach(function(lib) {
        try {
            if ('bootstrap-material-design' === lib) {
                libs.push(require.resolve(lib + '/dist/js/material.js'));
                libs.push(require.resolve(lib + '/dist/js/ripples.js'));
                return;
            }

            if ('bootstrap' === lib) {
                lib += '/dist/js/bootstrap.js';
            }

            if ('bootstrap-material-datetimepicker' === lib) {
                lib += '/js/bootstrap-material-datetimepicker.js';
            }

            if ('handlebars' === lib) {
                lib += '/dist/handlebars.runtime.js';
            }

            libs.push(require.resolve(lib));
        } catch (e) {
            // noop
        }
    });

    return gulp
        .src(libs)
        .pipe(gIf(!options.prod, sourcemap.init()))
        .pipe(gIf(!options.prod, sourcemap.write()))
        .pipe(gulp.dest('web/assets'))
        ;
}

function jsApp() {
    return gulp
        .src(options.js)
        .pipe(gIf(!options.prod, sourcemap.init()))
        .pipe(babel())
        .pipe(gIf(options.prod, uglify()))
        .pipe(gIf(!options.prod, sourcemap.write()))
        .pipe(gulp.dest('./web/assets'));

}

exports.js = gulp.series(cleanAssets, jsVendor, jsApp);

function watch(done) {
    gulp.watch([options.less + '/*', options.css + '/*'], gulp.series(cleanCss, gulp.parallel(cssApp, cssEmail, cssPdf)));
    gulp.watch(options.templates, templates);
    gulp.watch(options.js, exports.js);
    done();
}

exports.watch = gulp.series(exports.css, templates, exports.js, watch);

exports.build = gulp.series(exports.css, exports.fonts, exports.images, templates, exports.js);

exports.default = gulp.series(exports.clean, gulp.parallel(cssApp, cssEmail, cssPdf), fonts, images, templates, jsVendor, jsApp);

exports.assets = () => {
    return gulp.src(['web/css/!*.css', 'web/js/!*.js', 'web/js/translations/!**'], {base: 'web'})
        .pipe(vinylPaths(del))
        .pipe(rev())
        .pipe(gulp.dest('web'))
        .pipe(rev.manifest('manifest.json'))
        .pipe(gulp.dest('web'))
        ;
};
