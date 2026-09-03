<?php
/**
 * "Map & Field Styling" admin page: site-wide colors for the stylized map
 * (land, sea, borders, markers) plus per-field typography (font, size,
 * weight, color) for every field shown in the office info modal, and basic
 * modal/image sizing. Applies to every [office_map] shortcode on the site.
 *
 * @package OfficeLocationMap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Olm_Settings.
 */
class Olm_Settings {

	const OPTION_NAME  = 'olm_display_settings';
	const OPTION_GROUP = 'olm_display_settings_group';
	const PAGE_SLUG    = 'olm-display-settings';

	/**
	 * The typography-controllable field groups: option key => [ label, CSS selector, description ].
	 *
	 * @var array<string,array<string,string>>
	 */
	private static $typography_groups = array(
		'office_name'    => array(
			'label'       => 'Office Name',
			'selector'    => '.olm-field-office_name',
			'description' => 'The office name shown at the top of the info modal.',
		),
		'address'        => array(
			'label'       => 'Full Address',
			'selector'    => '.olm-field-address',
			'description' => 'The office\'s full street address.',
		),
		'description'    => array(
			'label'       => 'Description',
			'selector'    => '.olm-field-description',
			'description' => 'The office description text.',
		),
		'contact_name'   => array(
			'label'       => 'Contact Person Name',
			'selector'    => '.olm-field-contact_name',
			'description' => 'The contact person\'s name.',
		),
		'contact_title'  => array(
			'label'       => 'Contact Person Title',
			'selector'    => '.olm-field-contact_title',
			'description' => 'The contact person\'s job title.',
		),
		'contact_phone'  => array(
			'label'       => 'Contact Person Phone',
			'selector'    => '.olm-field-contact_phone',
			'description' => 'The contact person\'s phone number.',
		),
		'contact_email'  => array(
			'label'       => 'Contact Person Email',
			'selector'    => '.olm-field-contact_email',
			'description' => 'The contact person\'s email address.',
		),
	);

