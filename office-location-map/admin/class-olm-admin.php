<?php
/**
 * Admin-only glue: the "Getting Started" notice and its dismissal.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Admin.
 */
class Olm_Admin {

	const DISMISS_META_KEY = 'olm_dismissed_getting_started';
	const DISMISS_ACTION   = 'olm_dismiss_getting_started';

	/**
	 * Singleton instance.
	 *
	 * @var Olm_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Olm_Admin
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
		add_action( 'admin_notices', array( $this, 'maybe_render_getting_started' ) );
		add_action( 'admin_notices', array( $this, 'render_map_shortcode_hint' ) );
		add_action( 'wp_ajax_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Show the "Getting Started" panel once on Office Location admin
	 * screens, until the current user dismisses it.
	 */
	public function maybe_render_getting_started() {
		$screen = get_current_screen();

		if ( ! $screen || Olm_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_META_KEY, true ) ) {
			return;
		}

		$nonce = wp_create_nonce( self::DISMISS_ACTION );
		?>
		<div class="notice notice-info is-dismissible olm-getting-started" data-olm-dismiss-nonce="<?php echo esc_attr( $nonce ); ?>">
			<h2><?php esc_html_e( 'Getting Started with Office Location Map', 'office-location-map' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Add each office under Office Map → Add New: set its name (Title), latitude/longitude, full address, contact person details, an image, and a description in the main content editor.', 'office-location-map' ); ?></li>
				<li><?php esc_html_e( 'Use Office Map → Map & Field Styling to set the land, sea and border colors, and the font, size and color of every field shown in the info modal.', 'office-location-map' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %s: [office_map] shortcode, shown as inline code. */
						esc_html__( 'Paste the %s shortcode into any post, page or widget to show every office on the map -- clicking (or tapping) a marker opens its info modal centered on screen.', 'office-location-map' ),
						'<code>[office_map]</code>'
					);
					?>
				</li>
			</ol>
		</div>
		<script>
		( function () {
			document.addEventListener( 'click', function ( event ) {
				var notice = event.target.closest( '.olm-getting-started' );
				var isDismissButton = event.target.closest( '.notice-dismiss' );
				if ( ! notice || ! isDismissButton ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', '<?php echo esc_js( self::DISMISS_ACTION ); ?>' );
				data.append( 'nonce', notice.getAttribute( 'data-olm-dismiss-nonce' ) );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } );
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Small, permanent (non-dismissible) reminder of the `[office_map]`
	 * shortcode above the Office Locations list.
	 */
	public function render_map_shortcode_hint() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-' . Olm_Post_Type::POST_TYPE !== $screen->id ) {
			return;
		}
		?>
		<div class="notice notice-info olm-shortcode-hint">
			<p>
				<?php
				printf(
					/* translators: %s: [office_map] shortcode, shown as inline code. */
					esc_html__( 'Paste %s into any post, page or widget to display this map.', 'office-location-map' ),
					'<code>[office_map]</code>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX handler: remember that the current user dismissed the notice.
	 */
	public function handle_dismiss() {
		check_ajax_referer( self::DISMISS_ACTION, 'nonce' );

		update_user_meta( get_current_user_id(), self::DISMISS_META_KEY, 1 );

		wp_send_json_success();
	}
}
