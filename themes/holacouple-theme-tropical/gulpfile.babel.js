const gulp = require('gulp'); // Gulp of-course.
const sass = require('gulp-sass')(require('sass')) // Gulp plugin for Sass compilation.
const minifycss = require('gulp-uglifycss'); // Minifies CSS files.
const rename = require('gulp-rename'); 

const concat = require('gulp-concat'); // Concatenates JS files.
const babel = require('gulp-babel'); // Compiles ESNext to browser compatible JS.
const webpack = require('webpack-stream');
const lineec = require('gulp-line-ending-corrector'); // Consistent Line Endings for non UNIX systems. Gulp Plugin for Line Ending Corrector (A utility that makes sure your files have consistent line endings).
const notify = require('gulp-notify'); // Sends message notification to you.
const browserSync = require('browser-sync').create(); // Reloads browser and injects CSS. Time-saving synchronized browser testing.
const remember = require('gulp-remember'); //  Adds all the files it has ever seen back into the stream.
const plumber = require('gulp-plumber'); // Prevent pipe breaking caused by errors from gulp plugins.
const beep = require('beepbeep');

/**
 * Task: `browser-sync`.
 *
 * Live Reloads, CSS injections, Localhost tunneling.
 * @link http://www.browsersync.io/docs/options/
 *
 * @param {Mixed} done Done.
 */
const browsersync = done => {
	browserSync.init({
		proxy: config.projectURL,
		open: config.browserAutoOpen,
		injectChanges: config.injectChanges,
		watchEvents: ['change', 'add', 'unlink', 'addDir', 'unlinkDir']
	});
	done();
};

// Helper function to allow browser reload with Gulp 4.
const reload = done => {
	browserSync.reload();
	done();
};

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

gulp.task('js', () => {
	return gulp
		.src('assets/src/js/index.js') // Only run on changed files.
		.pipe(plumber(errorHandler))
		.pipe(webpack({mode:'development'}))
		.pipe(plumber(errorHandler))
		.pipe(
			babel({
				presets: [
					[
						'@babel/preset-env', // Preset to compile your modern JS to ES5.
                        {
							targets: {browsers: ['last 2 version', '> 1%']} // Target browser list to support.
						}
					]
				]
			})
		)
		.pipe(remember('./assets/src/js/index.js')) // Bring all files back to stream.
		.pipe(concat('index.js'))
		.pipe(lineec()) // Consistent Line Endings for non UNIX systems.
		.pipe(gulp.dest('./assets/dist/js/'))
		.pipe(
			notify({
				message: '\n\n✅  ===> VENDOR JS — completed!\n',
				onLast: true
			})
		);
});

gulp.task(
	'default',
	gulp.parallel(['css', 'js'], () => {
		
		gulp.watch('./assets/src/style/**/*.scss', gulp.parallel('css'));
		gulp.watch('./assets/src/js/**/*.js', gulp.series('js', reload)); // Reload on vendorsJS file changes.
	})
);