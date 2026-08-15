<?php
/**
 * Drag-and-drop manual reordering of Team Members on the admin list table.
 * Order is stored on the native `menu_order` post field and saved via AJAX.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Reorder.
 */
class Trd_Reorder {

	const AJAX_ACTION = 'trd_reorder_team_members';
	const NONCE_ACTION = 'trd_reorder_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Reorder|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Reorder
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
	}

	/**
	 * Enqueue jQuery UI Sortable + our small controller script, only on
	 * the Team Members list screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || Trd_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Reordering only makes sense on the default, unfiltered/unsearched
		// manual-order view -- a filtered subset (search, or a single group)
		// would otherwise silently renumber `menu_order` for only part of
		// the roster, corrupting the position of everyone else.
		if ( ! empty( $_GET['orderby'] ) || ! empty( $_GET['s'] ) || ! empty( $_GET['trd_team_group'] ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style(
			'trd-admin',
			TRD_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			TRD_VERSION
		);

		wp_enqueue_script(
			'trd-admin-reorder',
			TRD_PLUGIN_URL . 'admin/js/admin-reorder.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			TRD_VERSION,
			true
		);

		wp_localize_script(
			'trd-admin-reorder',
			'trdReorder',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'saving' => __( 'Saving order…', 'team-roster-for-divi' ),
					'saved'  => __( 'Order saved.', 'team-roster-for-divi' ),
					'error'  => __( 'Could not save order, please try again.', 'team-roster-for-divi' ),
				),
			)
		);
	}

	/**
	 * AJAX handler: persist the new row order as `menu_order` values.
	 */
	public function handle_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to reorder team members.', 'team-roster-for-divi' ) ), 403 );
		}

		$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
		$order = array_map( 'absint', $order );
		$order = array_filter( $order );

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to save.', 'team-roster-for-divi' ) ), 400 );
		}

		foreach ( array_values( $order ) as $position => $post_id ) {
			if ( Trd_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
				continue;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'         => $post_id,
					'menu_order' => $position,
				)
			);
		}

		wp_send_json_success( array( 'message' => __( 'Order saved.', 'team-roster-for-divi' ) ) );
	}
}
