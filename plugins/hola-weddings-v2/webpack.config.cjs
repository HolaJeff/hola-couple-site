const path                = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = ( _env, argv ) => {
	const isDev = argv.mode !== 'production';

	return {
		mode: argv.mode ?? 'development',
		entry: {
			index: path.resolve( __dirname, 'assets/booking-form/index.jsx' ),
		},
		output: {
			path:     path.resolve( __dirname, 'assets/dist/booking-form' ),
			filename: '[name].js',
		},
		devtool: isDev ? 'source-map' : false,
		module: {
			rules: [
				{
					test:    /\.[jt]sx?$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[ '@babel/preset-env', { targets: '> 0.25%, not dead' } ],
								[ '@babel/preset-react', {
									runtime:    'classic',
									pragma:     'wp.element.createElement',
									pragmaFrag: 'wp.element.Fragment',
								} ],
							],
						},
					},
				},
				{
					test: /\.scss$/,
					use:  [
						isDev ? 'style-loader' : MiniCssExtractPlugin.loader,
						'css-loader',
						'sass-loader',
					],
				},
			],
		},
		plugins: [
			...( isDev ? [] : [ new MiniCssExtractPlugin( { filename: 'booking-form.css' } ) ] ),
		],
		resolve: {
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
		// Map @wordpress/* imports to globals provided by WordPress core.
		// This keeps React & WP packages out of the bundle entirely.
		//
		// 'react' must be mapped too: third-party components (lucide-react and
		// anything else importing it directly) would otherwise get a second,
		// bundled copy of React. Their hooks would then run against a dispatcher
		// that is null while wp.element renders the tree, which throws and takes
		// the whole app down rather than just the component.
		externals: {
			'@wordpress/element':   [ 'wp', 'element' ],
			'@wordpress/api-fetch': [ 'wp', 'apiFetch' ],
			'@wordpress/i18n':      [ 'wp', 'i18n' ],
			react:                  'React',
			'react-dom':            'ReactDOM',
			'react/jsx-runtime':    'ReactJSXRuntime',
		},
		devServer: {
			port:         8887,
			hot:          false,      // HMR disabled — cross-origin WebSocket causes uncaught error
			liveReload:   true,       // Full-page reload on file change instead
			host:         '0.0.0.0',
			allowedHosts: 'all',
			headers:      { 'Access-Control-Allow-Origin': '*' },
			client: {
				webSocketURL: 'ws://localhost:8887/ws',
				overlay:      false,  // Don't inject the error overlay into the WP page
			},
		},
	};
};
