<?php
/**
 * Registers the "Group" taxonomy used to organize Team Members
 * (e.g. Leadership, Sales, Engineering).
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Taxonomy.
 */
class Trd_Taxonomy {

	const TAXONOMY = 'trd_team_group';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Taxonomy|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Taxonomy
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the `trd_team_group` hierarchical taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Groups', 'taxonomy general name', 'team-roster-for-divi' ),
			'singular_name'     => _x( 'Group', 'taxonomy singular name', 'team-roster-for-divi' ),
			'search_items'      => __( 'Search Groups', 'team-roster-for-divi' ),
			'all_items'         => __( 'All Groups', 'team-roster-for-divi' ),
			'parent_item'       => __( 'Parent Group', 'team-roster-for-divi' ),
			'parent_item_colon' => __( 'Parent Group:', 'team-roster-for-divi' ),
			'edit_item'         => __( 'Edit Group', 'team-roster-for-divi' ),
			'update_item'       => __( 'Update Group', 'team-roster-for-divi' ),
			'add_new_item'      => __( 'Add New Group', 'team-roster-for-divi' ),
			'new_item_name'     => __( 'New Group Name', 'team-roster-for-divi' ),
			'menu_name'         => __( 'Groups', 'team-roster-for-divi' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'team-groups',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'team-group' ),
		);

		register_taxonomy( self::TAXONOMY, array( Trd_Post_Type::POST_TYPE ), $args );
	}
}
