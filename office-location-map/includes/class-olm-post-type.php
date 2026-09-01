<?php
/**
 * Registers the "Office Location" custom post type.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Post_Type.
 */
class Olm_Post_Type {

	const POST_TYPE = 'olm_office';

	/**
	 * Singleton instance.
	 *
	 * @var Olm_Post_Type|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Olm_Post_Type
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
	 * Register the `olm_office` custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Office Locations', 'Post type general name', 'office-location-map' ),
			'singular_name'         => _x( 'Office Location', 'Post type singular name', 'office-location-map' ),
			'menu_name'             => _x( 'Office Map', 'Admin Menu text', 'office-location-map' ),
			'name_admin_bar'        => _x( 'Office Location', 'Add New on Toolbar', 'office-location-map' ),
			'add_new'               => __( 'Add New', 'office-location-map' ),
			'add_new_item'          => __( 'Add New Office Location', 'office-location-map' ),
			'new_item'              => __( 'New Office Location', 'office-location-map' ),
			'edit_item'             => __( 'Edit Office Location', 'office-location-map' ),
			'view_item'             => __( 'View Office Location', 'office-location-map' ),
			'all_items'             => __( 'Office Locations', 'office-location-map' ),
			'search_items'          => __( 'Search Office Locations', 'office-location-map' ),
			'not_found'             => __( 'No office locations found.', 'office-location-map' ),
			'not_found_in_trash'    => __( 'No office locations found in Trash.', 'office-location-map' ),
			'featured_image'        => __( 'Office Image', 'office-location-map' ),
			'set_featured_image'    => __( 'Set office image', 'office-location-map' ),
			'remove_featured_image' => __( 'Remove office image', 'office-location-map' ),
			'use_featured_image'    => __( 'Use as office image', 'office-location-map' ),
			'archives'              => __( 'Office Location archives', 'office-location-map' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Office locations plotted on the [office_map] shortcode.', 'office-location-map' ),
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_admin_bar'    => true,
			'show_in_nav_menus'    => false,
			'show_in_rest'         => true,
			'rest_base'            => 'office-locations',
			'menu_position'        => 26,
			'menu_icon'            => 'dashicons-location-alt',
			'query_var'            => true,
			'rewrite'              => array( 'slug' => 'office-location' ),
			'capability_type'      => 'post',
			'has_archive'          => false,
			'hierarchical'         => false,
			'supports'             => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'exclude_from_search'  => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
