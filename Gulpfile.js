var gulp = require('gulp'),

    $ = require('gulp-load-plugins')({
	pattern: ['gulp-*', 'main-bower-files', 'del']
    }),

    glob = require("glob"),

    options = {
	less: 'web/bundles/csbill*/less',
	css: 'web/bundles/csbill*/css',
	images: 'web/bundles/csbill*/img/*'
    };

gulp.task('fonts', function() {
    return gulp.src('web/bundles/**/fonts/*')
	.pipe($.filter('**/*.{eot,svg,ttf,woff,woff2}'))
	.pipe($.flatten())
	.pipe(gulp.dest('web/fonts/'));
});

gulp.task('images', function() {
    return gulp.src(options.images)
	.pipe($.filter('**/*.{png,gif,jpg,jpeg}'))
	.pipe($.flatten())
	.pipe(gulp.dest('web/img/'));
});

gulp.task('clean', function() {
    $.del(['web/css/**', '!web/css', '!web/css/.gitkeep']);
    $.del(['web/fonts/**', '!web/fonts', '!web/fonts/.gitkeep']);
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

gulp.task('css', ['css:app', 'css:email']);

gulp.task('watch', ['css'], function() {
    gulp.watch([options.less + '/*', options.css + '/*'], ['css']);
});

gulp.task('default', ['clean'], function() {
    gulp.start('css');
    gulp.start('fonts');
    gulp.start('images');
});