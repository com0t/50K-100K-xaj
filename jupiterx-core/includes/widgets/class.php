<?php
/**
 * Jupiter Widgets main class.
 *
 * This class is extendable and Jupiter widgets are generated by extending this class.
 * Also it is possible to add custom controls for widgets by extending this class.
 *
 * @package JupiterX_Core\Widgets
 *
 * @since 1.0.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */

if ( ! class_exists( 'JupiterX_Widget' ) ) {
	/**
	 * Extends WP_Widget to add custom controls.
	 *
	 * @since   1.0.0
	 * @ignore
	 *
	 * @package JupiterX_Core\Widgets
	 */
	class JupiterX_Widget extends WP_Widget {


		/**
		 * Jupiter widget controls.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $fields = [
			'text'     => 'text',
			'number'   => 'text',
			'url'      => 'text',
			'checkbox' => 'checkbox',
			'divider'  => 'divider',
			'select'   => 'select',
			'select2'  => 'select2',
			'color'    => 'color_picker',
			'flexible' => 'flexible',
		];

		/**
		 * Widget slug.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $base_id;

		/**
		 * Default values for widget controls.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $defaults = [];

		/**
		 * Sets up the widget.
		 *
		 * @since 1.0.0
		 *
		 * @param string $base_id Widget base id.
		 * @param string $name Widget name (label).
		 * @param array  $args Widget props.
		 *
		 * @return void
		 */
		public function __construct( $base_id = '', $name = '', $args = [] ) {

			parent::__construct( $base_id, $name, $args );

			$this->base_id = $base_id;

			$this->settings = isset( $args['settings'] ) ? $args['settings'] : [];

			add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 30 );
			add_action( 'customize_controls_enqueue_scripts', [ $this, 'assets' ], 30 );
			add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'scripts' ] );
			add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'styles' ] );
			$this->parse_args();
			$this->defaults();
		}

		/**
		 * Set default values. Defaults are used in form method to set default values in fields.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function defaults() {
			foreach ( $this->settings as $setting ) {
				$this->defaults[ $setting['name'] ] = $setting['default'];
			}
		}

		/**
		 * Parse settings to avoid missing required params.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function parse_args() {
			$parsed_settings = [];

			foreach ( $this->settings as $setting ) {
				$parsed_settings[] = wp_parse_args( $setting, [
					'type'      => '',
					'name'      => '',
					'label'     => '',
					'atts'      => [],
					'options'   => [],
					'default'   => '',
					'condition' => [],
				] );
			}

			$this->settings = $parsed_settings;
		}

		/**
		 * Enqueue needed control scripts.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function scripts() {
			$base_folder = version_compare( JUPITERX_VERSION, '1.9.2', '<=' ) ? 'admin/' : '';

			wp_enqueue_script( 'jupiterx-widget-controls', JUPITERX_ASSETS_URL . 'dist/js/' . $base_folder . 'widget-controls' . JUPITERX_MIN_JS . '.js', [ 'jquery', 'wp-color-picker' ], JUPITERX_VERSION, false );

			wp_enqueue_script( 'jupiterx-jquery-conditioner', JUPITERX_ASSETS_URL . 'dist/js/jquery.conditioner.js', [ 'jquery' ], JUPITERX_VERSION, false );

			if ( ! wp_script_is( 'jquery-elementor-select2', 'enqueued' ) ) {
				wp_enqueue_script( 'jupiterx-select2', JUPITERX_ADMIN_URL . 'assets/lib/select2/select2.full' . JUPITERX_MIN_JS . '.js', [ 'jquery' ], JUPITERX_VERSION, false );
			}
		}

		/**
		 * Enqueue needed control styles.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function styles() {
			$is_elementor_edit_mode = false;

			if ( class_exists( 'Elementor\Plugin' ) ) {
				$elementor = \Elementor\Plugin::instance();

				$is_elementor_edit_mode = $elementor->editor->is_edit_mode();
			}

			$base_folder = version_compare( JUPITERX_VERSION, '1.9.2', '<=' ) ? 'admin/' : '';

			wp_enqueue_style( 'jupiterx-widget-controls', JUPITERX_ASSETS_URL . 'dist/css/' . $base_folder . 'widget-controls' . JUPITERX_MIN_CSS . '.css', [ 'wp-color-picker' ], JUPITERX_VERSION );

			if ( ! wp_style_is( 'select2', 'enqueued' ) && ! $is_elementor_edit_mode ) {
				wp_enqueue_style( 'jupiterx-select2', JUPITERX_ADMIN_URL . 'assets/lib/select2/select2' . JUPITERX_MIN_CSS . '.css', [], JUPITERX_VERSION );
			}
		}

		/**
		 * Enqueue assets only in widget screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $hook Screen hook.
		 *
		 * @return void
		 */
		public function assets( $hook ) {
			if ( 'widgets.php' !== $hook ) {
				return;
			}

			$this->scripts();
			$this->styles();
		}

		/**
		 * Outputs the content of the widget.
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Widget instance.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
		 */
		public function widget( $args, $instance ) {}

		/**
		 * Outputs the options form on admin.
		 *
		 * @param array $instance The widget $instance.
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, $this->defaults );

			foreach ( $this->settings as $field ) {
				$attributes = [
					'class' => 'jupiterx-widget-control jupiterx-control-' . esc_attr( $field['type'] ),
				];

				$condition_setting = isset( $field['condition']['setting'] ) ? $field['condition']['setting'] : '';
				$condition_value   = isset( $field['condition']['value'] ) ? $field['condition']['value'] : '';

				if ( $condition_setting ) {
					$attributes ['data-condr-input']  = '(closest::.widget-inside)(find::.' . $condition_setting . ')';
					$attributes ['data-condr-value']  = $condition_value;
					$attributes ['data-condr-action'] = 'simple?show:hide';
					$attributes ['data-condr-events'] = 'change keyup blur';
				}

				jupiterx_open_markup_e( 'jupiterx_widget_control_wrapper', 'div', $attributes );

				if ( method_exists( $this, $this->fields[ $field['type'] ] ) ) {
					$fields_generator = $this->fields[ $field['type'] ];
					call_user_func_array( [ $this, $fields_generator ], [ $instance, $field ] );
				}

				jupiterx_close_markup_e( 'jupiterx_widget_control_wrapper', 'div' );
			}
		}

		/**
		 * Generate text field.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function text( $instance, $field ) {
			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			$value = isset( $instance[ $field['name'] ] ) ? $instance[ $field['name'] ] : '';

			jupiterx_open_markup_e( 'jupiterx_widget_control_text_label', 'label', [ 'for' => $field_id ] );

				echo esc_html( $field['label'] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_text_label', 'label' );

			$attributes = [
				'class'        => 'widefat ' . esc_attr( $field['name'] ),
				'id'           => $field_id,
				'name'         => $field_name,
				'type'         => $field['type'],
				'value'        => $value,
				'data-setting' => $field['name'],
			];

			// Merge & prevent overriding $attributes elements.
			$attributes = wp_parse_args( $attributes, $field['atts'] );

			jupiterx_selfclose_markup_e( 'jupiterx_widget_control_text_input', 'input', $attributes );
		}

		/**
		 * Generate checkbox field.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function checkbox( $instance, $field ) {
			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			$value = isset( $instance[ $field['name'] ] ) ? (bool) $instance[ $field['name'] ] : false;

			$attributes = [
				'class'        => 'checkbox ' . esc_attr( $field['name'] ),
				'id'           => $field_id,
				'name'         => $field_name,
				'type'         => 'checkbox',
			];

			if ( $value ) {
				$attributes['checked'] = 'checked';
			}

			jupiterx_selfclose_markup_e( 'jupiterx_widget_control_text_input', 'input', $attributes );

			jupiterx_open_markup_e( 'jupiterx_widget_control_text_label', 'label', [ 'for' => $field_id ] );

				echo esc_html( $field['label'] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_text_label', 'label' );
		}

		/**
		 * Generate select field.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function select( $instance, $field ) {
			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			$value = isset( $instance[ $field['name'] ] ) ? $instance[ $field['name'] ] : false;

			jupiterx_open_markup_e( 'jupiterx_widget_control_select_label', 'label', [ 'for' => $field_id ] );

				echo esc_html( $field['label'] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_select_label', 'label' );

			jupiterx_open_markup_e( 'jupiterx_widget_control_select', 'select', [
				'class' => 'widefat ' . esc_attr( $field['name'] ),
				'id'    => $field_id,
				'name'  => $field_name,
			] );

			// Get options from a function. Used for getting taxonomies from Customizer_Utils class.
			$field['options'] = apply_filters( "jupiterx_{$field['name']}_choices", $field['options'] );

			foreach ( $field['options'] as $key => $label ) {
				$attributes = [ 'value' => $key ];

				// Check for selected field.
				if ( $value === $key ) {
					$attributes['selected'] = 'selected';
				}

				jupiterx_open_markup_e( 'jupiterx_widget_control_select_option', 'option', $attributes );

					echo esc_html( $label );

				jupiterx_close_markup_e( 'jupiterx_widget_control_select_option', 'option' );
			}

			jupiterx_close_markup_e( 'jupiterx_widget_control_select', 'select' );
		}


		/**
		 * Generate select2 field.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function select2( $instance, $field ) {
			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			$value = isset( $instance[ $field['name'] ] ) ? $instance[ $field['name'] ] : false;

			jupiterx_open_markup_e( 'jupiterx_widget_control_select2_label', 'label', [ 'for' => $field_id ] );

				echo esc_html( $field['label'] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_select2_label', 'label' );

			jupiterx_open_markup_e( 'jupiterx_widget_control_select2', 'select', [
				'class'        => 'widefat jupiterx-select2 ' . esc_attr( $field['name'] ),
				'id'           => $field_id,
				'name'         => $field_name . '[]',
				'multiple'     => 'multiple',
				'style'        => 'width:100%',
				'data-setting' => $field['name'],
			] );

			// Get options from a function. Used for getting taxonomies from Customizer_Utils class.
			$field['options'] = apply_filters( "jupiterx_{$field['name']}_choices", $field['options'] );

			foreach ( $field['options'] as $key => $label ) {
				$attributes = [ 'value' => $key ];

				// Check for selected fields.
				if ( in_array( $key, (array) $value ) ) { // @phpcs:ignore
					$attributes['selected'] = 'selected';
				}

				jupiterx_open_markup_e( 'jupiterx_widget_control_select2_option', 'option', $attributes );

					echo esc_html( $label );

				jupiterx_close_markup_e( 'jupiterx_widget_control_select2_option', 'option' );
			}

			jupiterx_close_markup_e( 'jupiterx_widget_control_select2', 'select' );
		}

		/**
		 * Generate color picker field.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function color_picker( $instance, $field ) {

			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			$value = isset( $instance[ $field['name'] ] ) ? $instance[ $field['name'] ] : '';

			jupiterx_open_markup_e( 'jupiterx_widget_control_text_label', 'label', [ 'for' => $field_id ] );

				echo esc_html( $field['label'] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_text_label', 'label' );

			jupiterx_selfclose_markup_e( 'jupiterx_widget_control_color_input', 'input', [
				'class'             => 'widefat jupiterx-color-picker ' . esc_attr( $field['name'] ),
				'data-alpha'        => 'true',
				'id'                => $field_id,
				'name'              => $field_name,
				'type'              => 'text',
				'value'             => $value,
				'data-setting'      => $field['name'],
			] );
		}

		/**
		 * Generates flexible field.
		 *
		 * @since 1.0.0
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @return void
		 */
		public function flexible( $instance, $field ) {
			$field_id   = $this->get_field_id( $field['name'] );
			$field_name = $this->get_field_name( $field['name'] );

			if ( isset( $instance['save_trigger'] ) ) {
				unset( $instance['save_trigger'] );
			}

			$input_type = isset( $field['input_type'] ) ? $field['input_type'] : 'text';
			$values     = isset( $instance[ $field['name'] ] ) ? $instance[ $field['name'] ] : [];

			$field['options'] = apply_filters( "jupiterx_{$field['name']}_choices", $field['options'] );

			jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_wrapper', 'div', 'class=jupiterx-flexible-control-wrapper' );

				jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_controls', 'div', 'class=jupiterx-flexible-controls' );

					jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_label', 'label' );

						echo esc_html( $field['label'] );

					jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_label', 'label' );

					jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_add', 'a', [
						'type'  => 'button',
						'class' => 'button button-primary right jupiterx-flexible-option-add',
					] );

						esc_html_e( 'Add', 'jupiterx-core' );

					jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_add', 'a' );

				jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_controls', 'div' );

				jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_option_wrapper', 'div', 'class=jupiterx-flexible-option-wrapper' );
					foreach ( $field['options'] as $key => $label ) {

						$item_classes = '';
						if ( isset( $values[ $key ] ) ) {
							if ( ! empty( $values[ $key ]['value'] ) ) {
								$item_classes = 'hidden';
							}
						}

						jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_option', 'span', [
							'class'      => 'jupiterx-flexible-option ' . esc_attr( $item_classes ),
							'data-field' => $key,
						] );

							echo esc_html( $label );

						jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_option', 'span' );
					}

				jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_option_wrapper', 'div' );
				$field['options'] = wp_parse_args( $field['options'], $values );
				foreach ( $field['options'] as $key => $label ) {
					$value        = '';
					$item_classes = 'hidden';
					if ( isset( $values[ $key ] ) ) {
						if ( ! empty( $values[ $key ]['value'] ) ) {
							$value        = $values[ $key ]['value'];
							$item_classes = 'visible';
						}
					}

					jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_item', 'div', [
						'class'       => 'jupiterx-flexible-item ' . esc_attr( $item_classes ),
						'data-option' => $key,
					] );

						jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_item_label', 'label' );

							echo esc_html( $label );

						jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_item_label', 'label' );

						jupiterx_selfclose_markup_e( 'jupiterx_widget_control_flexible_input', 'input', [
							'class' => 'widefat',
							'name'  => $field_name . '[' . $key . '][value]',
							'type'  => $input_type,
							'value' => $value,
						] );

						jupiterx_selfclose_markup_e( 'jupiterx_widget_control_flexible_label_input', 'input', [
							'name'  => $field_name . '[' . $key . '][label]',
							'type'  => 'hidden',
							'value' => $label,
						] );

						// This span has nothing inside. It shows dashicon using :before. Empty line between codes escaped.
						jupiterx_open_markup_e( 'jupiterx_widget_control_flexible_remove', 'span', 'class=dashicons dashicons-minus jupiterx-flexible-option-remove' );
						jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_remove', 'span' );

					jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_item_field', 'div' );
				}

				jupiterx_selfclose_markup_e( 'jupiterx_widget_control_flexible_trigger_input', 'input', [
					'class' => 'save-trigger',
					'type'  => 'hidden',
					'name'  => $field_name . '[save_trigger]',
					'value' => '',
				] );

			jupiterx_close_markup_e( 'jupiterx_widget_control_flexible_wrapper', 'div' );
		}

		/**
		 * Generate divider.
		 *
		 * @param array $instance Widget instance.
		 * @param array $field Setting field args.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
		 */
		public function divider( $instance, $field ) {
			jupiterx_selfclose_markup_e( 'jupiterx_widget_control_divider', 'hr' );
		}

		/**
		 * Processing widget options on save.
		 *
		 * @since 1.0.0
		 *
		 * @param array $new_instance The new options.
		 * @param array $old_instance The previous options.
		 *
		 * @return $instance Modified widget instance.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			foreach ( $this->settings as $setting ) {
				$id = $setting['name'];

				$instance[ $id ] = isset( $new_instance[ $id ] ) ? $new_instance[ $id ] : '';
			}

			return $instance;
		}
	}
}
