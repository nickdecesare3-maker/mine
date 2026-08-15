<?php
/**
 * Custom admin list table columns and taxonomy filter for Team Members.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Admin_List.
 */
class Trd_Admin_List {

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Admin_List|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Admin_List
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
		$post_type = Trd_Post_Type::POST_TYPE;

		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_columns' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_columns' ) );

		add_action( 'restrict_manage_posts', array( $this, 'render_group_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_by_group' ) );
		add_action( 'pre_get_posts', array( $this, 'default_menu_order' ) );
	}

	/**
	 * Insert Photo / Job Title / Company / Group columns.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function add_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['trd_photo']     = __( 'Photo', 'team-roster-for-divi' );
				$new['trd_job_title'] = __( 'Job Title', 'team-roster-for-divi' );
				$new['trd_company']   = __( 'Company', 'team-roster-for-divi' );
				$new['trd_group']     = __( 'Group', 'team-roster-for-divi' );
			}
		}

		return $new;
	}

	/**
	 * Render each custom column's value.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID for the current row.
	 */
	public function render_column( $column, $post_id ) {
		$keys = trd_meta_keys();

		switch ( $column ) {
			case 'trd_photo':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 40, 40 ), array( 'style' => 'border-radius:4px;' ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'trd_job_title':
				echo esc_html( get_post_meta( $post_id, $keys['job_title'], true ) );
				break;

			case 'trd_company':
				echo esc_html( get_post_meta( $post_id, $keys['company'], true ) );
				break;

			case 'trd_group':
				$terms = get_the_terms( $post_id, Trd_Taxonomy::TAXONOMY );
				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					echo '&#8212;';
					break;
				}
				$names = wp_list_pluck( $terms, 'name' );
				echo esc_html( implode( ', ', $names ) );
				break;
		}
	}

	/**
	 * Mark the job title / company columns sortable.
	 *
	 * @param array<string,string> $columns Sortable columns.
	 * @return array<string,string>
	 */
	public function sortable_columns( $columns ) {
		$columns['trd_job_title'] = 'trd_job_title';
		$columns['trd_company']   = 'trd_company';
		return $columns;
	}

	/**
	 * Output the "filter by group" dropdown above the list table.
	 *
	 * @param string $post_type Current screen's post type.
	 */
	public function render_group_filter( $post_type ) {
		if ( Trd_Post_Type::POST_TYPE !== $post_type ) {
			return;
		}

		$selected = isset( $_GET['trd_team_group'] ) ? sanitize_title( wp_unslash( $_GET['trd_team_group'] ) ) : '';

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'All Groups', 'team-roster-for-divi' ),
				'taxonomy'         => Trd_Taxonomy::TAXONOMY,
				'name'             => 'trd_team_group',
				'orderby'          => 'name',
				'selected'         => $selected,
				'hierarchical'     => true,
				'depth'            => 3,
				'show_count'       => true,
				'hide_empty'       => false,
				'value_field'      => 'slug',
			)
		);
	}

	/**
	 * Apply the group filter to the admin query.
	 *
	 * @param WP_Query $query Current query.
	 */
	public function filter_by_group( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		if ( Trd_Post_Type::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( empty( $_GET['trd_team_group'] ) ) {
			return;
		}

		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => Trd_Taxonomy::TAXONOMY,
					'field'    => 'slug',
					'terms'    => sanitize_title( wp_unslash( $_GET['trd_team_group'] ) ),
				),
			)
		);
	}

	/**
	 * Default the admin list to manual (drag-and-drop) order when no
	 * explicit sort has been requested.
	 *
	 * @param WP_Query $query Current query.
	 */
	public function default_menu_order( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		if ( Trd_Post_Type::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order title' );
			$query->set( 'order', 'ASC' );

			// Show the whole roster on one page in the default (draggable)
			// view. Drag-and-drop reordering renumbers every visible row's
			// `menu_order`, so pagination would let a drag on one page
			// silently collide with positions already used on another.
			if ( empty( $_GET['s'] ) ) {
				$query->set( 'posts_per_page', -1 );
			}
		}
	}
}
