<?php
/**
 * [team_member_card] and [team_grid] shortcodes.
 *
 * Shortcodes are the one Team Roster for Divi surface that behaves
 * identically in Divi 4 (drop into a Text or Code module) and Divi 5
 * (same, or any block that renders shortcodes), and in plain WordPress
 * with no Divi at all -- unlike a Visual Builder module or a block, a
 * shortcode has no builder-specific registration API to target, so there
 * is nothing that can drift out of sync with whichever Divi version a
 * site happens to run.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Shortcodes.
 */
class Trd_Shortcodes {

	const CARD_TAG = 'team_member_card';
	const GRID_TAG = 'team_grid';

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Whether the Display Settings typography CSS has already been
	 * attached to the `trd-card` stylesheet on this request.
	 *
	 * @var bool
	 */
	private $inline_css_added = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Shortcodes
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
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register both shortcode tags.
	 */
	public function register_shortcodes() {
		add_shortcode( self::CARD_TAG, array( $this, 'render_card' ) );
		add_shortcode( self::GRID_TAG, array( $this, 'render_grid' ) );
	}

	/**
	 * Register (but do not enqueue) the card/modal assets. They're
	 * enqueued on demand from inside the shortcode callbacks below, so a
	 * page that never uses either shortcode never loads them, and pages
	 * that do get them regardless of which builder/module embedded the
	 * shortcode text.
	 */
	public function register_assets() {
		wp_register_style( 'trd-card', TRD_PLUGIN_URL . 'assets/css/card.css', array(), TRD_VERSION );
		// Depends on trd-card so it always prints after it: modal.css and
		// card.css both style .trd-modal__* selectors' shared classes, and
		// the Display Settings typography CSS (attached below, to
		// whichever handle prints last) needs to win the cascade over both
		// base stylesheets regardless of enqueue order elsewhere.
		wp_register_style( 'trd-modal', TRD_PLUGIN_URL . 'assets/css/modal.css', array( 'trd-card' ), TRD_VERSION );
		wp_register_script( 'trd-modal', TRD_PLUGIN_URL . 'assets/js/modal.js', array(), TRD_VERSION, true );
	}

