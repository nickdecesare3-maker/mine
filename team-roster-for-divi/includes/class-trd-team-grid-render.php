<?php
/**
 * Server-side rendering for the `team-roster-for-divi/team-grid` block:
 * queries Team Member posts (respecting manual drag order), optionally
 * splits them into per-group sections, and lays them out in a responsive
 * CSS grid of Team Member Cards.
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Team_Grid_Render.
 */
class Trd_Team_Grid_Render {

	/**
	 * Render the block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$attributes = wp_parse_args(
			$attributes,
			array(
				'displayMode'    => 'all', // 'all' | 'groups'.
				'groupIds'       => array(),
				'excludeIds'     => array(),
				'sortOrder'      => 'menu_order', // 'menu_order' | 'title_asc' | 'title_desc'.
				'groupSections'  => false,
				'columns'        => 3,
				'columnsTablet'  => 2,
				'columnsMobile'  => 1,
				'buttonLabel'    => __( 'Read Bio', 'team-roster-for-divi' ),
				'cardBackground' => '',
				'cardTextColor'  => '',
				'cardRadius'     => '',
			)
		);

		$wrapper_id = wp_unique_id( 'trd-grid-' );
		$columns    = max( 1, min( 6, absint( $attributes['columns'] ) ) );
		$tablet     = max( 1, min( 6, absint( $attributes['columnsTablet'] ) ) );
		$mobile     = max( 1, min( 6, absint( $attributes['columnsMobile'] ) ) );

		$body = $attributes['groupSections']
			? self::render_grouped( $attributes, $wrapper_id )
			: self::render_flat( $attributes, $wrapper_id );

		if ( '' === trim( wp_strip_all_tags( $body ) ) && false === strpos( $body, 'trd-card' ) ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<p class="trd-card-placeholder">' . esc_html__( 'Team Grid: no team members match the current filters yet.', 'team-roster-for-divi' ) . '</p>';
			}
			return '';
		}

		$style_vars = self::inline_style_vars( $attributes );

		ob_start();
		?>
		<style>
			#<?php echo esc_attr( $wrapper_id ); ?> .trd-grid {
				display: grid;
				grid-template-columns: repeat(<?php echo (int) $columns; ?>, 1fr);
			}
			@media (max-width: 980px) {
				#<?php echo esc_attr( $wrapper_id ); ?> .trd-grid {
					grid-template-columns: repeat(<?php echo (int) $tablet; ?>, 1fr);
				}
			}
			@media (max-width: 767px) {
				#<?php echo esc_attr( $wrapper_id ); ?> .trd-grid {
					grid-template-columns: repeat(<?php echo (int) $mobile; ?>, 1fr);
				}
			}
		</style>
		<div id="<?php echo esc_attr( $wrapper_id ); ?>" class="trd-team-grid-block"<?php echo $style_vars ? ' style="' . esc_attr( $style_vars ) . '"' : ''; ?>>
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped sub-templates. ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the `style="--trd-card-...` attribute value for card overrides.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	private static function inline_style_vars( $attributes ) {
		$vars = array();

		if ( ! empty( $attributes['cardBackground'] ) ) {
			$vars[] = '--trd-card-bg:' . sanitize_text_field( $attributes['cardBackground'] );
		}
		if ( ! empty( $attributes['cardTextColor'] ) ) {
			$vars[] = '--trd-card-color:' . sanitize_text_field( $attributes['cardTextColor'] );
		}
		if ( '' !== $attributes['cardRadius'] && null !== $attributes['cardRadius'] ) {
			$vars[] = '--trd-card-radius:' . absint( $attributes['cardRadius'] ) . 'px';
		}

		return implode( ';', $vars );
	}

	/**
	 * One flat grid of every matching team member.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string               $wrapper_id Unique wrapper id (unused here, kept for symmetry).
	 * @return string
	 */
	private static function render_flat( $attributes, $wrapper_id ) {
		$posts = self::query_members( $attributes );

		if ( empty( $posts ) ) {
			return '';
		}

		return '<div class="trd-grid">' . self::cards_html( $posts, $attributes ) . '</div>';
	}

