const gulp = require('gulp');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const { src } = require('vinyl-fs');

const paths = {
    adminjs: {
        src: 'bpmpesagateway/admin/js/src/*.js',
        dest: 'bpmpesagateway/admin/js/dist/'
    },
    publicjs : {
        src: 'bpmpesagateway/public/js/src/*.js',
        dest: 'bpmpesagateway/public/js/dist/'
    }
};

function minifyAdminJS() {
    return src(paths.adminjs.src)
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.adminjs.dest));
}

function minifyPublicJS() {
    return src(paths.publicjs.src)
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.publicjs.dest));
}

// Combined JS task
const minifyJs = gulp.parallel(minifyAdminJS, minifyPublicJS);

// Watch for changes
function watchJs() {
  gulp.watch(paths.adminjs.src, minifyAdminJS);
  gulp.watch(paths.publicjs.src, minifyPublicJS);
}


// Default task
exports.default = minifyJs;
exports.js = minifyJs;
exports.watch = gulp.series(minifyJs, watchJs);
exports.adminJs = minifyAdminJS;
exports.publicJs = minifyPublicJS;