<?php
/**
 * Plugin Name:       Team Roster for Divi
 * Plugin URI:        https://github.com/nickdecesare3-maker/mine
 * Description:       Adds a "Team Member" post type plus [team_member_card] and [team_grid] shortcodes -- drop them into a Text/Code module in Divi 4 or Divi 5 -- so you can build a Team page in minutes.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Team Roster for Divi
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       team-roster-for-divi
 * Domain Path:       /languages
 *
 * @package TeamRosterForDivi
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Basic plugin constants. All constants, hooks, functions and classes are
// prefixed with TRD / trd_ to avoid collisions with other plugins/themes.
define( 'TRD_VERSION', '1.0.0' );
define( 'TRD_PLUGIN_FILE', __FILE__ );
define( 'TRD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TRD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Composer-free, hand-rolled autoload/require of the plugin's includes.
 * The plugin intentionally has no external PHP dependencies.
 */
require_once TRD_PLUGIN_DIR . 'includes/functions.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-post-type.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-taxonomy.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-meta-boxes.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-rest.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-admin-list.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-reorder.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-team-grid-render.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-shortcodes.php';
require_once TRD_PLUGIN_DIR . 'includes/class-trd-activation.php';
require_once TRD_PLUGIN_DIR . 'admin/class-trd-admin.php';
require_once TRD_PLUGIN_DIR . 'admin/class-trd-settings.php';

/**
 * Bootstraps every plugin component. Runs on `plugins_loaded` so that
 * translations and other plugins have had a chance to load first.
 */
function trd_bootstrap() {
	load_plugin_textdomain( 'team-roster-for-divi', false, dirname( TRD_PLUGIN_BASENAME ) . '/languages' );

	Trd_Post_Type::instance();
	Trd_Taxonomy::instance();
	Trd_Meta_Boxes::instance();
	Trd_Rest::instance();
	Trd_Admin_List::instance();
	Trd_Reorder::instance();
	Trd_Shortcodes::instance();
	Trd_Admin::instance();
	Trd_Settings::instance();
}
add_action( 'plugins_loaded', 'trd_bootstrap' );

register_activation_hook( __FILE__, array( 'Trd_Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Trd_Activation', 'deactivate' ) );
