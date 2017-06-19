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

    packageJson = require('./package.json'),

    del = require('del'),
    glob = require("glob"),

    lessNpmImportPlugin = require("less-plugin-npm-import"),

    options = {
        less: 'web/bundles/csbill*/less',
        css: 'web/bundles/csbill*/css',
        images: 'web/bundles/csbill*/img/*',
        js: 'web/bundles/**/js/**/*.js',
        templates: 'web/bundles/**/templates/**/*.hbs',
        prod: !!util.env.prod
    };

gulp.task('fonts', ['clean:fonts'], () => {
    return gulp.src(['web/bundles/**/fonts/*', 'node_modules/font-awesome/fonts/*'])
        .pipe(filter('**/*.{eot,svg,ttf,woff,woff2}'))
        .pipe(flatten())
        .pipe(gulp.dest('web/fonts/'));
});

gulp.task('images', ['clean:images'], () => {
    return gulp.src(options.images)
        .pipe(filter('**/*.{png,gif,jpg,jpeg}'))
        .pipe(filter('**/*.{png,gif,jpg,jpeg}'))
        .pipe(flatten())
        .pipe(gulp.dest('web/img/'));
});

gulp.task('clean', ['clean:css', 'clean:js', 'clean:fonts', 'clean:images']);

gulp.task('clean:css', () => {
    del(['web/css/**', '!web/css', '!web/css/.gitkeep']);
});

gulp.task('clean:js', () => {
    del(['web/js/**', '!web/js', '!web/js/.gitkeep']);
});

gulp.task('clean:fonts', () => {
    del(['web/fonts/**', '!web/fonts', '!web/fonts/.gitkeep']);
});

gulp.task('clean:images', () => {
    del(['web/img/**', '!web/img', '!web/img/.gitkeep']);
});

gulp.task('css:app', () => {
    const lessOptions = {
            'paths': glob.sync(options.less),
            'plugins': [new lessNpmImportPlugin({prefix: '~'})]
        },

        files = [
            options.less + '/*.less',
            options.css + '/*.css',
            '!' + options.less + '/email.less'
        ];

    return gulp.src(files)
        .pipe(filter('**/*.{css,less}'))
        //.pipe(flatten())
        .pipe(gIf(!options.prod, plumber(function (error) {
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
});

gulp.task('css:email', () => {
    const lessOptions = {
            'paths': glob.sync(options.less),
            'plugins': [new lessNpmImportPlugin({prefix: '~'})]
        },

        files = [options.less + '/email.less'];

    return gulp.src(files)
        .pipe(filter('**/*.{css,less}'))
        //.pipe(flatten())
        .pipe(gIf(!options.prod, plumber(function (error) {
            console.log(error.toString());
            this.emit('end');
        })))
        .pipe(less(lessOptions))
        .pipe(cssmin())
        .pipe(concat('email.css'))
        .pipe(gulp.dest('web/css/'))
        ;
});

gulp.task('css', ['clean:css'], () => {
    gulp.start('css:app');
    gulp.start('css:email');
});

gulp.task('templates', ['clean:js'], () => {
    return gulp.src(options.templates)
        .pipe(gIf(!options.prod, plumber(function (error) {
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
                return declare.processNameByPath(filePath.replace('web/bundles/csbill', '').replace('templates/', ''));
            }
        }))
        .pipe(concat('hbs-templates.js'))
        .pipe(wrap('define([\'handlebars.runtime\'], function(Handlebars) {\nHandlebars = Handlebars["default"]; var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};\n<%= contents %> return templates; });'))
        .pipe(gulp.dest('web/js'));
});

gulp.task('js:vendor', () => {
    del.sync('./web/assets');

    let libs = [];

    Object.keys(packageJson.dependencies).forEach(function (lib) {
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
        .pipe(gulp.dest('./web/assets'));
});

gulp.task('js:app', () => {
    return gulp
        .src(options.js)
        .pipe(gIf(!options.prod, sourcemap.init()))
        .pipe(babel())
        .pipe(gIf(options.prod, uglify()))
        .pipe(gIf(!options.prod, sourcemap.write()))
        .pipe(gulp.dest('./web/assets'));

});

gulp.task('js', ['js:vendor', 'js:app']);

gulp.task('watch', ['css', 'templates', 'js'], () => {
    gulp.watch([options.less + '/*', options.css + '/*'], ['css']);
    gulp.watch([options.templates], ['templates']);
    gulp.watch([options.js], ['js:app']);
});

gulp.task('build', ['css', 'fonts', 'images', 'templates']);

gulp.task('default', ['clean'], () => {
    gulp.start('build');
});