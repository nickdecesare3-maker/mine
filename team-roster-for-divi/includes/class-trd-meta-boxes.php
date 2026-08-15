<?php
/**
 * Team Member details meta box (job title, company, contact info).
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Meta_Boxes.
 */
class Trd_Meta_Boxes {

	const NONCE_ACTION = 'trd_save_team_member_details';
	const NONCE_NAME   = 'trd_team_member_details_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Meta_Boxes
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . Trd_Post_Type::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Register the meta boxes on the Team Member edit screen.
	 */
	public function add_meta_box() {
		add_meta_box(
			'trd_team_member_details',
			__( 'Team Member Details', 'team-roster-for-divi' ),
			array( $this, 'render' ),
			Trd_Post_Type::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'trd_team_member_shortcode',
			__( 'Shortcode', 'team-roster-for-divi' ),
			array( $this, 'render_shortcode_box' ),
			Trd_Post_Type::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render a ready-to-copy `[team_member_card]` shortcode for this post,
	 * so users don't need to look up the post ID themselves to place it
	 * in a Divi Text/Code module.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_shortcode_box( $post ) {
		$shortcode = sprintf( '[team_member_card id="%d"]', $post->ID );
		?>
		<p>
			<input
				type="text"
				readonly="readonly"
				class="widefat code"
				value="<?php echo esc_attr( $shortcode ); ?>"
				onclick="this.select();"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'Click to select, then copy this into a Text/Code module in Divi (or anywhere shortcodes render) to show this person\'s card.', 'team-roster-for-divi' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the meta box fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$keys = trd_meta_keys();

		$job_title = get_post_meta( $post->ID, $keys['job_title'], true );
		$company   = get_post_meta( $post->ID, $keys['company'], true );
		$email     = get_post_meta( $post->ID, $keys['email'], true );
		$phone     = get_post_meta( $post->ID, $keys['phone'], true );
		$link      = get_post_meta( $post->ID, $keys['link'], true );
		?>
		<p class="description">
			<?php esc_html_e( 'The name uses the Title field above, the biography uses the main content editor below, and the photo uses the Featured Image box.', 'team-roster-for-divi' ); ?>
		</p>
		<table class="form-table trd-meta-box-table">
			<tbody>
				<tr>
					<th scope="row"><label for="trd_job_title"><?php esc_html_e( 'Job Title', 'team-roster-for-divi' ); ?></label></th>
					<td><input type="text" id="trd_job_title" name="trd_job_title" class="regular-text" value="<?php echo esc_attr( $job_title ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trd_company"><?php esc_html_e( 'Company', 'team-roster-for-divi' ); ?></label></th>
					<td><input type="text" id="trd_company" name="trd_company" class="regular-text" value="<?php echo esc_attr( $company ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trd_email"><?php esc_html_e( 'Email', 'team-roster-for-divi' ); ?></label></th>
					<td><input type="email" id="trd_email" name="trd_email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trd_phone"><?php esc_html_e( 'Phone', 'team-roster-for-divi' ); ?></label></th>
					<td><input type="tel" id="trd_phone" name="trd_phone" class="regular-text" value="<?php echo esc_attr( $phone ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trd_link"><?php esc_html_e( 'Link (LinkedIn, website, etc.)', 'team-roster-for-divi' ); ?></label></th>
					<td><input type="url" id="trd_link" name="trd_link" class="regular-text" placeholder="https://" value="<?php echo esc_attr( $link ); ?>" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Sanitize and persist the meta box fields.
	 *
	 * @param int $post_id Post ID being saved.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$keys = trd_meta_keys();

		if ( isset( $_POST['trd_job_title'] ) ) {
			update_post_meta( $post_id, $keys['job_title'], sanitize_text_field( wp_unslash( $_POST['trd_job_title'] ) ) );
		}

		if ( isset( $_POST['trd_company'] ) ) {
			update_post_meta( $post_id, $keys['company'], sanitize_text_field( wp_unslash( $_POST['trd_company'] ) ) );
		}

		if ( isset( $_POST['trd_email'] ) ) {
			update_post_meta( $post_id, $keys['email'], sanitize_email( wp_unslash( $_POST['trd_email'] ) ) );
		}

		if ( isset( $_POST['trd_phone'] ) ) {
			update_post_meta( $post_id, $keys['phone'], sanitize_text_field( wp_unslash( $_POST['trd_phone'] ) ) );
		}

		if ( isset( $_POST['trd_link'] ) ) {
			update_post_meta( $post_id, $keys['link'], esc_url_raw( wp_unslash( $_POST['trd_link'] ) ) );
		}
	}
}
