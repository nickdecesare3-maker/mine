<?php
/**
 * Registers the Team Member Card and Team Grid Divi 5 / Gutenberg blocks.
 *
 * Divi 5 rebuilt its Visual Builder on the WordPress block editor, so
 * "native Divi 5 modules" are, at the API level, WordPress blocks: this
 * class registers standard dynamic blocks via `register_block_type()`,
 * which places them in Divi 5's block inserter/Visual Builder exactly like
 * any first-party Divi module, while still working in plain Gutenberg (or
 * inside Divi 4 via its block-editor fallback) with no Divi dependency.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Blocks.
 */
class Trd_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Blocks
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
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register both blocks from their block.json metadata.
	 */
	public function register_blocks() {
		$card_dir = TRD_PLUGIN_DIR . 'modules/team-member-card';
		$grid_dir = TRD_PLUGIN_DIR . 'modules/team-grid';

		if ( file_exists( $card_dir . '/block.json' ) ) {
			register_block_type(
				$card_dir,
				array( 'render_callback' => array( $this, 'render_team_member_card' ) )
			);
		}

		if ( file_exists( $grid_dir . '/block.json' ) ) {
			register_block_type(
				$grid_dir,
				array( 'render_callback' => array( $this, 'render_team_grid' ) )
			);
		}
	}

	/**
	 * Server-side render for `team-roster-for-divi/team-member-card`.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_team_member_card( $attributes ) {
		$member = $this->resolve_card_member( $attributes );

		if ( ! $member ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<p class="trd-card-placeholder">' . esc_html__( 'Team Member Card: choose a team member or fill in the fields manually.', 'team-roster-for-divi' ) . '</p>';
			}
			return '';
		}

		$button_label = ! empty( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : __( 'Read Bio', 'team-roster-for-divi' );

		return '<div class="trd-team-member-card-block">' . trd_render_team_card( $member, array( 'button_label' => $button_label ) ) . '</div>';
	}

	/**
	 * Build the display-ready member array for the card block, from
	 * either the selected CPT post or the manually entered attributes.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return array<string,mixed>|null
	 */
	private function resolve_card_member( $attributes ) {
		$mode = isset( $attributes['mode'] ) ? $attributes['mode'] : 'select';

		if ( 'select' === $mode ) {
			$post_id = ! empty( $attributes['teamMemberId'] ) ? absint( $attributes['teamMemberId'] ) : 0;
			return $post_id ? trd_get_team_member_data( $post_id ) : null;
		}

		$name = isset( $attributes['manualName'] ) ? sanitize_text_field( $attributes['manualName'] ) : '';

		if ( '' === $name ) {
			return null;
		}

		return array(
			'name'      => $name,
			'photo_url' => isset( $attributes['manualPhotoUrl'] ) ? esc_url_raw( $attributes['manualPhotoUrl'] ) : '',
			'job_title' => isset( $attributes['manualJobTitle'] ) ? sanitize_text_field( $attributes['manualJobTitle'] ) : '',
			'company'   => isset( $attributes['manualCompany'] ) ? sanitize_text_field( $attributes['manualCompany'] ) : '',
			'email'     => isset( $attributes['manualEmail'] ) ? sanitize_email( $attributes['manualEmail'] ) : '',
			'phone'     => isset( $attributes['manualPhone'] ) ? sanitize_text_field( $attributes['manualPhone'] ) : '',
			'link'      => isset( $attributes['manualLink'] ) ? esc_url_raw( $attributes['manualLink'] ) : '',
			'bio'       => isset( $attributes['manualBio'] ) ? wp_kses_post( $attributes['manualBio'] ) : '',
		);
	}

	/**
	 * Server-side render for `team-roster-for-divi/team-grid`.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_team_grid( $attributes ) {
		return Trd_Team_Grid_Render::render( $attributes );
	}
}
