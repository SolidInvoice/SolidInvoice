var gulp = require('gulp'),

    $ = require('gulp-load-plugins')({
        pattern: ['gulp-*', 'main-bower-files', 'del']
    }),

    glob = require("glob"),

    options = {
        less: 'web/bundles/csbill*/less',
        css: 'web/bundles/csbill*/css',
        images: 'web/bundles/csbill*/img/*',
        js: 'web/bundles/**/js/**/*.js',
        templates: 'web/bundles/**/templates/**/*.hbs'
    };

gulp.task('fonts', ['clean:fonts'], function() {
    return gulp.src('web/bundles/**/fonts/*')
        .pipe($.filter('**/*.{eot,svg,ttf,woff,woff2}'))
        .pipe($.flatten())
        .pipe(gulp.dest('web/fonts/'));
});

gulp.task('images', ['clean:images'], function() {
    return gulp.src(options.images)
        .pipe($.filter('**/*.{png,gif,jpg,jpeg}'))
        .pipe($.flatten())
        .pipe(gulp.dest('web/img/'));
});

gulp.task('clean', ['clean:css', 'clean:js', 'clean:fonts', 'clean:images']);

gulp.task('clean:css', function() {
    $.del(['web/css/**', '!web/css', '!web/css/.gitkeep']);
});

gulp.task('clean:js', function() {
    $.del(['web/js/**', '!web/js', '!web/js/.gitkeep']);
});

gulp.task('clean:fonts', function() {
    $.del(['web/fonts/**', '!web/fonts', '!web/fonts/.gitkeep']);
});

gulp.task('clean:images', function() {
    $.del(['web/img/**', '!web/img', '!web/img/.gitkeep']);
});

gulp.task('css:app', function() {
    var lessOptions = {
        'paths': glob.sync(options.less)
    };

    var files = [
        'web/bundles/csbillcore/less/bootstrap/bootstrap.less',
        'web/bundles/csbillcore/less/material/material.less',
        'web/bundles/csbillcore/less/font-awesome/font-awesome.less',
        options.less + '/*.less',
        options.css + '/*.css',
        '!' + options.less + '/email.less'
    ];

    return gulp.src(files)
        .pipe($.filter('**/*.{css,less}'))
        .pipe($.flatten())
        .pipe($.less(lessOptions))
        .pipe($.cssmin())
        .pipe($.concat('app.css'))
        .pipe(gulp.dest('web/css/'))
        ;
});

gulp.task('css:email', function() {
    var lessOptions = {
        'paths': glob.sync(options.less)
    };

    var files = [options.less + '/email.less'];

    return gulp.src(files)
        .pipe($.filter('**/*.{css,less}'))
        .pipe($.flatten())
        .pipe($.less(lessOptions))
        .pipe($.cssmin())
        .pipe($.concat('email.css'))
        .pipe(gulp.dest('web/css/'))
        ;
});

gulp.task('css', ['clean:css'], function() {
    gulp.start('css:app');
    gulp.start('css:email');
});

gulp.task('templates', ['clean:js'], function() {
    return gulp.src(options.templates)
        .pipe($.handlebars({
            handlebars: require('handlebars')
        }))
        .pipe($.wrap('template(<%= contents %>)'))
        .pipe($.declare({
            root: 'templates',
            noRedeclare: true,
            processName: function(filePath) {
                // Allow nesting based on path using gulp-declare's processNameByPath()
                return $.declare.processNameByPath(filePath.replace('web/bundles/csbill', '').replace('templates/', ''));
            }
        }))
        .pipe($.concat('hbs-templates.js'))
        .pipe($.wrap('define([\'handlebars.runtime\'], function(Handlebars) {\nHandlebars = Handlebars["default"];  var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};\n<%= contents %> return templates; });'))
        .pipe(gulp.dest('web/js'));
});

gulp.task('watch', ['css', 'templates'], function() {
    gulp.watch([options.less + '/*', options.css + '/*'], ['css']);
    gulp.watch([options.templates], ['templates']);
});

gulp.task('build', ['css', 'fonts', 'images', 'templates']);

gulp.task('default', ['clean'], function() {
    gulp.start('build');
});