	/**
	 * Singleton instance.
	 *
	 * @var Olm_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Olm_Settings
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Get the field-group definitions (used by the render + CSS methods).
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function typography_groups() {
		return self::$typography_groups;
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
			'color'       => '',
		);

		$defaults = array(
			'map'   => array(
				'land_color'         => '#4b6a72',
				'sea_color'          => '#cfe8f3',
				'border_color'       => '#ffffff',
				'border_width'       => 1,
				'marker_color'       => '#e0483e',
				'marker_hover_color' => '#b7362d',
				'marker_size'        => 16,
				'max_width'          => 960,
			),
			'modal' => array(
				'background_color' => '#ffffff',
				'border_radius'    => 8,
				'max_width'        => 480,
				'image_width'      => 96,
				'image_radius'     => 50,
			),
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

		foreach ( array( 'map', 'modal' ) as $key ) {
			if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
				$merged[ $key ] = wp_parse_args( $saved[ $key ], $defaults[ $key ] );
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
	 * Register the "Map & Field Styling" submenu page under Office Map.
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=' . Olm_Post_Type::POST_TYPE,
			__( 'Office Map Styling', 'office-location-map' ),
			__( 'Map & Field Styling', 'office-location-map' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue the color-picker assets on the settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( strpos( (string) $hook_suffix, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'olm-admin', OLM_PLUGIN_URL . 'admin/css/admin.css', array(), OLM_VERSION );
		wp_enqueue_script( 'olm-admin', OLM_PLUGIN_URL . 'admin/js/admin.js', array( 'wp-color-picker' ), OLM_VERSION, true );
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
	 * Sanitize a hex color, falling back to an empty string (meaning "use
	 * the plugin default") if the input isn't a valid hex color.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	private static function sanitize_color( $value ) {
		$value = sanitize_hex_color( (string) $value );
		return $value ? $value : '';
	}

	/**
	 * Sanitize the whole settings array on save.
	 *
	 * @param mixed $input Raw POSTed value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input           = is_array( $input ) ? $input : array();
		$defaults        = self::defaults();
		$sanitized       = array();
		$allowed_weights = array( '', '300', '400', '500', '600', '700', '800' );

		$map = isset( $input['map'] ) && is_array( $input['map'] ) ? $input['map'] : array();
		$sanitized['map'] = array(
			'land_color'         => self::sanitize_color( $map['land_color'] ?? '' ) ?: $defaults['map']['land_color'],
			'sea_color'          => self::sanitize_color( $map['sea_color'] ?? '' ) ?: $defaults['map']['sea_color'],
			'border_color'       => self::sanitize_color( $map['border_color'] ?? '' ) ?: $defaults['map']['border_color'],
			'border_width'       => isset( $map['border_width'] ) ? max( 0, min( 10, (float) $map['border_width'] ) ) : $defaults['map']['border_width'],
			'marker_color'       => self::sanitize_color( $map['marker_color'] ?? '' ) ?: $defaults['map']['marker_color'],
			'marker_hover_color' => self::sanitize_color( $map['marker_hover_color'] ?? '' ) ?: $defaults['map']['marker_hover_color'],
			'marker_size'        => isset( $map['marker_size'] ) ? max( 6, min( 48, absint( $map['marker_size'] ) ) ) : $defaults['map']['marker_size'],
			'max_width'          => isset( $map['max_width'] ) ? max( 240, min( 2400, absint( $map['max_width'] ) ) ) : $defaults['map']['max_width'],
		);

		$modal = isset( $input['modal'] ) && is_array( $input['modal'] ) ? $input['modal'] : array();
		$sanitized['modal'] = array(
			'background_color' => self::sanitize_color( $modal['background_color'] ?? '' ) ?: $defaults['modal']['background_color'],
			'border_radius'    => isset( $modal['border_radius'] ) ? max( 0, min( 60, absint( $modal['border_radius'] ) ) ) : $defaults['modal']['border_radius'],
			'max_width'        => isset( $modal['max_width'] ) ? max( 240, min( 1200, absint( $modal['max_width'] ) ) ) : $defaults['modal']['max_width'],
			'image_width'      => isset( $modal['image_width'] ) ? max( 32, min( 480, absint( $modal['image_width'] ) ) ) : $defaults['modal']['image_width'],
			'image_radius'     => isset( $modal['image_radius'] ) ? max( 0, min( 50, absint( $modal['image_radius'] ) ) ) : $defaults['modal']['image_radius'],
		);

		foreach ( array_keys( self::$typography_groups ) as $key ) {
			$group = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();

			$font_size = isset( $group['font_size'] ) ? trim( (string) $group['font_size'] ) : '';
			$font_size = ( '' !== $font_size && is_numeric( $font_size ) ) ? (string) max( 8, min( 96, (int) $font_size ) ) : '';

			$font_weight = isset( $group['font_weight'] ) ? (string) $group['font_weight'] : '';
			if ( ! in_array( $font_weight, $allowed_weights, true ) ) {
				$font_weight = '';
			}

			$sanitized[ $key ] = array(
				'font_family' => isset( $group['font_family'] ) ? self::sanitize_font_family( $group['font_family'] ) : '',
				'font_size'   => $font_size,
				'font_weight' => $font_weight,
				'color'       => self::sanitize_color( $group['color'] ?? '' ),
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
		<div class="wrap olm-settings-page">
			<h1><?php esc_html_e( 'Office Map Styling', 'office-location-map' ); ?></h1>
			<p><?php esc_html_e( 'These settings apply to every [office_map] shortcode on the site.', 'office-location-map' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<h2><?php esc_html_e( 'Map Colors', 'office-location-map' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="olm_map_land_color"><?php esc_html_e( 'Land color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'map', 'land_color', $settings['map']['land_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_sea_color"><?php esc_html_e( 'Sea color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'map', 'sea_color', $settings['map']['sea_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_border_color"><?php esc_html_e( 'Border color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'map', 'border_color', $settings['map']['border_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_border_width"><?php esc_html_e( 'Border width (px)', 'office-location-map' ); ?></label></th>
							<td><input type="number" step="0.5" id="olm_map_border_width" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[map][border_width]" class="small-text" min="0" max="10" value="<?php echo esc_attr( $settings['map']['border_width'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_marker_color"><?php esc_html_e( 'Marker color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'map', 'marker_color', $settings['map']['marker_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_marker_hover_color"><?php esc_html_e( 'Marker hover color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'map', 'marker_hover_color', $settings['map']['marker_hover_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_marker_size"><?php esc_html_e( 'Marker size (px)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'map', 'marker_size', $settings['map']['marker_size'], 6, 48 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_map_max_width"><?php esc_html_e( 'Map max width (px)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'map', 'max_width', $settings['map']['max_width'], 240, 2400 ); ?></td>
						</tr>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Info Modal', 'office-location-map' ); ?></h2>
				<p class="description"><?php esc_html_e( 'The popup that opens in the center of the screen when a marker is clicked or tapped.', 'office-location-map' ); ?></p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="olm_modal_background_color"><?php esc_html_e( 'Background color', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_color_input( 'modal', 'background_color', $settings['modal']['background_color'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_modal_border_radius"><?php esc_html_e( 'Corner radius (px)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'modal', 'border_radius', $settings['modal']['border_radius'], 0, 60 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_modal_max_width"><?php esc_html_e( 'Max width (px)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'modal', 'max_width', $settings['modal']['max_width'], 240, 1200 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_modal_image_width"><?php esc_html_e( 'Image size (px)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'modal', 'image_width', $settings['modal']['image_width'], 32, 480 ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="olm_modal_image_radius"><?php esc_html_e( 'Image corner radius (%)', 'office-location-map' ); ?></label></th>
							<td><?php $this->render_number_input( 'modal', 'image_radius', $settings['modal']['image_radius'], 0, 50 ); ?><p class="description"><?php esc_html_e( '0 = square, 50 = circle.', 'office-location-map' ); ?></p></td>
						</tr>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Field Typography', 'office-location-map' ); ?></h2>
				<?php foreach ( self::$typography_groups as $key => $group ) : ?>
					<h3><?php echo esc_html( $group['label'] ); ?></h3>
					<p class="description"><?php echo esc_html( $group['description'] ); ?></p>
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="olm_<?php echo esc_attr( $key ); ?>_font_family"><?php esc_html_e( 'Font family', 'office-location-map' ); ?></label></th>
								<td>
									<input
										type="text"
										id="olm_<?php echo esc_attr( $key ); ?>_font_family"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_family]"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'e.g. Georgia, serif', 'office-location-map' ); ?>"
										value="<?php echo esc_attr( $settings[ $key ]['font_family'] ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="olm_<?php echo esc_attr( $key ); ?>_font_size"><?php esc_html_e( 'Font size (px)', 'office-location-map' ); ?></label></th>
								<td>
									<input
										type="number"
										id="olm_<?php echo esc_attr( $key ); ?>_font_size"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_size]"
										class="small-text"
										min="8"
										max="96"
										value="<?php echo esc_attr( $settings[ $key ]['font_size'] ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="olm_<?php echo esc_attr( $key ); ?>_font_weight"><?php esc_html_e( 'Font weight', 'office-location-map' ); ?></label></th>
								<td>
									<select id="olm_<?php echo esc_attr( $key ); ?>_font_weight" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][font_weight]">
										<?php
										$weight_labels = array(
											''    => __( 'Theme default', 'office-location-map' ),
											'300' => __( '300 (Light)', 'office-location-map' ),
											'400' => __( '400 (Normal)', 'office-location-map' ),
											'500' => __( '500 (Medium)', 'office-location-map' ),
											'600' => __( '600 (Semibold)', 'office-location-map' ),
											'700' => __( '700 (Bold)', 'office-location-map' ),
											'800' => __( '800 (Extrabold)', 'office-location-map' ),
										);
										foreach ( $weight_labels as $value => $label ) :
											?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ]['font_weight'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="olm_<?php echo esc_attr( $key ); ?>_color"><?php esc_html_e( 'Text color', 'office-location-map' ); ?></label></th>
								<td><?php $this->render_color_input( $key, 'color', $settings[ $key ]['color'], true ); ?></td>
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
	 * Render a WP color-picker text input.
	 *
	 * @param string $group    Settings group key.
	 * @param string $field    Field key within the group.
	 * @param string $value    Current value.
	 * @param bool   $optional Whether an empty value is valid (theme default).
	 */
	private function render_color_input( $group, $field, $value, $optional = false ) {
		printf(
			'<input type="text" id="olm_%1$s_%2$s" name="%3$s[%1$s][%2$s]" class="olm-color-field" data-alpha="false" %4$s value="%5$s" />',
			esc_attr( $group ),
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			$optional ? 'data-default-color=""' : '',
			esc_attr( $value )
		);
	}

