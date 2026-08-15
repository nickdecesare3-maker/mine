<?php
/**
 * Admin-only glue: the "Getting Started" notice and its dismissal.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Admin.
 */
class Trd_Admin {

	const DISMISS_META_KEY = 'trd_dismissed_getting_started';
	const DISMISS_ACTION   = 'trd_dismiss_getting_started';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Admin
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
		add_action( 'wp_ajax_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Show the "Getting Started" panel once on Team Member admin screens,
	 * until the current user dismisses it.
	 */
	public function maybe_render_getting_started() {
		$screen = get_current_screen();

		if ( ! $screen || Trd_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_META_KEY, true ) ) {
			return;
		}

		$nonce = wp_create_nonce( self::DISMISS_ACTION );
		?>
		<div class="notice notice-info is-dismissible trd-getting-started" data-trd-dismiss-nonce="<?php echo esc_attr( $nonce ); ?>">
			<h2><?php esc_html_e( 'Getting Started with Team Roster for Divi', 'team-roster-for-divi' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Add your team members under Team Members → Add New (photo, job title, company, contact info, and bio).', 'team-roster-for-divi' ); ?></li>
				<li><?php esc_html_e( 'Drag rows in the Team Members list to reorder, or assign each person to a Group (e.g. Leadership, Sales, Engineering).', 'team-roster-for-divi' ); ?></li>
				<li><?php esc_html_e( 'Open a page in the Divi Builder and drag the "Team Grid" module onto it to display your whole team automatically, or use "Team Member Card" for individual entries.', 'team-roster-for-divi' ); ?></li>
			</ol>
		</div>
		<script>
		( function () {
			document.addEventListener( 'click', function ( event ) {
				var notice = event.target.closest( '.trd-getting-started' );
				var isDismissButton = event.target.closest( '.notice-dismiss' );
				if ( ! notice || ! isDismissButton ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', '<?php echo esc_js( self::DISMISS_ACTION ); ?>' );
				data.append( 'nonce', notice.getAttribute( 'data-trd-dismiss-nonce' ) );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } );
			} );
		} )();
		</script>
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
