<?php
/**
 * Custom radio field controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmField;
use FrmFieldsHelper;
use FrmProImages;
use FrmSurveys\helpers\AppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Radio/Checkbox buttons routing.
 *
 * @since 1.0
 */
class RadioController {

	/**
	 * Extend the Radio/Checkbox field code.
	 *
	 * @param string $class The Class name.
	 * @param string $field_type The name of the field type.
	 *
	 * @since 1.0
	 */
	public static function change_radio_field_type_class( $class, $field_type ) {
		if ( 'radio' === $field_type ) {
			return '\\FrmSurveys\\models\\fields\\Radio';
		}
		if ( 'checkbox' === $field_type ) {
			return '\\FrmSurveys\\models\\fields\\Checkbox';
		}
		return $class;
	}

	/**
	 * Checks if image is enabled in the field.
	 *
	 * @param array $field Field data.
	 * @return bool
	 */
	public static function is_image_enabled( $field ) {
		$field = (array) $field;
		if ( ! isset( $field['type'] ) || ( 'radio' !== $field['type'] && 'checkbox' !== $field['type'] ) ) {
			return false;
		}

		$display_format        = FrmField::get_option( $field, 'image_options' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );
		return 1 === intval( $display_format ) || 'buttons' === $display_format && $use_images_in_buttons;
	}

	/**
	 * This is hooked to `frm_pro_field_should_show_images`.
	 *
	 * @param bool  $show Set to `true` to show.
	 * @param array $args The arguments.
	 * @return bool
	 */
	public static function field_should_show_images( $show, $args ) {
		$field = (array) $args['field'];
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $show;
		}

		return self::is_image_enabled( $field );
	}

	/**
	 * This is hooked to `frm_pro_field_should_show_label`.
	 *
	 * @param bool  $show Set to `true` to show.
	 * @param array $args The arguments.
	 * @return bool
	 */
	public static function field_should_show_label( $show, $args ) {
		$field = (array) $args['field'];
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $show;
		}

		if ( 'buttons' === FrmField::get_option( $field, 'image_options' ) ) {
			// Always show label in buttons format.
			return true;
		}

		return $show;
	}

	/**
	 * Adds custom CSS classes to field element.
	 *
	 * @param string $classes CSS classes.
	 * @param array  $field   Field data.
	 * @return string
	 */
	public static function add_field_classes( $classes, $field ) {
		$field = (array) $field;
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $classes;
		}

		if ( 'buttons' !== FrmField::get_option( $field, 'image_options' ) ) {
			return $classes;
		}

		$classes .= ' frm_display_format_buttons';

		$text_align            = FrmField::get_option( $field, 'text_align' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );

		$classes .= ' frm_text_align_' . $text_align;

		if ( $use_images_in_buttons && 'center' !== $text_align ) {
			$image_align = FrmField::get_option( $field, 'image_align' );
			$classes    .= ' frm_image_align_' . $image_align;
		}

		return $classes;
	}

	/**
	 * Changes the HTML of option label.
	 *
	 * @param string $label Label HTML.
	 * @param array  $args  The arguments. Contains `field`.
	 * @return string
	 */
	public static function choice_field_option_label( $label, $args ) {
		$field = $args['field'];

		$display_format        = FrmField::get_option( $field, 'image_options' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );

		if ( 'buttons' === $display_format && ! $use_images_in_buttons ) {
			// Add a wrapper div to change styling of the label.
			return '<div class="frm_label_button_container">' . $label . '</div>';
		}

		return $label;
	}

	/**
	 * Changes options of Display format setting of Radio field.
	 *
	 * @param array $options The options array.
	 * @return array
	 */
	public static function change_radio_display_format_options( $options ) {
		_deprecated_function( __METHOD__, '1.0.09', 'AppController::change_field_display_format_options' );
		return AppController::change_field_display_format_options( $options );
	}

	/**
	 * Changes args of Display format setting of Radio field.
	 *
	 * @param array $args        The arguments.
	 * @param array $method_args The arguments from the method. Contains `field`, `options`.
	 * @return array
	 */
	public static function change_radio_display_format_args( $args, $method_args ) {
		_deprecated_function( __METHOD__, '1.0.09', 'AppController::change_field_display_format_args' );
		return AppController::change_field_display_format_args( $args, $method_args );
	}
}
