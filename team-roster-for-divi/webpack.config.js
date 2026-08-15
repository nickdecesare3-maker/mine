/**
 * Custom webpack config for Team Roster for Divi.
 *
 * Extends the @wordpress/scripts default config (which already wires up
 * React/JSX, the WordPress externals mapping so @wordpress/* imports
 * resolve to the wp.* globals WordPress already loads, and production
 * minification) but points it at our two block entry points under
 * `modules/<block>/src/index.js` and outputs each block's bundle back
 * into `modules/<block>/build/`, matching the paths referenced by each
 * block's block.json ("editorScript": "file:./build/index.js").
 *
 * @package TeamRosterForDivi
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'team-member-card/build/index': path.resolve( __dirname, 'modules/team-member-card/src/index.js' ),
		'team-grid/build/index': path.resolve( __dirname, 'modules/team-grid/src/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'modules' ),
		filename: '[name].js',
		// IMPORTANT: the default @wordpress/scripts config cleans
		// `output.path` before every build. Since our `output.path` is the
		// shared `modules/` directory (not a single block's own build/
		// folder), leaving cleaning on would delete block.json, editor.css
		// and src/ for both blocks on every build. Only the build/
		// subfolders should ever be wiped/regenerated.
		clean: false,
	},
};