	/**
	 * One labeled section per group, each containing its own grid.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string               $wrapper_id Unique wrapper id.
	 * @return string
	 */
	private static function render_grouped( $attributes, $wrapper_id ) {
		if ( 'groups' === $attributes['displayMode'] && ! empty( $attributes['groupIds'] ) ) {
			$term_ids = array_map( 'absint', (array) $attributes['groupIds'] );
			$terms    = array();
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, Trd_Taxonomy::TAXONOMY );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
		} else {
			$terms = get_terms(
				array(
					'taxonomy'   => Trd_Taxonomy::TAXONOMY,
					'hide_empty' => true,
				)
			);
			$terms = is_wp_error( $terms ) ? array() : $terms;
		}

		$html = '';

		foreach ( $terms as $term ) {
			$posts = self::query_members( $attributes, $term->term_id );

			if ( empty( $posts ) ) {
				continue;
			}

			$html .= '<div class="trd-grid-section">';
			$html .= '<h2 class="trd-grid-section__title">' . esc_html( $term->name ) . '</h2>';
			$html .= '<div class="trd-grid">' . self::cards_html( $posts, $attributes ) . '</div>';
			$html .= '</div>';
		}

		// Members with no group at all, only relevant when showing everything.
		if ( 'all' === $attributes['displayMode'] ) {
			$ungrouped = self::query_members( $attributes, 0, true );
			if ( ! empty( $ungrouped ) ) {
				$html .= '<div class="trd-grid-section">';
				$html .= '<h2 class="trd-grid-section__title">' . esc_html__( 'Other', 'team-roster-for-divi' ) . '</h2>';
				$html .= '<div class="trd-grid">' . self::cards_html( $ungrouped, $attributes ) . '</div>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	/**
	 * Run the WP_Query for either the flat grid or a single group's section.
	 *
	 * @param array<string,mixed> $attributes    Block attributes.
	 * @param int                  $term_id       Optional single group term id.
	 * @param bool                 $only_ungrouped Only return posts with no group term at all.
	 * @return WP_Post[]
	 */
	private static function query_members( $attributes, $term_id = 0, $only_ungrouped = false ) {
		switch ( $attributes['sortOrder'] ) {
			case 'title_asc':
				$orderby = 'title';
				$order   = 'ASC';
				break;
			case 'title_desc':
				$orderby = 'title';
				$order   = 'DESC';
				break;
			default:
				$orderby = 'menu_order title';
				$order   = 'ASC';
				break;
		}

		$args = array(
			'post_type'      => Trd_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => $orderby,
			'order'          => $order,
			'no_found_rows'  => true,
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $attributes['excludeIds'] ) ) {
			$args['post__not_in'] = array_map( 'absint', (array) $attributes['excludeIds'] );
		}

		if ( $only_ungrouped ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Trd_Taxonomy::TAXONOMY,
					'operator' => 'NOT EXISTS',
				),
			);
		} elseif ( $term_id ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Trd_Taxonomy::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			);
		} elseif ( 'groups' === $attributes['displayMode'] && ! empty( $attributes['groupIds'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Trd_Taxonomy::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) $attributes['groupIds'] ),
					'operator' => 'IN',
				),
			);
		}

		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Render each queried post as a card.
	 *
	 * @param WP_Post[]            $posts      Team member posts.
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	private static function cards_html( $posts, $attributes ) {
		$html = '';

		foreach ( $posts as $post ) {
			$member = trd_get_team_member_data( $post->ID );
			if ( $member ) {
				$html .= trd_render_team_card( $member, array( 'button_label' => $attributes['buttonLabel'] ) );
			}
		}

		return $html;
	}
}