	/**
	 * Enqueue the card + modal assets. Safe to call more than once per
	 * request (e.g. multiple shortcodes on one page); WP dedupes handles.
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'trd-card' );
		wp_enqueue_style( 'trd-modal' );
		wp_enqueue_script( 'trd-modal' );

		if ( ! $this->inline_css_added ) {
			$css = Trd_Settings::generate_css();
			if ( '' !== $css ) {
				// Attached to trd-modal (not trd-card): trd-modal is
				// registered as depending on trd-card, so it always prints
				// last, and its inline CSS needs to come after both base
				// stylesheets to win the cascade for every selector,
				// including the .trd-modal__* ones only modal.css defines.
				wp_add_inline_style( 'trd-modal', $css );
			}
			$this->inline_css_added = true;
		}
	}

	/**
	 * [team_member_card] -- either `id="123"` to pull an existing Team
	 * Member post, or manual fields for a one-off card. Enclosed content,
	 * if any, becomes the biography for the manual case (ignored when
	 * `id` is set, since the CPT's own content editor is the bio there).
	 *
	 * Examples:
	 *   [team_member_card id="123"]
	 *   [team_member_card name="Jane Doe" photo="https://example.com/jane.jpg"
	 *     job_title="CEO" company="Acme Inc" email="jane@acme.com"
	 *     phone="555-1234" link="https://linkedin.com/in/janedoe"]
	 *     Jane co-founded Acme in 2014...
	 *   [/team_member_card]
	 *
	 * @param array<string,string> $atts    Shortcode attributes.
	 * @param string                $content Enclosed content (manual-mode bio only).
	 * @return string
	 */
	public function render_card( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'id'            => 0,
				'name'          => '',
				'photo'         => '',
				'job_title'     => '',
				'company'       => '',
				'email'         => '',
				'phone'         => '',
				'link'          => '',
				'button_label'  => __( 'Read Bio', 'team-roster-for-divi' ),
			),
			$atts,
			self::CARD_TAG
		);

		$post_id = absint( $atts['id'] );

		if ( $post_id ) {
			$member = trd_get_team_member_data( $post_id );
		} else {
			$name = sanitize_text_field( $atts['name'] );

			$member = '' === $name ? null : array(
				'name'      => $name,
				'photo_url' => esc_url_raw( $atts['photo'] ),
				'job_title' => sanitize_text_field( $atts['job_title'] ),
				'company'   => sanitize_text_field( $atts['company'] ),
				'email'     => sanitize_email( $atts['email'] ),
				'phone'     => sanitize_text_field( $atts['phone'] ),
				'link'      => esc_url_raw( $atts['link'] ),
				'bio'       => wp_kses_post( wpautop( do_shortcode( trim( $content ) ) ) ),
			);
		}

		if ( ! $member ) {
			return current_user_can( 'edit_posts' )
				? '<p class="trd-card-placeholder">' . esc_html__( '[team_member_card]: set an id="" for an existing Team Member, or at least a name="" for a manual card.', 'team-roster-for-divi' ) . '</p>'
				: '';
		}

		$this->enqueue_assets();

		return '<div class="trd-team-member-card-shortcode">' . trd_render_team_card( $member, array( 'button_label' => sanitize_text_field( $atts['button_label'] ) ) ) . '</div>';
	}

	/**
	 * [team_grid] -- automatically lists Team Member posts as a
	 * responsive grid of cards.
	 *
	 * Examples:
	 *   [team_grid]
	 *   [team_grid mode="groups" groups="leadership,sales" sections="yes"]
	 *   [team_grid exclude="12,45" sort="title_asc" columns="4" columns_tablet="2" columns_mobile="1"]
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_grid( $atts ) {
		$defaults = Trd_Settings::get_settings();

		$atts = shortcode_atts(
			array(
				'mode'           => 'all', // 'all' | 'groups'.
				'groups'         => '', // comma-separated group slugs, used when mode="groups".
				'exclude'        => '', // comma-separated Team Member post IDs.
				'sort'           => 'menu_order', // 'menu_order' | 'title_asc' | 'title_desc'.
				'sections'       => 'no', // 'yes' to render each group as its own labeled section.
				// Column defaults come from Team Members → Display Settings;
				// pass columns="" / columns_tablet="" / columns_mobile=""
				// explicitly on the shortcode to override per instance.
				'columns'        => $defaults['columns_desktop'],
				'columns_tablet' => $defaults['columns_tablet'],
				'columns_mobile' => $defaults['columns_mobile'],
				'button_label'   => __( 'Read Bio', 'team-roster-for-divi' ),
				'card_bg'        => '',
				'card_color'     => '',
				'card_radius'    => 8,
			),
			$atts,
			self::GRID_TAG
		);

		$display_mode = 'groups' === $atts['mode'] ? 'groups' : 'all';

		$group_ids = array();
		if ( 'groups' === $display_mode && '' !== trim( $atts['groups'] ) ) {
			foreach ( array_filter( array_map( 'trim', explode( ',', $atts['groups'] ) ) ) as $slug ) {
				$term = get_term_by( 'slug', sanitize_title( $slug ), Trd_Taxonomy::TAXONOMY );
				if ( $term && ! is_wp_error( $term ) ) {
					$group_ids[] = $term->term_id;
				}
			}
		}

		$exclude_ids = array_filter( array_map( 'absint', explode( ',', $atts['exclude'] ) ) );

		$sort_order = in_array( $atts['sort'], array( 'menu_order', 'title_asc', 'title_desc' ), true )
			? $atts['sort']
			: 'menu_order';

		$render_attributes = array(
			'displayMode'    => $display_mode,
			'groupIds'       => $group_ids,
			'excludeIds'     => array_values( $exclude_ids ),
			'sortOrder'      => $sort_order,
			'groupSections'  => in_array( strtolower( (string) $atts['sections'] ), array( 'yes', 'true', '1' ), true ),
			'columns'        => absint( $atts['columns'] ) ?: 3,
			'columnsTablet'  => absint( $atts['columns_tablet'] ) ?: 2,
			'columnsMobile'  => absint( $atts['columns_mobile'] ) ?: 1,
			'buttonLabel'    => sanitize_text_field( $atts['button_label'] ),
			'cardBackground' => sanitize_text_field( $atts['card_bg'] ),
			'cardTextColor'  => sanitize_text_field( $atts['card_color'] ),
			'cardRadius'     => is_numeric( $atts['card_radius'] ) ? (int) $atts['card_radius'] : 8,
		);

		$this->enqueue_assets();

		return Trd_Team_Grid_Render::render( $render_attributes );
	}
}