	/**
	 * Render a min/max-bounded number input.
	 *
	 * @param string $group Settings group key.
	 * @param string $field Field key within the group.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 */
	private function render_number_input( $group, $field, $value, $min, $max ) {
		printf(
			'<input type="number" id="olm_%1$s_%2$s" name="%3$s[%1$s][%2$s]" class="small-text" min="%4$d" max="%5$d" value="%6$s" />',
			esc_attr( $group ),
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			(int) $min,
			(int) $max,
			esc_attr( $value )
		);
	}

	/**
	 * Build the inline CSS reflecting the current settings, to be added
	 * via `wp_add_inline_style()` alongside the plugin's base stylesheets.
	 *
	 * @return string
	 */
	public static function generate_css() {
		$settings = self::get_settings();
		$map      = $settings['map'];
		$modal    = $settings['modal'];
		$css      = '';

		$css .= '.olm-map-wrap{max-width:' . (int) $map['max_width'] . 'px;}';
		$css .= '.olm-map-wrap .olm-sea{fill:' . $map['sea_color'] . ';}';
		$css .= '.olm-map-wrap .olm-land{fill:' . $map['land_color'] . ';stroke:' . $map['border_color'] . ';stroke-width:' . (float) $map['border_width'] . 'px;}';
		$css .= '.olm-marker__dot{width:' . (int) $map['marker_size'] . 'px;height:' . (int) $map['marker_size'] . 'px;background-color:' . $map['marker_color'] . ';box-shadow:0 0 0 4px ' . self::color_with_alpha( $map['marker_color'], 0.35 ) . ';}';
		$css .= '.olm-marker:hover .olm-marker__dot,.olm-marker:focus .olm-marker__dot,.olm-marker.is-active .olm-marker__dot{background-color:' . $map['marker_hover_color'] . ';box-shadow:0 0 0 6px ' . self::color_with_alpha( $map['marker_hover_color'], 0.35 ) . ';}';

		$css .= '.olm-modal__dialog{background-color:' . $modal['background_color'] . ';border-radius:' . (int) $modal['border_radius'] . 'px;max-width:' . (int) $modal['max_width'] . 'px;}';
		$css .= '.olm-modal__image{width:' . (int) $modal['image_width'] . 'px;height:' . (int) $modal['image_width'] . 'px;border-radius:' . (int) $modal['image_radius'] . '%;}';

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
			if ( '' !== $field['color'] ) {
				$rules[] = 'color:' . $field['color'] . ';';
			}

			if ( ! empty( $rules ) ) {
				$css .= $group['selector'] . '{' . implode( '', $rules ) . '}';
			}
		}

		return $css;
	}

	/**
	 * Convert a `#rrggbb` hex color to an `rgba()` string at the given
	 * opacity, for the marker's soft halo -- falls back to the hex color
	 * unchanged if it isn't a 6-digit hex value.
	 *
	 * @param string $hex   Hex color, e.g. "#e0483e".
	 * @param float  $alpha 0-1 opacity.
	 * @return string
	 */
	private static function color_with_alpha( $hex, $alpha ) {
		if ( ! preg_match( '/^#([0-9a-fA-F]{6})$/', (string) $hex, $matches ) ) {
			return $hex;
		}

		$rgb = sscanf( $matches[1], '%02x%02x%02x' );

		return sprintf( 'rgba(%d,%d,%d,%s)', $rgb[0], $rgb[1], $rgb[2], $alpha );
	}
}
