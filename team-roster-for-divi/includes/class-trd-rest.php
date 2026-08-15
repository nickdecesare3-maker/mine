<?php
/**
 * Exposes Team Member custom fields on the REST API so Divi 5's
 * block/React editor (or any other REST consumer) can read them.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Rest.
 */
class Trd_Rest {

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Rest|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Rest
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
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register each custom field with `register_post_meta` so it is
	 * readable (and, for logged-in users who can edit, writable) via
	 * `/wp-json/wp/v2/team-members/<id>`.
	 */
	public function register_meta() {
		$keys = trd_meta_keys();

		$string_fields = array( 'job_title', 'company', 'phone' );
		foreach ( $string_fields as $field ) {
			register_post_meta(
				Trd_Post_Type::POST_TYPE,
				$keys[ $field ],
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => array( $this, 'auth_callback' ),
				)
			);
		}

		register_post_meta(
			Trd_Post_Type::POST_TYPE,
			$keys['email'],
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_email',
				'auth_callback'     => array( $this, 'auth_callback' ),
			)
		);

		register_post_meta(
			Trd_Post_Type::POST_TYPE,
			$keys['link'],
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => array( $this, 'auth_callback' ),
			)
		);
	}

	/**
	 * Only users who can edit the given post may write these fields via REST.
	 *
	 * @param bool   $allowed Whether the value is allowed to be set.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id Post ID.
	 * @return bool
	 */
	public function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}
}
