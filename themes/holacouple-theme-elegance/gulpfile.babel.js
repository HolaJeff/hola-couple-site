const gulp = require('gulp'); // Gulp of-course.
const sass = require('gulp-sass')(require('sass')) // Gulp plugin for Sass compilation.
const minifycss = require('gulp-uglifycss'); // Minifies CSS files.
const rename = require('gulp-rename'); 

/**
 * Custom Error Handler.
 *
 * @param Mixed err
 */
const errorHandler = r => {
	notify.onError('\n\n❌  ===> ERROR: <%= error.message %>\n')(r);
	beep();

	// this.emit('end');
};

gulp.task('css', () => {
	return gulp
		.src('./assets/src/style/default.scss', {allowEmpty: true})
		.pipe(
			sass({
				errLogToConsole: true,
				outputStyle: 'expanded',
				precision: 10
			}).on('error', sass.logError)
		)
        .pipe(rename('theme.css'))
        .pipe(gulp.dest('./assets/dist/css/'))
		.pipe(rename({suffix: '.min'}))
		.pipe(minifycss())
		.pipe(gulp.dest('./assets/dist/css/'));
    }
);

gulp.task('js', () => { return gulp });

gulp.task(
	'default',
	gulp.parallel('css', () => {
		
		gulp.watch('./assets/src/style/**/*.scss', gulp.parallel('css'));
		//gulp.watch(config.watchJsVendor, gulp.series('vendorsJS', reload)); // Reload on vendorsJS file changes.
	})
);