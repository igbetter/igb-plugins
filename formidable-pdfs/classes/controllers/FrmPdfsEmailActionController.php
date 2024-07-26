<?php
/**
 * Controller for email action
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsEmailActionController
 */
class FrmPdfsEmailActionController {

	/**
	 * Checks if PDF attachment is enabled in email action settings.
	 *
	 * @param array|object $settings Form action settings array or form action object.
	 * @return bool
	 */
	public static function is_pdf_attachment_enabled( $settings ) {
		if ( is_object( $settings ) ) {
			return ! empty( $settings->post_content['attach_pdf'] );
		}

		if ( is_array( $settings ) ) {
			return ! empty( $settings['attach_pdf'] );
		}

		return false;
	}

	/**
	 * Adds email action attachment setting.
	 *
	 * @param array $args The args.
	 */
	public static function add_attachment_setting( $args ) {
		$form_action = $args['form_action'];
		$pass_args   = $args['pass_args'];

		include FrmPdfsAppHelper::plugin_path() . '/classes/views/email-attachment-setting.php';
	}

	/**
	 * Adds attachment to email.
	 *
	 * @param array  $attachments Array of attachment file paths.
	 * @param object $form        Form object.
	 * @param array  $args        The args. See {@see FrmEmail::set_attachments()}.
	 * @return array
	 */
	public static function add_attachments( $attachments, $form, $args ) {
		if ( ! self::is_pdf_attachment_enabled( $args['settings'] ) ) {
			return $attachments;
		}

		$pdf_args = array();

		$args['form'] = $form;

		/**
		 * Filters the args of FrmPdfsAppController:generate_entry_pdf() method when adding email attachments.
		 *
		 * @since 2.0
		 *
		 * @param array $pdf_args The args of {@see FrmPdfsAppController::generate_entry_pdf()}.
		 * @param array $args     The args of {@see FrmEmail::set_attachments()} with `form` object added.
		 */
		$pdf_args = apply_filters( 'frm_pdfs_email_attachment_args', $pdf_args, $args );

		$pdf_args['mode'] = FrmPdfsAppController::FILE_MODE;

		$file_path = FrmPdfsAppController::generate_entry_pdf( $args['entry'], $pdf_args );
		if ( $file_path ) {
			$attachments[] = $file_path;

			add_action(
				'frm_notification',
				function() use ( $file_path ) {
					FrmPdfsAppController::delete_file( $file_path );
				}
			);
		}

		return $attachments;
	}
}
