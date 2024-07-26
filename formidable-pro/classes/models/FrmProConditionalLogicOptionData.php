<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProConditionalLogicOptionData {

	private static $all_fields_by_form_id = array();

	/**
	 * @param int|string $form_id
	 * @return array
	 */
	public static function get_all_fields_for_form( $form_id ) {
		if ( ! isset( self::$all_fields_by_form_id[ $form_id ] ) ) {
			self::$all_fields_by_form_id[ $form_id ] = self::get_prefiltered_list_of_fields_for_conditional_logic_options( (int) $form_id );
		}
		return self::$all_fields_by_form_id[ $form_id ];
	}

	/**
	 * This list of fields is used for conditional logic dropdown options.
	 * It is pre-filtered in order to make the list smaller.
	 * This way we can iterate fewer items when printing each logic row.
	 *
	 * @since 6.10.1
	 *
	 * @param int $form_id
	 * @return array
	 */
	private static function get_prefiltered_list_of_fields_for_conditional_logic_options( $form_id ) {
		return array_filter(
			FrmField::get_all_for_form( $form_id ),
			/**
			 * @param stdClass $field
			 * @return bool
			 */
			function ( $field ) {
				if ( FrmField::is_no_save_field( $field->type ) ) {
					return false;
				}

				if ( in_array( $field->type, array( 'file', 'date', 'address', 'credit_card' ), true ) ) {
					return false;
				}

				return ! FrmProField::is_list_field( $field );
			}
		);
	}

	/**
	 * @since 6.10.1
	 *
	 * @param int|string $form_id
	 * @return bool
	 */
	public static function data_is_available( $form_id ) {
		return array_key_exists( $form_id, self::$all_fields_by_form_id );
	}
}
