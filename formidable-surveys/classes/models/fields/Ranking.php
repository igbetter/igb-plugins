<?php
/**
 * Ranking Field type class
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models\fields;

use FrmFieldType;
use FrmProImages;
use FrmProAppHelper;
use FrmSurveys\helpers\AppHelper;
use FrmSurveys\controllers\RankingController;
use FrmProFieldsHelper;
use FrmFieldsHelper;

/**
 * Extend Field class.
 *
 * @since 1.1
 */
class Ranking extends FrmFieldType {

	/**
	 * Set field type.
	 *
	 * @var string
	 * @since 1.1
	 */
	protected $type = 'ranking';

	/**
	 * Does the html for this field label need to include "for"?
	 *
	 * @var bool
	 * @since 1.1
	 */
	protected $has_for_label = false;

	/**
	 * The HTML input.
	 */
	protected function input_html() {
		return $this->multiple_input_html();
	}

	/**
	 * Get the Ranking Field frontend view file.
	 */
	protected function include_form_builder_file() {
		return $this->include_front_form_file();
	}

	/**
	 * The default Ranking Field options which will get displayed by default in the builder.
	 *
	 * @return string[]
	 */
	protected function new_field_settings() {
		return array(
			'options' => array(
				__( 'Ranking Option 1', 'formidable-surveys' ),
				__( 'Ranking Option 2', 'formidable-surveys' ),
			),
		);
	}

	/**
	 * Get the type of field being displayed.
	 *
	 * @since 1.1
	 *
	 * @param array $field .
	 *
	 * @return array
	 */
	public function displayed_field_type( $field ) {
		return array(
			$this->type => true,
		);
	}

	/**
	 * Adds extra options to Image options: Limit Selections, Image Size.
	 *
	 * @since 1.1
	 */
	protected function extra_field_opts() {
		return array_merge(
			parent::extra_field_opts(),
			array(
				'limit_selections'   => 0,
				'answers_limit'      => '',
				'randomize_options'  => 0,
				'image_options'      => 0,
				'image_sizes'        => 0,
				'hide_option_labels' => 0,
			)
		);
	}

	/**
	 * Displays extra option for Ranking Field. It will be displayed below Ranking Field Options.
	 *
	 * @since 1.1
	 *
	 * @param array $args - Includes 'field', 'display', and 'values'.
	 */
	public function show_extra_field_choices( $args ) {
		$field = $args['field'];
		include AppHelper::plugin_path() . '/classes/views/fields/backend/ranking-extra-options.php';
	}

	/**
	 * Display Option Tabs: "Simple" and "Images".
	 *
	 * @since 1.1
	 *
	 * @param array $args .
	 *
	 * @return void
	 */
	protected function show_priority_field_choices( $args = array() ) {
		FrmProImages::show_image_choices( $args );
	}

	/**
	 * Load Ranking Field frontend file.
	 *
	 * @return string
	 */
	protected function include_front_form_file() {
		RankingController::init_frontend_scripts();
		return AppHelper::plugin_path() . '/classes/views/fields/frontend/ranking.php';
	}

	/**
	 * Enable to add field multiple options.
	 *
	 * @param array $args Includes field, display, and values.
	 *
	 * @return bool
	 */
	protected function should_continue_to_field_options( $args ) {
		return true;
	}

	/**
	 * Fill the Pro defaults for this field.
	 * This enables the option to toggle this on and off with conditional logic.
	 * It also enables the field visiblity setting.
	 *
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings = array();
		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * Validate field.
	 *
	 * @param array $args Arguments. Includes `errors`, `value`.
	 * @return array Errors array.
	 */
	public function validate( $args ) {
		$errors = isset( $args['errors'] ) ? $args['errors'] : array();

		if ( ! $this->field->required ) {
			return $errors;
		}
		$active_order_selections = array_filter(
			$args['value']['order'],
			function( $item ) {
				return (int) $item !== 0;
			}
		);

		if ( ! empty( $active_order_selections ) ) {
			return $errors;
		}

		$errors[ 'field' . $args['id'] ] = FrmFieldsHelper::get_error_msg( $this->field, 'blank' );
		return $errors;
	}

	/**
	 * Prepare Ranking Field values before form data saving.
	 *
	 * @param array $value The field values.
	 * @param array $atts The field atts.
	 *
	 * @return bool
	 */
	public function get_value_to_save( $value, $atts ) {
		if ( is_array( $value ) ) {
			unset( $value['order'] );
		}
		return $value;
	}

	/**
	 * Make sure that when using separated values, labels are shown by default.
	 *
	 * @since 1.1
	 *
	 * @param array|string $value The value before display.
	 * @param array        $atts  Display attributes.
	 * @return array|string
	 */
	protected function prepare_display_value( $value, $atts ) {
		if ( ! empty( $atts['saved_value'] ) ) {
			// This is the "value" column on the entries list page.
			return $value;
		}

		if ( ! empty( $atts['show'] ) && 'value' === $atts['show'] ) {
			// Show the value if we're using show="value".
			return $value;
		}

		if ( is_string( $value ) && false !== strpos( $value, 'frm_show_images' ) ) {
			// Leave values with images alone.
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		$output = array();
		foreach ( $value as $current_value ) {
			foreach ( $this->field->options as $option ) {
				if ( is_array( $option ) && isset( $option['value'] ) && $current_value === $option['value'] ) {
					$output[] = $option['label'];
					break;
				}
			}
		}

		return $output;
	}
}
