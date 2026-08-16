<?php
/**
 * "Display Settings" admin page: site-wide typography (font, size, weight,
 * justification) for each part of a Team Member Card/bio, and the default
 * Team Grid column counts. Applies to every [team_member_card] and
 * [team_grid] shortcode on the site, since a settings page -- not a
 * builder module -- is the right place for a sitewide default that has to
 * look the same however the shortcode gets embedded (Divi 4, Divi 5, or
 * neither).
 *
 * @package TeamRosterForDivi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Trd_Settings.
 */
class Trd_Settings {

	const OPTION_NAME  = 'trd_display_settings';
	const OPTION_GROUP = 'trd_display_settings_group';
	const PAGE_SLUG    = 'trd-display-settings';

	/**
	 * The typography-controllable field groups: option key => [ label, CSS selector(s), description ].
	 *
	 * @var array<string,array<string,string>>
	 */
	private static $typography_groups = array(
		'name'            => array(
			'label'       => 'Name Heading',
			'selector'    => '.trd-card__name, .trd-modal__name',
			'description' => 'The team member\'s name, shown on the card and at the top of the bio modal.',
		),
		'section_heading' => array(
			'label'       => 'Group Section Headings',
			'selector'    => '.trd-grid-section__title',
			'description' => 'The group name heading shown above each section when a Team Grid is split into group sections.',
		),
		'meta'            => array(
			'label'       => 'Job Title & Company',
			'selector'    => '.trd-card__meta, .trd-modal__meta',
			'description' => 'The job title / company line under the name.',
		),
		'contact'         => array(
			'label'       => 'Contact Info',
			'selector'    => '.trd-card__contact',
			'description' => 'The email, phone and link list on the card.',
		),
		'bio'             => array(
			'label'       => 'Biography',
			'selector'    => '.trd-modal__bio',
			'description' => 'The biography text inside the Read Bio modal.',
		),
	);

	/**
	 * Singleton instance.
	 *
	 * @var Trd_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Trd_Settings
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
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * The full set of default values, used whenever an option/sub-key is
	 * missing (e.g. first activation, or a settings shape upgrade).
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		$typography_default = array(
			'font_family' => '',
			'font_size'   => '',
			'font_weight' => '',
			'text_align'  => 'left',
		);

		$defaults = array(
			'columns_desktop' => 3,
			'columns_tablet'  => 2,
			'columns_mobile'  => 1,
		);

		foreach ( array_keys( self::$typography_groups ) as $key ) {
			$defaults[ $key ] = $typography_default;
		}

		return $defaults;
	}

	/**
	 * Get the current (saved + defaulted) settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		return self::merge_with_defaults( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Deep-merge saved values over the defaults so a partially-saved or
	 * pre-upgrade option never produces missing-key notices.
	 *
	 * @param array<string,mixed> $saved Raw saved option value.
	 * @return array<string,mixed>
	 */
	private static function merge_with_defaults( $saved ) {
		$defaults = self::defaults();
		$merged   = $defaults;

		foreach ( array( 'columns_desktop', 'columns_tablet', 'columns_mobile' ) as $key ) {
			if ( isset( $saved[ $key ] ) ) {
				$merged[ $key ] = $saved[ $key ];
			}
		}

		foreach ( array_keys( self::$typography_groups ) as $key ) {
			if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
				$merged[ $key ] = wp_parse_args( $saved[ $key ], $defaults[ $key ] );
			}
		}

