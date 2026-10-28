/**
 * Load Plugins.
 *
 * Load gulp plugins and passing them semantic names.
 */
const gulp = require('gulp'); // Gulp of-course.
const sass = require('gulp-sass')(require('sass')) // Gulp plugin for Sass compilation.
const minifycss = require('gulp-uglifycss'); // Minifies CSS files.
const rename = require('gulp-rename'); 

// JS related plugins.
const concat = require('gulp-concat'); // Concatenates JS files.
const babel = require('gulp-babel'); // Compiles ESNext to browser compatible JS.
const webpack = require('webpack-stream');
const terser = require('gulp-terser');

// Utility related plugins.
const lineec = require('gulp-line-ending-corrector'); // Consistent Line Endings for non UNIX systems. Gulp Plugin for Line Ending Corrector (A utility that makes sure your files have consistent line endings).
const notify = require('gulp-notify'); // Sends message notification to you.
const browserSync = require('browser-sync').create(); // Reloads browser and injects CSS. Time-saving synchronized browser testing.
const remember = require('gulp-remember'); //  Adds all the files it has ever seen back into the stream.
const plumber = require('gulp-plumber'); // Prevent pipe breaking caused by errors from gulp plugins.
const beep = require('beepbeep');

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


gulp.task('posJS', () => {
	return gulp
		.src('assets/src/js/Standalone/POS/index.js') // Only run on changed files.
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
		.pipe(remember('./assets/src/js/Standalone/POS/index.js')) // Bring all files back to stream.
		.pipe(concat('POS.js'))
		.pipe(lineec()) // Consistent Line Endings for non UNIX systems.
		.pipe(gulp.dest('./assets/dist/js/standalone/'))
		.pipe(
			notify({
				message: '\n\n✅  ===> VENDOR JS — completed!\n',
				onLast: true
			})
		);
});

/**
 * Build main js
 */
function bundle() {
  return gulp.src('./assets/src/js/app/index.js')
    .pipe(plumber((err) => { console.error(err); }))
    .pipe(webpack({
		mode: 'development',
		entry: './assets/src/js/app/index.js',
		output: {
			filename: 'app.js',
			iife: false,
		},
      	// No Babel: just resolve imports/exports
		module: { rules: [] },
		resolve: {
			extensions: ['.js'],
		},

		devtool: 'source-map',      // nice for dev; remove if you don’t want maps
		optimization: { minimize: false },
    }))
    .pipe(lineec())
    .pipe(gulp.dest('./assets/dist/js/'))
    .pipe(notify({ message: '✅ JS bundled (imports resolved)', onLast: true }));
};

// 2) minify app.js -> app.min.js (+ sourcemap)
function minify() {
  return gulp.src('./assets/dist/js/app.js', { allowEmpty: true })
    //.pipe(sourcemaps.init({ loadMaps: true }))     // picks up webpack’s map
    .pipe(terser({ format: { comments: false } }))
    .pipe(rename({ suffix: '.min' }))              // app.min.js
    //.pipe(sourcemaps.write('.'))                   // app.min.js.map
    .pipe(gulp.dest('./assets/dist/js/'));
}

/**
 * build front-end style
 */
gulp.task('css', () => {
	return gulp
		.src('./assets/src/style/index.scss', {allowEmpty: true})
		.pipe(
			sass({
				errLogToConsole: true,
				outputStyle: 'expanded',
				precision: 10,
				includePaths: ['node_modules'] 
			}).on('error', sass.logError)
		)
		.pipe(rename('main.css'))
		.pipe(gulp.dest('./assets/dist/css/'))
		.pipe(rename({suffix: '.min'}))
		.pipe(minifycss())
		.pipe(gulp.dest('./assets/dist/css/'));
	}
);

gulp.task('js', gulp.series(bundle));

/**
 * Watch POS.
 *
 * Watches for file changes and runs specific tasks.
 */
gulp.task(
	'build-pos',
    () => {
        return gulp.watch('./assets/src/js/**/*.js', gulp.series('posJS', reload))
    }
);

/**
 * watch frontend files
 */
gulp.task(
	'default',
	() => {
		gulp.watch( './assets/src/style/**/*.scss', gulp.series('css') );
		gulp.watch( './assets/src/js/app/**/*.js', gulp.series('js') );
	}
)