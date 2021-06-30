<?php
/**
 * This class handles caching of customizer theme mods.
 *
 * @package JupiterX\Framework\API\Customizer
 *
 * @since 1.15.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizer theme mods caching.
 *
 * @since 1.15.0
 * @ignore
 * @access private
 *
 * @package JupiterX\Framework\API\Customizer
 */
final class JupiterX_Customizer_Cache {

	/**
	 * Theme mods storage.
	 *
	 * @var array
	 */
	public $theme_mods = [];

	/**
	 * Construct the class.
	 *
	 * @since 1.15.0
	 */
	public function __construct() {
		add_action( 'jupiterx_control_panel_settings_customizer_cache', [ $this, 'settings_html' ] );

		if ( '1' !== jupiterx_get_option( 'customizer_cache', '1' ) ) {
			return;
		}

		$slug = get_option( 'stylesheet' );

		add_filter( "pre_update_option_theme_mods_$slug", [ $this, 'clear_cache' ], 5, 1 );
		add_filter( "option_theme_mods_$slug", [ $this, 'set_cache' ], 5 );
		add_filter( "pre_option_theme_mods_$slug", [ $this, 'get_cache' ], 5 );
	}

	/**
	 * Set cache.
	 *
	 * Ensured that all hooks are executed during the first run of the `get_theme_mod` function.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/option_option
	 *
	 * @param array $value Serialized theme mods.
	 *
	 * @return array Theme mods value.
	 */
	public function set_cache( $value ) {
		// Save cache.
		$this->theme_mods = $value;

		return $value;
	}

	/**
	 * Get cache.
	 *
	 * Short-circuit pre option filter when theme mods are set.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/pre_option_option
	 *
	 * @since 1.15.0
	 *
	 * @return array|boolean Theme mods when cache exists and false if not.
	 */
	public function get_cache() {
		if ( ! empty( $this->theme_mods ) ) {
			return $this->theme_mods;
		}

		return false;
	}

	/**
	 * Clear cache.
	 *
	 * Clear cache if theme mods are updated.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/update_option_option/
	 *
	 * @since 1.15.0
	 *
	 * @param array $value unserialized theme mods.
	 *
	 * @return array|boolean Theme mods when cache exists and false if not.
	 */
	public function clear_cache( $value ) {
		$this->theme_mods = [];

		return $value;
	}

	/**
	 * Render Customizer Cache settings on control panel.
	 *
	 * @since 1.15.0
	 */
	public function settings_html() {
		$checked = jupiterx_get_option( 'customizer_cache', '1' );

		if ( '1' === $checked ) {
			$checked = true;
		}
		?>
		<div class="form-group col-md-6">
			<label for="jupiterx-cp-settings-customizer-cache"><?php esc_html_e( 'Customizer Cache', 'jupiterx-core' ); ?></label>
			<input type="hidden" name="jupiterx_customizer_cache" value="0">
			<div class="jupiterx-switch">
				<input type="checkbox" id="jupiterx-cp-settings-customizer-cache-enabled" name="jupiterx_customizer_cache" value="1" <?php checked( $checked, true ); ?>>
				<label for="jupiterx-cp-settings-customizer-cache-enabled"></label>
			</div>
			<small class="form-text text-muted"><?php esc_html_e( 'Enable Customizer Cache to improve page load time.', 'jupiterx-core' ); ?></small>
		</div>
		<?php
	}
}

// Initialize.
new JupiterX_Customizer_Cache();
