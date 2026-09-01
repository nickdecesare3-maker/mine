<?php
/**
 * [office_map] shortcode.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Shortcode.
 */
class Olm_Shortcode {

	const TAG = 'office_map';

	/**
	 * Singleton instance.
	 *
	 * @var Olm_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Whether the Display Settings CSS has already been attached to the
	 * `olm-modal` stylesheet on this request.
	 *
	 * @var bool
	 */
	private $inline_css_added = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Olm_Shortcode
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
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register the shortcode tag.
	 */
	public function register_shortcode() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Register (but do not enqueue) the map/modal assets. They're enqueued
	 * on demand from inside the shortcode callback, so a page that never
	 * uses the shortcode never loads them.
	 */
	public function register_assets() {
		wp_register_style( 'olm-map', OLM_PLUGIN_URL . 'assets/css/map.css', array(), OLM_VERSION );
		// Depends on olm-map so it always prints after it, and the Display
		// Settings CSS (attached below, to whichever handle prints last)
		// needs to win the cascade over both base stylesheets.
		wp_register_style( 'olm-modal', OLM_PLUGIN_URL . 'assets/css/modal.css', array( 'olm-map' ), OLM_VERSION );
		wp_register_script( 'olm-map', OLM_PLUGIN_URL . 'assets/js/map.js', array(), OLM_VERSION, true );
	}

	/**
	 * Enqueue the map + modal assets. Safe to call more than once per
	 * request (e.g. multiple shortcodes on one page); WP dedupes handles.
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'olm-map' );
		wp_enqueue_style( 'olm-modal' );
		wp_enqueue_script( 'olm-map' );

		if ( ! $this->inline_css_added ) {
			$css = Olm_Settings::generate_css();
			if ( '' !== $css ) {
				wp_add_inline_style( 'olm-modal', $css );
			}
			$this->inline_css_added = true;
		}
	}

	/**
	 * [office_map] -- plots every published Office Location (or a specific
	 * `ids="1,2,3"` subset) on the stylized map.
	 *
	 * Examples:
	 *   [office_map]
	 *   [office_map ids="12,45"]
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids' => '',
			),
			$atts,
			self::TAG
		);

		$query_args = array(
			'post_type'      => Olm_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
		if ( ! empty( $ids ) ) {
			$query_args['post__in'] = array_values( $ids );
			$query_args['orderby']  = 'post__in';
		}

		$query   = new WP_Query( $query_args );
		$offices = array();

		foreach ( $query->posts as $post ) {
			$office = olm_get_office_data( $post->ID );
			if ( $office ) {
				$offices[] = $office;
			}
		}

		if ( empty( $offices ) ) {
			return current_user_can( 'edit_posts' )
				? '<p class="olm-map-placeholder">' . esc_html__( '[office_map]: no office locations with both a latitude and longitude were found. Add one under Office Map → Add New.', 'office-location-map' ) . '</p>'
				: '';
		}

		$this->enqueue_assets();

		$svg = olm_get_world_map_svg();

		ob_start();
		?>
		<div class="olm-map-wrap">
			<div class="olm-map">
				<?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted, plugin-bundled SVG markup. ?>
				<?php foreach ( $offices as $office ) : ?>
					<?php echo olm_render_office_marker( $office ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside olm_render_office_marker(). ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
