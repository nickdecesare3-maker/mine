<?php
/**
 * Activation / deactivation lifecycle.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Activation.
 */
class Trd_Activation {

	/**
	 * Fired on plugin activation: register the CPT/taxonomy so rewrite
	 * rules exist for them, then flush.
	 */
	public static function activate() {
		Trd_Post_Type::instance()->register_post_type();
		Trd_Taxonomy::instance()->register_taxonomy();

		flush_rewrite_rules();

		add_option( 'trd_show_getting_started', true );
	}

	/**
	 * Fired on plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
