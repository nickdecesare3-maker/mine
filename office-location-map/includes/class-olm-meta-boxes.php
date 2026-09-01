<?php
/**
 * Office Location details meta box (coordinates, address, contact info).
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Meta_Boxes.
 */
class Olm_Meta_Boxes {

	const NONCE_ACTION = 'olm_save_office_details';
	const NONCE_NAME   = 'olm_office_details_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var Olm_Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Olm_Meta_Boxes
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
		add_action( 'save_post_' . Olm_Post_Type::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Register the meta boxes on the Office Location edit screen.
	 */
	public function add_meta_box() {
		add_meta_box(
			'olm_office_coordinates',
			__( 'Map Coordinates', 'office-location-map' ),
			array( $this, 'render_coordinates' ),
			Olm_Post_Type::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'olm_office_details',
			__( 'Office & Contact Details', 'office-location-map' ),
			array( $this, 'render_details' ),
			Olm_Post_Type::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'olm_office_shortcode',
			__( 'Shortcode', 'office-location-map' ),
			array( $this, 'render_shortcode_box' ),
			Olm_Post_Type::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render a ready-to-copy `[office_map]` shortcode scoped to this one
	 * office, so users don't need to look up the post ID themselves.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_shortcode_box( $post ) {
		$shortcode = sprintf( '[office_map ids="%d"]', $post->ID );
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
			<?php esc_html_e( 'Click to select, then copy this to show only this office. Use [office_map] with no ids="" to show every published office location.', 'office-location-map' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the coordinates meta box fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_coordinates( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$keys      = olm_meta_keys();
		$latitude  = get_post_meta( $post->ID, $keys['latitude'], true );
		$longitude = get_post_meta( $post->ID, $keys['longitude'], true );
		?>
		<p class="description">
			<?php esc_html_e( 'The marker\'s position on the [office_map] shortcode. Decimal degrees, e.g. 40.7484 latitude, -73.9857 longitude for New York City.', 'office-location-map' ); ?>
		</p>
		<table class="form-table olm-meta-box-table">
			<tbody>
				<tr>
					<th scope="row"><label for="olm_latitude"><?php esc_html_e( 'Latitude', 'office-location-map' ); ?></label></th>
					<td><input type="text" inputmode="decimal" id="olm_latitude" name="olm_latitude" class="regular-text" placeholder="-90 to 90" value="<?php echo esc_attr( $latitude ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="olm_longitude"><?php esc_html_e( 'Longitude', 'office-location-map' ); ?></label></th>
					<td><input type="text" inputmode="decimal" id="olm_longitude" name="olm_longitude" class="regular-text" placeholder="-180 to 180" value="<?php echo esc_attr( $longitude ); ?>" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the office/contact details meta box fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_details( $post ) {
		$keys = olm_meta_keys();

		$address       = get_post_meta( $post->ID, $keys['address'], true );
		$contact_name  = get_post_meta( $post->ID, $keys['contact_name'], true );
		$contact_title = get_post_meta( $post->ID, $keys['contact_title'], true );
		$contact_phone = get_post_meta( $post->ID, $keys['contact_phone'], true );
		$contact_email = get_post_meta( $post->ID, $keys['contact_email'], true );
		?>
		<p class="description">
			<?php esc_html_e( 'The office name uses the Title field above, the description uses the main content editor below, and the image uses the Featured Image box.', 'office-location-map' ); ?>
		</p>
		<table class="form-table olm-meta-box-table">
			<tbody>
				<tr>
					<th scope="row"><label for="olm_address"><?php esc_html_e( 'Full Address', 'office-location-map' ); ?></label></th>
					<td><textarea id="olm_address" name="olm_address" class="large-text" rows="3"><?php echo esc_textarea( $address ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="olm_contact_name"><?php esc_html_e( 'Contact Person Name', 'office-location-map' ); ?></label></th>
					<td><input type="text" id="olm_contact_name" name="olm_contact_name" class="regular-text" value="<?php echo esc_attr( $contact_name ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="olm_contact_title"><?php esc_html_e( 'Contact Person Title', 'office-location-map' ); ?></label></th>
					<td><input type="text" id="olm_contact_title" name="olm_contact_title" class="regular-text" value="<?php echo esc_attr( $contact_title ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="olm_contact_phone"><?php esc_html_e( 'Contact Person Phone', 'office-location-map' ); ?></label></th>
					<td><input type="tel" id="olm_contact_phone" name="olm_contact_phone" class="regular-text" value="<?php echo esc_attr( $contact_phone ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="olm_contact_email"><?php esc_html_e( 'Contact Person Email', 'office-location-map' ); ?></label></th>
					<td><input type="email" id="olm_contact_email" name="olm_contact_email" class="regular-text" value="<?php echo esc_attr( $contact_email ); ?>" /></td>
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

		$keys = olm_meta_keys();

		if ( isset( $_POST['olm_latitude'] ) ) {
			$latitude = wp_unslash( $_POST['olm_latitude'] );
			if ( is_numeric( $latitude ) ) {
				update_post_meta( $post_id, $keys['latitude'], max( -90, min( 90, (float) $latitude ) ) );
			} else {
				update_post_meta( $post_id, $keys['latitude'], '' );
			}
		}

		if ( isset( $_POST['olm_longitude'] ) ) {
			$longitude = wp_unslash( $_POST['olm_longitude'] );
			if ( is_numeric( $longitude ) ) {
				update_post_meta( $post_id, $keys['longitude'], max( -180, min( 180, (float) $longitude ) ) );
			} else {
				update_post_meta( $post_id, $keys['longitude'], '' );
			}
		}

		if ( isset( $_POST['olm_address'] ) ) {
			update_post_meta( $post_id, $keys['address'], sanitize_textarea_field( wp_unslash( $_POST['olm_address'] ) ) );
		}

		if ( isset( $_POST['olm_contact_name'] ) ) {
			update_post_meta( $post_id, $keys['contact_name'], sanitize_text_field( wp_unslash( $_POST['olm_contact_name'] ) ) );
		}

		if ( isset( $_POST['olm_contact_title'] ) ) {
			update_post_meta( $post_id, $keys['contact_title'], sanitize_text_field( wp_unslash( $_POST['olm_contact_title'] ) ) );
		}

		if ( isset( $_POST['olm_contact_phone'] ) ) {
			update_post_meta( $post_id, $keys['contact_phone'], sanitize_text_field( wp_unslash( $_POST['olm_contact_phone'] ) ) );
		}

		if ( isset( $_POST['olm_contact_email'] ) ) {
			update_post_meta( $post_id, $keys['contact_email'], sanitize_email( wp_unslash( $_POST['olm_contact_email'] ) ) );
		}
	}
}
