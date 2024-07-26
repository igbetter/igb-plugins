<?php
/**
 * Onboarding Wizard Helper class.
 *
 * @package Formidable
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles validation related tasks for Formidable Pro entries.
 *
 * @since 6.9
 */
class FrmProEntryValidate {

	/**
	 * Applies international phone number format to the given pattern, if specified in field options.
	 *
	 * @since 6.9
	 *
	 * @param string $pattern Existing regex pattern for phone number validation.
	 * @param object $field   Form field object containing field options.
	 * @return string Modified or original regex pattern.
	 */
	public static function apply_international_phone_format( $pattern, $field ) {
		$format = FrmField::get_option( $field, 'format' );

		if ( 'international' === $format ) {
			$pattern = self::get_international_phone_regex();
		}

		return $pattern;
	}

	/**
	 * Provides a regex pattern for validating international phone numbers.
	 *
	 * @since 6.9
	 *
	 * @return string Regex pattern for international phone number validation.
	 */
	private static function get_international_phone_regex() {
		return '^\+?\d{1,4}[\s\-]?(?:\(\d{1,3}\)[\s\-]?)?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}$';
	}
}
