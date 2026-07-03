const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Two independent bundles: the admin SPA and the lightweight public form
// script. Keep them separate — the public bundle must never grow admin
// dependencies (see docs/ARCHITECTURE.md §4).
module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve( __dirname, 'client/admin/index.tsx' ),
		'public-form': path.resolve( __dirname, 'client/public-form/index.ts' ),
		'block-form': path.resolve( __dirname, 'client/blocks/form/index.tsx' ),
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve ? defaultConfig.resolve.alias : {} ),
			'@': path.resolve( __dirname, 'client/admin' ),
		},
	},
};
