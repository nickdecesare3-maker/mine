<?php
/**
 * Activation / deactivation lifecycle.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Activation.
 */
class Olm_Activation {

	/**
	 * Fired on plugin activation: register the CPT so rewrite rules exist
	 * for it, then flush.
	 */
	public static function activate() {
		Olm_Post_Type::instance()->register_post_type();

		flush_rewrite_rules();

		add_option( 'olm_show_getting_started', true );
	}

	/**
	 * Fired on plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
