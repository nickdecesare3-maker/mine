<?php
/**
 * Shared helper functions.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta keys used by the Team Member post type, centralised so every file
 * (meta boxes, REST, shortcodes, render templates) reads/writes the same keys.
 *
 * @return array<string,string> Map of field id => meta key.
 */
function trd_meta_keys() {
	return array(
		'job_title' => '_trd_job_title',
		'company'   => '_trd_company',
		'email'     => '_trd_email',
		'phone'     => '_trd_phone',
		'link'      => '_trd_link',
	);
}

/**
 * Gather every displayable field for a Team Member post into one array.
 * Used by both shortcodes (manual-select "from post" mode) and the REST API.
 *
 * @param int $post_id Team Member post ID.
 * @return array<string,mixed>|null
 */
function trd_get_team_member_data( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || Trd_Post_Type::POST_TYPE !== $post->post_type ) {
		return null;
	}

	$keys = trd_meta_keys();

	$groups = wp_get_post_terms( $post_id, Trd_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );

	return array(
		'id'          => $post->ID,
		'name'        => get_the_title( $post ),
		'photo_id'    => (int) get_post_thumbnail_id( $post ),
		'photo_url'   => get_the_post_thumbnail_url( $post, 'medium' ),
		'job_title'   => get_post_meta( $post_id, $keys['job_title'], true ),
		'company'     => get_post_meta( $post_id, $keys['company'], true ),
		'email'       => get_post_meta( $post_id, $keys['email'], true ),
		'phone'       => get_post_meta( $post_id, $keys['phone'], true ),
		'link'        => get_post_meta( $post_id, $keys['link'], true ),
		'bio'         => apply_filters( 'the_content', $post->post_content ),
		'bio_raw'     => $post->post_content,
		'groups'      => is_wp_error( $groups ) ? array() : $groups,
		'menu_order'  => (int) $post->menu_order,
		'permalink'   => get_permalink( $post ),
	);
}

/**
 * Escape + format a phone number for a tel: href without mangling
 * international formats the site owner may have typed in.
 *
 * @param string $phone Raw phone number.
 * @return string
 */
function trd_phone_href( $phone ) {
	$digits = preg_replace( '/[^0-9+]/', '', (string) $phone );
	return 'tel:' . rawurlencode( $digits );
}

/**
 * Render one Team Member Card (photo, name, title, company, contact info,
 * Read Bio button) plus its paired bio modal markup. Shared by the single
 * `[team_member_card]` and `[team_grid]` shortcodes so both stay in sync.
 *
 * @param array<string,mixed> $member      Data shaped like trd_get_team_member_data().
 * @param array<string,mixed> $args {
 *     Optional display overrides.
 *
 *     @type string $button_label Read Bio button text.
 * }
 * @return string Escaped HTML.
 */
function trd_render_team_card( $member, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'button_label' => __( 'Read Bio', 'team-roster-for-divi' ),
		)
	);

	$name       = isset( $member['name'] ) ? $member['name'] : '';
	$job_title  = isset( $member['job_title'] ) ? $member['job_title'] : '';
	$company    = isset( $member['company'] ) ? $member['company'] : '';
	$email      = isset( $member['email'] ) ? $member['email'] : '';
	$phone      = isset( $member['phone'] ) ? $member['phone'] : '';
	$link       = isset( $member['link'] ) ? $member['link'] : '';
	$photo_url  = isset( $member['photo_url'] ) ? $member['photo_url'] : '';
	$bio        = isset( $member['bio'] ) ? wp_kses_post( $member['bio'] ) : '';
	$modal_id   = wp_unique_id( 'trd-bio-modal-' );
	$heading_id = $modal_id . '-title';

	$meta_parts = array_filter( array( $job_title, $company ) );
	$meta_line  = implode( ', ', $meta_parts );

	ob_start();
	?>
	<div class="trd-card">
		<div class="trd-card__photo">
			<?php if ( $photo_url ) : ?>
				<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
			<?php else : ?>
				<div class="trd-card__photo-placeholder" aria-hidden="true"><?php echo esc_html( trd_initials( $name ) ); ?></div>
			<?php endif; ?>
		</div>
		<div class="trd-card__body">
			<?php if ( $name ) : ?>
				<h3 class="trd-card__name"><?php echo esc_html( $name ); ?></h3>
			<?php endif; ?>
			<?php if ( $meta_line ) : ?>
				<p class="trd-card__meta"><?php echo esc_html( $meta_line ); ?></p>
			<?php endif; ?>
			<?php if ( $email || $phone || $link ) : ?>
				<ul class="trd-card__contact">
					<?php if ( $email ) : ?>
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<li><a href="<?php echo esc_url( trd_phone_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( $link ) : ?>
						<li><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( trd_link_label( $link ) ); ?></a></li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $bio ) : ?>
				<button type="button" class="trd-card__bio-btn" data-trd-modal-open="<?php echo esc_attr( $modal_id ); ?>" aria-haspopup="dialog">
					<?php echo esc_html( $args['button_label'] ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $bio ) : ?>
		<div class="trd-modal" id="<?php echo esc_attr( $modal_id ); ?>" data-trd-modal hidden>
			<div class="trd-modal__overlay" data-trd-modal-close></div>
			<div class="trd-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>" tabindex="-1">
				<button type="button" class="trd-modal__close" data-trd-modal-close aria-label="<?php esc_attr_e( 'Close', 'team-roster-for-divi' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
				<?php if ( $photo_url ) : ?>
					<img class="trd-modal__photo" src="<?php echo esc_url( $photo_url ); ?>" alt="" />
				<?php endif; ?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="trd-modal__name"><?php echo esc_html( $name ); ?></h2>
				<?php if ( $meta_line ) : ?>
					<p class="trd-modal__meta"><?php echo esc_html( $meta_line ); ?></p>
				<?php endif; ?>
				<div class="trd-modal__bio"><?php echo wp_kses_post( $bio ); ?></div>
			</div>
		</div>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

/**
 * Two-letter initials fallback avatar for a team member with no photo.
 *
 * @param string $name Full name.
 * @return string
 */
function trd_initials( $name ) {
	$words    = preg_split( '/\s+/', trim( (string) $name ) );
	$words    = array_filter( $words );
	$initials = array_map(
		static function ( $word ) {
			return mb_strtoupper( mb_substr( $word, 0, 1 ) );
		},
		array_slice( $words, 0, 2 )
	);
	return implode( '', $initials );
}

/**
 * Friendly label for a contact link (its host, e.g. "linkedin.com").
 *
 * @param string $url Link URL.
 * @return string
 */
function trd_link_label( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host ? preg_replace( '/^www\./', '', $host ) : $url;
}
