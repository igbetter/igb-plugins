<?php
/**
 * Custom scale field controller
 *
 * @since 1.0.08
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmField;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Scale buttons routing.
 *
 * @since 1.0.08
 */
class ScaleController {

	/**
	 * Extend the Scale field code.
	 *
	 * @param string $class      The Class name.
	 * @param string $field_type The name of the field type.
	 *
	 * @since 1.0.08
	 */
	public static function change_scale_field_type_class( $class, $field_type ) {
		if ( 'scale' === $field_type ) {
			return '\\FrmSurveys\\models\\fields\\Scale';
		}
		return $class;
	}

	/**
	 * Changes options of Display format setting of Scale field.
	 *
	 * @since 1.0.08
	 *
	 * @param array $options The options array.
	 * @param array $field   The field array.
	 *
	 * @return array
	 */
	public static function change_scale_display_format_options( $options, $field ) {
		unset( $options['1'] );

		if ( isset( $options['buttons']['addon'] ) ) {
			unset( $options['buttons']['addon'] );
		}

		return $options;
	}

	/**
	 * Changes args of Display format setting args of Scale field.
	 *
	 * @since 1.0.08
	 *
	 * @param array $args        The arguments.
	 * @param array $method_args The arguments from the method. Contains `field`, `options`.
	 *
	 * @return array
	 */
	public static function change_scale_display_format_args( $args, $method_args ) {
		$field               = $method_args['field'];
		$args['selected']    = FrmField::get_option( $field, 'image_options' );
		$args['input_attrs'] = array(
			'name'     => 'field_options[image_options_' . intval( $field['id'] ) . ']',
			'class'    => 'frm_scale_display_format',
			'data-fid' => intval( $field['id'] ),
		);

		return $args;
	}

	/**
	 * Adds custom CSS classes to field element based off the field setting.
	 *
	 * @since 1.0.08
	 *
	 * @param string $classes CSS classes.
	 * @param array  $field   Field data.
	 *
	 * @return string
	 */
	public static function add_field_classes( $classes, $field ) {
		$field = (array) $field;
		if ( 'scale' !== $field['type'] ) {
			return $classes;
		}

		if ( 'buttons' !== FrmField::get_option( $field, 'image_options' ) ) {
			return $classes;
		}

		$classes .= ' frm_display_format_buttons';

		return $classes;
	}

	/**
	 * Updates the displayed field types so that the 'Scale Options' section will become available in the field options.
	 *
	 * @since 1.0.08
	 *
	 * @param array $display_type   The list of field types.
	 * @param array $args Contains 'field'.
	 *
	 * @return array
	 */
	public static function update_displayed_field_types( $display_type, $args ) {
		$display_type['scale'] = 'scale' === FrmField::get_field_type( $args['field'] );

		return $display_type;
	}

	/**
	 * Returns true if the pro and core dependencies are met for the button display format feature.
	 *
	 * @since 1.0.08
	 *
	 * @return bool
	 */
	public static function core_and_pro_versions_met() {
		return is_callable( 'FrmFieldsHelper::get_display_format_options' ) && is_callable( array( new \FrmProFieldScale(), 'echo_option_label' ) );
	}
}
