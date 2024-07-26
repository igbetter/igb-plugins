<?php
/**
 * Hooks controller
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsHooksController
 */
class FrmPdfsHooksController {

	/**
	 * Adds this class to hook controllers list.
	 *
	 * @param array $controllers Hooks controllers.
	 * @return array
	 */
	public static function add_hooks_controller( $controllers ) {
		if ( FrmPdfsAppHelper::get_incompatible_error_messages_arr() ) {
			self::load_incompatible_hooks();
			return $controllers;
		}

		$controllers[] = __CLASS__;
		return $controllers;
	}

	/**
	 * Loads hooks when this plugin isn't safe to run.
	 */
	private static function load_incompatible_hooks() {
		self::load_translation();

		add_action( 'admin_notices', array( 'FrmPdfsAppController', 'show_incompatible_notice' ) );
		add_filter( 'frm_message_list', array( 'FrmPdfsAppController', 'add_incompatible_notice_to_message_list' ) );
	}

	/**
	 * Loads translation.
	 */
	private static function load_translation() {
		add_action( 'plugins_loaded', array( 'FrmPdfsAppController', 'init_translation' ) );
	}

	/**
	 * Loads plugin hooks.
	 */
	public static function load_hooks() {
		self::load_translation();

		add_filter( 'frm_entry_formatter_class', array( 'FrmPdfsAppController', 'entry_formatter_class' ), 10, 2 );
		add_shortcode( FrmPdfsShortcodeController::ENTRY_PDF_SC, array( 'FrmPdfsShortcodeController', 'frm_pdf_handler' ) );
		add_action( 'wp', array( 'FrmPdfsShortcodeController', 'handle_request' ) );
		add_filter( 'frm_display_entry_content', array( 'FrmPdfsShortcodeController', 'replace_pdf_link_shortcode' ), 20, 2 ); // Run after internal shortcodes in a View.
		add_filter( 'frm_replace_content_shortcodes', array( 'FrmPdfsShortcodeController', 'replace_pdf_link_shortcode' ), 20, 2 );
		add_action( 'frm_notification_attachment', array( 'FrmPdfsEmailActionController', 'add_attachments' ), 10, 3 );

		add_filter( 'frm_display_entry_content', array( 'FrmPdfsViewsController', 'entry_content' ), 10, 7 );
		add_filter( 'frm_display_inner_content_before_add_wrapper', array( 'FrmPdfsViewsController', 'inner_content_before_add_wrapper' ), 10, 3 );
		add_filter( 'frm_views_table_class', array( 'FrmPdfsViewsController', 'table_view_class' ) );
	}

	/**
	 * These hooks only load in the admin area.
	 */
	public static function load_admin_hooks() {
		add_action( 'admin_init', array( 'FrmPdfsAppController', 'include_updater' ) );
		add_filter( 'frm_entry_actions_dropdown', array( 'FrmPdfsViewEntryController', 'add_download_pdf_action' ), 20, 2 );
		add_action( 'frm_pro_after_email_attachment_row', array( 'FrmPdfsEmailActionController', 'add_attachment_setting' ) );
		add_filter( 'frm_helper_shortcodes', array( 'FrmPdfsShortcodeController', 'add_to_shortcodes_list' ) );
	}
}
