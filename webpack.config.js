/**
 * Webpack configuration for extrachill-admin-tools
 *
 * Extends @wordpress/scripts defaults for React admin app.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin-tools': './src/index.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve?.alias || {} ),
			'@extrachill/components': path.resolve( __dirname, 'src/components/shared' ),
		},
	},
};
