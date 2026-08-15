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
		add_action( 'admin_notices', array( $this, 'render_grid_shortcode_hint' ) );
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
				<li>
					<?php
					printf(
						/* translators: %s: [team_grid] shortcode, shown as inline code. */
						esc_html__( 'In Divi (4 or 5), add a Text or Code module to your page and paste the %s shortcode to display the whole team automatically -- or paste the %s shortcode from an individual Team Member\'s edit screen for a single card.', 'team-roster-for-divi' ),
						'<code>[team_grid]</code>',
						'<code>[team_member_card id="…"]</code>'
					);
					?>
				</li>
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
	 * Small, permanent (non-dismissible) reminder of the `[team_grid]`
	 * shortcode above the Team Members list, since there's no visual
	 * builder module to drag in -- shortcodes are the whole interface.
	 */
	public function render_grid_shortcode_hint() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-' . Trd_Post_Type::POST_TYPE !== $screen->id ) {
			return;
		}
		?>
		<div class="notice notice-info trd-shortcode-hint">
			<p>
				<?php
				printf(
					/* translators: %s: [team_grid] shortcode, shown as inline code. */
					esc_html__( 'Paste %s into a Text or Code module in Divi (4 or 5), or into any post/page content, to display this whole roster.', 'team-roster-for-divi' ),
					'<code>[team_grid]</code>'
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
