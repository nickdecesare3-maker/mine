<?php
/**
 * Shared helper functions.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta keys used by the Office Location post type, centralised so every
 * file (meta boxes, shortcode, render templates) reads/writes the same keys.
 *
 * @return array<string,string> Map of field id => meta key.
 */
function olm_meta_keys() {
	return array(
		'latitude'       => '_olm_latitude',
		'longitude'      => '_olm_longitude',
		'address'        => '_olm_address',
		'contact_name'   => '_olm_contact_name',
		'contact_title'  => '_olm_contact_title',
		'contact_phone'  => '_olm_contact_phone',
		'contact_email'  => '_olm_contact_email',
	);
}

/**
 * Gather every displayable field for an Office Location post into one array.
 *
 * @param int $post_id Office Location post ID.
 * @return array<string,mixed>|null
 */
function olm_get_office_data( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || Olm_Post_Type::POST_TYPE !== $post->post_type ) {
		return null;
	}

	$keys = olm_meta_keys();

	$latitude  = get_post_meta( $post_id, $keys['latitude'], true );
	$longitude = get_post_meta( $post_id, $keys['longitude'], true );

	if ( '' === $latitude || '' === $longitude || null === $latitude || null === $longitude ) {
		return null;
	}

	return array(
		'id'             => $post->ID,
		'name'           => get_the_title( $post ),
		'latitude'       => (float) $latitude,
		'longitude'      => (float) $longitude,
		'address'        => get_post_meta( $post_id, $keys['address'], true ),
		'image_id'       => (int) get_post_thumbnail_id( $post ),
		'image_url'      => get_the_post_thumbnail_url( $post, 'medium' ),
		'description'    => apply_filters( 'the_content', $post->post_content ),
		'contact_name'   => get_post_meta( $post_id, $keys['contact_name'], true ),
		'contact_title'  => get_post_meta( $post_id, $keys['contact_title'], true ),
		'contact_phone'  => get_post_meta( $post_id, $keys['contact_phone'], true ),
		'contact_email'  => get_post_meta( $post_id, $keys['contact_email'], true ),
	);
}

/**
 * Project a latitude/longitude pair onto the plugin's equirectangular
 * stylized map (viewBox "0 0 1000 500", matching assets/images/world-map.svg)
 * as a percentage position, so a marker can be placed with plain CSS
 * `left`/`top` inside the map container -- no mapping JS library needed.
 *
 * @param float $latitude  -90 (south pole) to 90 (north pole).
 * @param float $longitude -180 (west) to 180 (east).
 * @return array{x: float, y: float} Percentages, each 0-100.
 */
function olm_project_marker_position( $latitude, $longitude ) {
	$latitude  = max( -90, min( 90, (float) $latitude ) );
	$longitude = max( -180, min( 180, (float) $longitude ) );

	return array(
		'x' => ( $longitude + 180 ) / 360 * 100,
		'y' => ( 90 - $latitude ) / 180 * 100,
	);
}

/**
 * Get the plugin's stylized world map SVG markup, inlined directly (rather
 * than loaded via `<img src>`) so the site's CSS -- including the admin's
 * land/sea/border color choices -- can actually reach the `.olm-land` /
 * `.olm-sea` elements inside it. An externally-referenced SVG image is a
 * separate document and can't be styled by the parent page's stylesheet.
 *
 * @return string Raw `<svg>…</svg>` markup, or an empty string if the
 *                bundled asset is missing.
 */
function olm_get_world_map_svg() {
	static $svg = null;

	if ( null !== $svg ) {
		return $svg;
	}

	$path = OLM_PLUGIN_DIR . 'assets/images/world-map.svg';

	if ( ! file_exists( $path ) ) {
		$svg = '';
		return $svg;
	}

	$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$start    = strpos( $contents, '<svg' );
	$svg      = false === $start ? '' : substr( $contents, $start );

	return $svg;
}

