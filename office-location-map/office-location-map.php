<?php
/**
 * Plugin Name:       Office Location Map
 * Plugin URI:        https://github.com/nickdecesare3-maker/mine
 * Description:       Adds an "Office Location" post type plus an [office_map] shortcode that plots offices by latitude/longitude on a fully re-colorable stylized map. Clicking (or tapping) a marker opens a centered modal with the office's name, address, photo, description and contact details.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Office Location Map
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       office-location-map
 * Domain Path:       /languages
 *
 * @package OfficeLocationMap
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Basic plugin constants. All constants, hooks, functions and classes are
// prefixed with OLM / olm_ to avoid collisions with other plugins/themes.
define( 'OLM_VERSION', '1.0.2' );
define( 'OLM_PLUGIN_FILE', __FILE__ );
define( 'OLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OLM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Composer-free, hand-rolled autoload/require of the plugin's includes.
 * The plugin intentionally has no external PHP dependencies.
 */
require_once OLM_PLUGIN_DIR . 'includes/functions.php';
require_once OLM_PLUGIN_DIR . 'includes/class-olm-post-type.php';
require_once OLM_PLUGIN_DIR . 'includes/class-olm-meta-boxes.php';
require_once OLM_PLUGIN_DIR . 'includes/class-olm-shortcode.php';
require_once OLM_PLUGIN_DIR . 'includes/class-olm-activation.php';
require_once OLM_PLUGIN_DIR . 'admin/class-olm-admin.php';
require_once OLM_PLUGIN_DIR . 'admin/class-olm-settings.php';

/**
 * Bootstraps every plugin component. Runs on `plugins_loaded` so that
 * translations and other plugins have had a chance to load first.
 */
function olm_bootstrap() {
	load_plugin_textdomain( 'office-location-map', false, dirname( OLM_PLUGIN_BASENAME ) . '/languages' );

	Olm_Post_Type::instance();
	Olm_Meta_Boxes::instance();
	Olm_Shortcode::instance();
	Olm_Admin::instance();
	Olm_Settings::instance();
}
add_action( 'plugins_loaded', 'olm_bootstrap' );

register_activation_hook( __FILE__, array( 'Olm_Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Olm_Activation', 'deactivate' ) );
