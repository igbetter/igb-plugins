<?php

/**
 * Submit helper
 *
 * @since 6.9
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmProSubmitHelper
 */
class FrmProSubmitHelper {

	/**
	 * Checks if submit field feature is available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'FrmSubmitHelper' );
	}

	/**
	 * Copies submit field settings to form options.
	 *
	 * @since 6.9
	 *
	 * @param stdClass $form Form object.
	 * @return object
	 */
	public static function copy_submit_field_settings_to_form( $form ) {
		if ( ! self::is_available() ) {
			return $form;
		}

		$submit_field = FrmSubmitHelper::get_submit_field( $form->id );
		if ( ! $submit_field ) {
			return $form;
		}

		$form->options['edit_value']       = FrmField::get_option( $submit_field, 'edit_text' );
		$form->options['submit_align']     = FrmField::get_option( $submit_field, 'align' );
		$form->options['start_over']       = FrmField::get_option( $submit_field, 'start_over' );
		$form->options['start_over_label'] = FrmField::get_option( $submit_field, 'start_over_label' );

		return $form;
	}

	/**
	 * @param array $values
	 * @param int   $field_id
	 * @return array
	 */
	public static function clean_field_options_before_update( $values, $field_id = 0 ) {
		if ( ! $field_id || $values['type'] !== 'submit' ) {
			return $values;
		}

		$field = FrmField::getOne( $field_id );
		if ( $field ) {
			$form = FrmForm::getOne( $field->form_id );
			if ( $form ) {
				$form->options['submit_conditions'] = array(
					'show_hide'       => $values['field_options']['show_hide'],
					'any_all'         => $values['field_options']['any_all'],
					'hide_field'      => $values['field_options']['hide_field'],
					'hide_field_cond' => $values['field_options']['hide_field_cond'],
					'hide_opt'        => $values['field_options']['hide_opt'],
				);
				global $wpdb;
				$wpdb->update(
					$wpdb->prefix . 'frm_forms',
					array(
						'options' => serialize( $form->options ),
					),
					array(
						'id' => $form->id,
					)
				);
				FrmForm::clear_form_cache();
			}
		}

		unset( $values['field_options']['show_hide'] );
		unset( $values['field_options']['any_all'] );
		unset( $values['field_options']['hide_field'] );
		unset( $values['field_options']['hide_field_cond'] );
		unset( $values['field_options']['hide_opt'] );

		return $values;
	}
}
