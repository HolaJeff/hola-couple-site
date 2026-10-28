const path                = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = ( _env, argv ) => {
	const isDev = argv.mode !== 'production';

	return {
		mode: argv.mode ?? 'development',
		entry: {
			index: path.resolve( __dirname, 'assets/feedback/index.jsx' ),
		},
		output: {
			path:     path.resolve( __dirname, 'assets/dist/feedback' ),
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
			...( isDev ? [] : [ new MiniCssExtractPlugin( { filename: 'feedback.css' } ) ] ),
		],
		resolve: {
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
		// See webpack.config.cjs for why 'react' is mapped as well: a bundled
		// second copy of React breaks hooks in any third-party component.
		externals: {
			'@wordpress/element':   [ 'wp', 'element' ],
			'@wordpress/api-fetch': [ 'wp', 'apiFetch' ],
			'@wordpress/i18n':      [ 'wp', 'i18n' ],
			react:                  'React',
			'react-dom':            'ReactDOM',
			'react/jsx-runtime':    'ReactJSXRuntime',
		},
		devServer: {
			port:         8888,
			hot:          false,
			liveReload:   true,
			host:         '0.0.0.0',
			allowedHosts: 'all',
			headers:      { 'Access-Control-Allow-Origin': '*' },
			client: {
				webSocketURL: 'ws://localhost:8888/ws',
				overlay:      false,
			},
		},
	};
};