/**
 * Escape + format a phone number for a tel: href without mangling
 * international formats the site owner may have typed in.
 *
 * @param string $phone Raw phone number.
 * @return string
 */
function olm_phone_href( $phone ) {
	$digits = preg_replace( '/[^0-9+]/', '', (string) $phone );
	return 'tel:' . rawurlencode( $digits );
}

/**
 * Render one office marker (a positioned dot on the map) plus its paired
 * info modal markup. Shared so every [office_map] instance stays in sync.
 *
 * @param array<string,mixed> $office Data shaped like olm_get_office_data().
 * @return string Escaped HTML.
 */
function olm_render_office_marker( $office ) {
	$name          = isset( $office['name'] ) ? $office['name'] : '';
	$address       = isset( $office['address'] ) ? $office['address'] : '';
	$image_url     = isset( $office['image_url'] ) ? $office['image_url'] : '';
	$description   = isset( $office['description'] ) ? wp_kses_post( $office['description'] ) : '';
	$contact_name  = isset( $office['contact_name'] ) ? $office['contact_name'] : '';
	$contact_title = isset( $office['contact_title'] ) ? $office['contact_title'] : '';
	$contact_phone = isset( $office['contact_phone'] ) ? $office['contact_phone'] : '';
	$contact_email = isset( $office['contact_email'] ) ? $office['contact_email'] : '';

	$position   = olm_project_marker_position( $office['latitude'], $office['longitude'] );
	$modal_id   = wp_unique_id( 'olm-office-modal-' );
	$heading_id = $modal_id . '-title';

	ob_start();
	?>
	<button
		type="button"
		class="olm-marker"
		style="left:<?php echo esc_attr( round( $position['x'], 3 ) ); ?>%;top:<?php echo esc_attr( round( $position['y'], 3 ) ); ?>%;"
		data-olm-marker-for="<?php echo esc_attr( $modal_id ); ?>"
		aria-haspopup="dialog"
		aria-label="<?php echo esc_attr( $name ); ?>"
	>
		<span class="olm-marker__dot" aria-hidden="true"></span>
	</button>
	<div class="olm-modal" id="<?php echo esc_attr( $modal_id ); ?>" data-olm-modal hidden>
		<div class="olm-modal__overlay" data-olm-modal-close></div>
		<div class="olm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>" tabindex="-1">
			<button type="button" class="olm-modal__close" data-olm-modal-close aria-label="<?php esc_attr_e( 'Close', 'office-location-map' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
			<?php if ( $image_url ) : ?>
				<img class="olm-modal__image olm-field-image" src="<?php echo esc_url( $image_url ); ?>" alt="" />
			<?php endif; ?>
			<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="olm-modal__name olm-field-office_name"><?php echo esc_html( $name ); ?></h2>
			<?php if ( $address ) : ?>
				<p class="olm-modal__address olm-field-address"><?php echo nl2br( esc_html( $address ) ); ?></p>
			<?php endif; ?>
			<?php if ( $description ) : ?>
				<div class="olm-modal__description olm-field-description"><?php echo wp_kses_post( $description ); ?></div>
			<?php endif; ?>
			<?php if ( $contact_name || $contact_title || $contact_phone || $contact_email ) : ?>
				<div class="olm-modal__contact">
					<?php if ( $contact_name ) : ?>
						<p class="olm-modal__contact-name olm-field-contact_name"><?php echo esc_html( $contact_name ); ?></p>
					<?php endif; ?>
					<?php if ( $contact_title ) : ?>
						<p class="olm-modal__contact-title olm-field-contact_title"><?php echo esc_html( $contact_title ); ?></p>
					<?php endif; ?>
					<?php if ( $contact_phone ) : ?>
						<p class="olm-modal__contact-phone olm-field-contact_phone"><a href="<?php echo esc_url( olm_phone_href( $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></p>
					<?php endif; ?>
					<?php if ( $contact_email ) : ?>
						<p class="olm-modal__contact-email olm-field-contact_email"><a href="<?php echo esc_url( 'mailto:' . $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
