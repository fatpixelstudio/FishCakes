"use strict";

var gulp = require('gulp');
var sass = require('gulp-sass');
var notify = require('gulp-notify');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var jshint = require('gulp-jshint');

gulp.task('styles', function () {
	return gulp.src('src/stylesheets/fishcakes.scss')
		.pipe(sass()) // Converts Sass to CSS with gulp-sass
		.pipe(rename({
			basename: 'fishcakes'
		}))
		.pipe(gulp.dest('dist'))
		.pipe(notify('FishCakes: styles done'));
});

gulp.task('scripts', function () {
	return gulp.src([
			// 'src/javascript/polyfills/addeventlistener.js',
			// 'src/js/utils/debounce.js',
			'src/javascript/utils/cookie.js',
			'src/javascript/utils/domready.js',
			'src/javascript/main.js'
		])
		.pipe(jshint())
		.pipe(jshint.reporter('jshint-stylish'))
		.pipe(jshint.reporter('fail'))
		.on('error', notify.onError({
			title: 'JSHint Error',
			message: '<%= error.message %>'
		}))
		.pipe(concat('fishcakes.js'))
		.pipe(gulp.dest('dist'))
		.pipe(notify('FishCakes: scripts done'));

});

gulp.task('watch', function () {
	return gulp.watch(['src/stylesheets/**/*.scss', 'src/javascript/**/*.js'], gulp.series('styles', 'scripts'));
	// Other watchers
});

gulp.task('default', gulp.series('styles', 'scripts', 'watch'));
