const gulp = require('gulp');
const { watch, src } = require('gulp');
const gulpSass = require('gulp-sass')(require('sass'));

function sass() {
    return src('./sass/*.scss')
        .pipe(gulpSass({ outputStyle: 'compressed' }).on('error', gulpSass.logError))
        .pipe(gulp.dest('./theme/static/css'));
}

exports.sass = sass;
exports.default = function () {
    watch('./sass/**/*.scss', { ignoreInitial: false }, sass);
};
