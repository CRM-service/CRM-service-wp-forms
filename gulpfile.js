/*
REQUIRED STUFF
==============
*/

var changed     = require('gulp-changed');
var gulp        = require('gulp');
var sass        = require('gulp-sass');
var browserSync = require('browser-sync');
var reload      = browserSync.reload;
var notify      = require('gulp-notify');
var prefix      = require('gulp-autoprefixer');
var minifycss   = require('gulp-minify-css');
var uglify      = require('gulp-uglify');
var cache       = require('gulp-cache');
var concat      = require('gulp-concat');
var util        = require('gulp-util');
var header      = require('gulp-header');
var pixrem      = require('gulp-pixrem');
var exec        = require('child_process').exec;

/*
ERROR HANDLING
==============
*/

var handleError = function(task) {
  return function(err) {

      notify.onError({
        message: task + ' failed, check the logs..',
        sound: false
      })(err);

    util.log(util.colors.bgRed(task + ' error:'), util.colors.red(err));
  };
};

/*

FILE PATHS
==========
*/

var src = 'assets/src';
var srcjs = src + '/js/*.js';
var assets = '/assets/admin';
var sassSrc = src + '/sass/*.{sass,scss}';
var jsfile = src + '/js/scripts.js';

/*

BROWSERSYNC
===========
*/

gulp.task('browsersync', function () {

    var files = [
      '**/*.php',
      srcjs
    ];

    browserSync.init(files, {
      proxy: "development.test/wp/wp-admin/",
      notify: true
    });

});


/*

SASS
====
*/

gulp.task('sass', function() {

  gulp.src(src + '/sass/main.scss')

  .pipe(sass({
    compass: false,
    bundleExec: true,
    sourcemap: false,
    style: 'compressed',
    debugInfo: true,
    lineNumbers: true,
    errLogToConsole: true,
    includePaths: [
      'bower_components/',
      'vendor/bower_components/',
      'node_modules/',
      // require('node-bourbon').includePaths
    ]
  }))

  .on('error', handleError('sass'))
  .pipe(prefix('last 3 version', 'safari 5', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
  .pipe(minifycss({keepBreaks: false, keepSpecialComments: 0,}))
  .pipe(pixrem())
  .pipe(gulp.dest('./assets/admin/'))
  .pipe(browserSync.reload({ stream:true }));

});

/*

SCRIPTS
=======
*/

var currentDate   = util.date(new Date(), 'dd-mm-yyyy HH:ss');
var pkg       = require('./package.json');
var banner      = '/*! <%= pkg.name %> <%= currentDate %> - <%= pkg.author %> */\n';

gulp.task('js', function() {

      // Normal scripts
      gulp.src(
        [
          'node_modules/tippy.js/dist/tippy.all.js',
          src + '/js/metabox.js'
        ])
        .pipe(concat('metabox.js'))
        .pipe(uglify({preserveComments: false, compress: true, mangle: false}).on('error',function(e){console.log('\x07',e.message);return this.end();}))
        .pipe(header(banner, {pkg: pkg, currentDate: currentDate}))
        .pipe(gulp.dest('./assets/admin/'));
});

/*
WATCH
=====
*/

gulp.task('js-watch', ['js'], browserSync.reload);
gulp.task('css-watch', ['sass'], browserSync.stream);
gulp.task('watch', ['browsersync'], function() {

  gulp.watch(sassSrc, ['css-watch']);
  gulp.watch(srcjs, ['js-watch']);

});
