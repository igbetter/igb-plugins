<?php
/**
 * License expired email controller
 *
 * @since 6.7
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProEmailLicenseExpiredController {

	const LICENSE_EXPIRED = 'license';

	/**
	 * Maybe send the license expired email.
	 */
	public static function maybe_send() {
		if ( ! class_exists( 'FrmEmailSummaryHelper', false ) || ! FrmEmailSummaryHelper::is_enabled() ) {
			return;
		}

		$last_sent_date = self::get_last_sent_date_for_current_license();

		// Check for license expired email.
		if ( ! FrmProAddonsController::is_license_expired() ) {
			if ( $last_sent_date ) {
				$clear_date   = gmdate( 'Y-m-d', strtotime( '+11 months', strtotime( $last_sent_date ) ) );
				$current_date = FrmEmailSummaryHelper::get_date_from_today();

				if ( $current_date > $clear_date ) {
					// Clear last sent date if license is renewed and it's been over 11 months from the last sent date.
					FrmEmailSummaryHelper::set_last_sent_date( self::get_email_option_key(), '' );
				}
			}
			return;
		}

		if ( ! $last_sent_date ) {
			// If license expired and license email hasn't been sent, send it.
			self::send();
		}
	}

	/**
	 * Gets option key to track last sent date for current license.
	 *
	 * @since 6.9.1
	 *
	 * @return string
	 */
	private static function get_email_option_key() {
		$option_key  = self::LICENSE_EXPIRED;
		$pro_license = FrmAddonsController::get_pro_license();
		if ( $pro_license ) {
			$option_key = $option_key . '_' . md5( $pro_license );
		}

		return $option_key;
	}

	/**
	 * Gets last sent date for current license expired email.
	 *
	 * @since 6.9.1
	 *
	 * @return false|string
	 */
	private static function get_last_sent_date_for_current_license() {
		$new_option_key     = self::get_email_option_key();
		$old_last_sent_date = FrmEmailSummaryHelper::get_last_sent_date( self::LICENSE_EXPIRED );

		if ( self::LICENSE_EXPIRED === $new_option_key ) {
			return $old_last_sent_date;
		}

		if ( $old_last_sent_date ) {
			// If email was sent in the old version, move last sent date to new option.
			FrmEmailSummaryHelper::set_last_sent_date( $new_option_key, $old_last_sent_date );
			FrmEmailSummaryHelper::set_last_sent_date( self::LICENSE_EXPIRED, '' );
			return $old_last_sent_date;
		}

		return FrmEmailSummaryHelper::get_last_sent_date( $new_option_key );
	}

	/**
	 * Sends the license expired email.
	 */
	private static function send() {
		$license_email = new FrmProEmailLicenseExpired();

		if ( $license_email->send() ) {
			FrmEmailSummaryHelper::set_last_sent_date( self::get_email_option_key() );
		}
	}
}