		return $merged;
	}

	/**
	 * Register the "Display Settings" submenu page under Team Members.
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=' . Trd_Post_Type::POST_TYPE,
			__( 'Team Roster Display Settings', 'team-roster-for-divi' ),
			__( 'Display Settings', 'team-roster-for-divi' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option with its sanitize callback.
	 */
	public function register_setting() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize the whole settings array on save.
	 *
	 * @param mixed $input Raw POSTed value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$sanitized  = array();
		$allowed_weights = array( '', '300', '400', '500', '600', '700', '800' );
		$allowed_aligns  = array( 'left', 'center', 'right', 'justify' );

		foreach ( array( 'columns_desktop', 'columns_tablet', 'columns_mobile' ) as $key ) {
			$value             = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
			$sanitized[ $key ] = max( 1, min( 6, $value ?: 1 ) );
		}

		foreach ( array_keys( self::$typography_groups ) as $key ) {
			$group = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();

			$font_size = isset( $group['font_size'] ) ? trim( (string) $group['font_size'] ) : '';
			$font_size = ( '' !== $font_size && is_numeric( $font_size ) ) ? (string) max( 8, min( 72, (int) $font_size ) ) : '';

			$font_weight = isset( $group['font_weight'] ) ? (string) $group['font_weight'] : '';
			if ( ! in_array( $font_weight, $allowed_weights, true ) ) {
				$font_weight = '';
			}

			$text_align = isset( $group['text_align'] ) ? (string) $group['text_align'] : 'left';
			if ( ! in_array( $text_align, $allowed_aligns, true ) ) {
				$text_align = 'left';
			}

			$sanitized[ $key ] = array(
				'font_family' => isset( $group['font_family'] ) ? self::sanitize_font_family( $group['font_family'] ) : '',
				'font_size'   => $font_size,
				'font_weight' => $font_weight,
				'text_align'  => $text_align,
			);
		}

		return $sanitized;
	}

	/**
	 * Restrict a font-family value to characters that can appear in a CSS
	 * `font-family` declaration (names, stacks, quoted names) and nothing
	 * that could break out of the declaration.
	 *
	 * @param string $value Raw font-family input.
	 * @return string
	 */
	public static function sanitize_font_family( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		return trim( preg_replace( '/[^A-Za-z0-9 ,\-\'"]/', '', $value ) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get_settings();
		?>
		<div class="wrap trd-settings-page">
			<h1><?php esc_html_e( 'Team Roster Display Settings', 'team-roster-for-divi' ); ?></h1>
			<p><?php esc_html_e( 'These defaults apply everywhere Team Member Card and Team Grid shortcodes are used. Leave a field blank to inherit your theme\'s default.', 'team-roster-for-divi' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<h2><?php esc_html_e( 'Team Grid Columns', 'team-roster-for-divi' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Default number of cards per row. A [team_grid] shortcode can still override these with its own columns / columns_tablet / columns_mobile attributes.', 'team-roster-for-divi' ); ?></p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="trd_columns_desktop"><?php esc_html_e( 'Columns (desktop)', 'team-roster-for-divi' ); ?></label></th>
							<td><?php $this->render_number_input( 'columns_desktop', $settings['columns_desktop'], 1, 6 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="trd_columns_tablet"><?php esc_html_e( 'Columns (tablet, ≤ 980px)', 'team-roster-for-divi' ); ?></label></th>
							<td><?php $this->render_number_input( 'columns_tablet', $settings['columns_tablet'], 1, 6 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="trd_columns_mobile"><?php esc_html_e( 'Columns (mobile, ≤ 767px)', 'team-roster-for-divi' ); ?></label></th>
							<td><?php $this->render_number_input( 'columns_mobile', $settings['columns_mobile'], 1, 6 ); ?></td>
						</tr>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Typography', 'team-roster-for-divi' ); ?></h2>
				<?php foreach ( self::$typography_groups as $key => $group ) : ?>
					<h3><?php echo esc_html( $group['label'] ); ?></h3>
					<p class="description"><?php echo esc_html( $group['description'] ); ?></p>
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="trd_<?php echo esc_attr( $key ); ?>_font_family"><?php esc_html_e( 'Font family', 'team-roster-for-divi' ); ?></label></th>
								<td>
									<input
										type="text"
										id="trd_<?php echo esc_attr( $key ); ?>_font_family"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_family]"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'e.g. Georgia, serif', 'team-roster-for-divi' ); ?>"
										value="<?php echo esc_attr( $settings[ $key ]['font_family'] ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="trd_<?php echo esc_attr( $key ); ?>_font_size"><?php esc_html_e( 'Font size (px)', 'team-roster-for-divi' ); ?></label></th>
								<td>
									<input
										type="number"
										id="trd_<?php echo esc_attr( $key ); ?>_font_size"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_size]"
										class="small-text"
										min="8"
										max="72"
										value="<?php echo esc_attr( $settings[ $key ]['font_size'] ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="trd_<?php echo esc_attr( $key ); ?>_font_weight"><?php esc_html_e( 'Font weight', 'team-roster-for-divi' ); ?></label></th>
								<td>
									<select id="trd_<?php echo esc_attr( $key ); ?>_font_weight" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_weight]">
										<?php
										$weight_labels = array(
											''    => __( 'Theme default', 'team-roster-for-divi' ),
											'300' => __( '300 (Light)', 'team-roster-for-divi' ),
											'400' => __( '400 (Normal)', 'team-roster-for-divi' ),
											'500' => __( '500 (Medium)', 'team-roster-for-divi' ),
											'600' => __( '600 (Semibold)', 'team-roster-for-divi' ),
											'700' => __( '700 (Bold)', 'team-roster-for-divi' ),
											'800' => __( '800 (Extrabold)', 'team-roster-for-divi' ),
										);
										foreach ( $weight_labels as $value => $label ) :
											?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ]['font_weight'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="trd_<?php echo esc_attr( $key ); ?>_text_align"><?php esc_html_e( 'Justification', 'team-roster-for-divi' ); ?></label></th>
								<td>
									<select id="trd_<?php echo esc_attr( $key ); ?>_text_align" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][text_align]">
										<?php
										$align_labels = array(
											'left'    => __( 'Left', 'team-roster-for-divi' ),
											'center'  => __( 'Center', 'team-roster-for-divi' ),
											'right'   => __( 'Right', 'team-roster-for-divi' ),
											'justify' => __( 'Justify', 'team-roster-for-divi' ),
										);
										foreach ( $align_labels as $value => $label ) :
											?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ]['text_align'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a min/max-bounded number input for the columns fields.
	 *
	 * @param string $key   Settings key.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 */
	private function render_number_input( $key, $value, $min, $max ) {
		printf(
			'<input type="number" id="trd_%1$s" name="%2$s[%1$s]" class="small-text" min="%3$d" max="%4$d" value="%5$d" />',
			esc_attr( $key ),
			esc_attr( self::OPTION_NAME ),
			(int) $min,
			(int) $max,
			(int) $value
		);
	}

	/**
	 * Build the inline CSS reflecting the current settings, to be added
	 * via `wp_add_inline_style()` alongside the plugin's base stylesheet.
	 *
	 * @return string
	 */
	public static function generate_css() {
		$settings = self::get_settings();
		$css      = '';

		foreach ( self::$typography_groups as $key => $group ) {
			$rules = array();
			$field = $settings[ $key ];

			if ( '' !== $field['font_family'] ) {
				$rules[] = 'font-family:' . $field['font_family'] . ';';
			}
			if ( '' !== $field['font_size'] ) {
				$rules[] = 'font-size:' . (int) $field['font_size'] . 'px;';
			}
			if ( '' !== $field['font_weight'] ) {
				$rules[] = 'font-weight:' . (int) $field['font_weight'] . ';';
			}
			if ( '' !== $field['text_align'] ) {
				$rules[] = 'text-align:' . $field['text_align'] . ';';
			}

			if ( ! empty( $rules ) ) {
				$css .= $group['selector'] . '{' . implode( '', $rules ) . '}';
			}
		}

		return $css;
	}
}
