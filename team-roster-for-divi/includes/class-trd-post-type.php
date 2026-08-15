<?php
/**
 * Registers the "Team Member" custom post type.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Post_Type.
 */
class Trd_Post_Type {

	const POST_TYPE = 'trd_team_member';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Post_Type|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Post_Type
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
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the `trd_team_member` custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Team Members', 'Post type general name', 'team-roster-for-divi' ),
			'singular_name'         => _x( 'Team Member', 'Post type singular name', 'team-roster-for-divi' ),
			'menu_name'             => _x( 'Team Members', 'Admin Menu text', 'team-roster-for-divi' ),
			'name_admin_bar'        => _x( 'Team Member', 'Add New on Toolbar', 'team-roster-for-divi' ),
			'add_new'               => __( 'Add New', 'team-roster-for-divi' ),
			'add_new_item'          => __( 'Add New Team Member', 'team-roster-for-divi' ),
			'new_item'              => __( 'New Team Member', 'team-roster-for-divi' ),
			'edit_item'             => __( 'Edit Team Member', 'team-roster-for-divi' ),
			'view_item'             => __( 'View Team Member', 'team-roster-for-divi' ),
			'all_items'             => __( 'Team Members', 'team-roster-for-divi' ),
			'search_items'          => __( 'Search Team Members', 'team-roster-for-divi' ),
			'not_found'             => __( 'No team members found.', 'team-roster-for-divi' ),
			'not_found_in_trash'    => __( 'No team members found in Trash.', 'team-roster-for-divi' ),
			'featured_image'        => __( 'Photo', 'team-roster-for-divi' ),
			'set_featured_image'    => __( 'Set photo', 'team-roster-for-divi' ),
			'remove_featured_image' => __( 'Remove photo', 'team-roster-for-divi' ),
			'use_featured_image'    => __( 'Use as photo', 'team-roster-for-divi' ),
			'archives'              => __( 'Team Member archives', 'team-roster-for-divi' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Individual team member entries used by the Team Member Card and Team Grid Divi modules.', 'team-roster-for-divi' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'team-members',
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-groups',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'team-member' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes' ),
			'exclude_from_search' => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
