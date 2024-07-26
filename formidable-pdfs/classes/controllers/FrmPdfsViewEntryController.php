<?php
/**
 * Controller for view entry page
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsViewEntryController
 */
class FrmPdfsViewEntryController {

	/**
	 * Adds Download as PDF action.
	 *
	 * @param array $actions Entry actions.
	 * @param array $args    {
	 *     The passed args.
	 *
	 *     @type object $entry  Entry object.
	 *     @type int    $id     The ID of the current entry.
	 * }
	 * @return array
	 */
	public static function add_download_pdf_action( $actions, $args ) {
		if ( ! FrmPdfsAppHelper::current_user_can_download_pdf() ) {
			return $actions;
		}

		$url         = FrmPdfsShortcodeController::frm_pdf_handler(
			array(
				'id'    => intval( $args['id'] ),
				'mode'  => FrmPdfsAppController::DOWNLOAD_MODE,
				'label' => '',
			)
		);
		$new_actions = array();
		foreach ( $actions as $name => $action ) {
			if ( 'frm_resend' === $name ) {
				$new_actions['frm_download_pdf'] = array(
					'url'   => $url,
					'label' => __( 'Download as PDF', 'formidable-pdfs' ),
					'icon'  => 'frm_icon_font frm_download_icon',
					'class' => 'frm_download_pdf_button',
				);
			}

			$new_actions[ $name ] = $action;
		}

		return $new_actions;
	}
}
