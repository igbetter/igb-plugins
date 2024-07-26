<?php
/**
 * Assets controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmAppHelper;
use FrmSurveys\helpers\AppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Assets controller
 *
 * @since 1.0
 */
class AssetsController {

	/**
	 * Load scripts for the form builder.
	 *
	 * @since 1.0
	 */
	public static function load_admin_scripts() {
		$version    = AppHelper::plugin_version();
		$plugin_url = AppHelper::plugin_url();

		wp_register_style( 'frm-surveys-admin', $plugin_url . '/css/frm-surveys-admin.css', array(), $version );
		wp_register_script( 'frm-surveys-admin', $plugin_url . '/js/frm-surveys-admin.js', array( 'jquery-ui-sortable' ), $version, true );

		wp_localize_script(
			'frm-surveys-admin',
			'FrmSurveysL10n',
			array(
				'ajaxNonce' => wp_create_nonce( 'frm_surveys_ajax' ),
			)
		);

		// Enqueue.
		if ( FrmAppHelper::is_formidable_admin() ) {
			wp_enqueue_style( 'frm-surveys-admin' );
			wp_enqueue_script( 'frm-surveys-admin' );
		}
	}

	/**
	 * Load CSS into the combined Frm stylesheet.
	 *
	 * @since 1.0
	 */
	public static function add_frontend_css() {
		readfile( AppHelper::plugin_path() . '/css/frm-surveys.css' );
	}

	/**
	 * Loads and enqueues frontend js.
	 *
	 * @since 1.0.04
	 */
	public static function load_frontend_js() {
		wp_register_script( 'frm-surveys-likert', AppHelper::plugin_url() . '/js/frm-surveys-likert.js', array( 'jquery' ), AppHelper::$plug_version, true );
	}

	/**
	 * Use for JS in the form builder.
	 *
	 * @since 1.0
	 */
	public static function print_admin_footer_html() {
		if ( ! FrmAppHelper::doing_ajax() && FrmAppHelper::is_admin_page() && in_array( FrmAppHelper::get_param( 'frm_action' ), array( 'edit', 'update', 'duplicate' ) ) ) {
			LikertController::print_likert_opt_tmpl();
		}
	}

	/**
	 * Init the assests in the block editor - admin only.
	 *
	 * @since 1.1
	 */
	public static function block_editor_assets() {
		wp_enqueue_style(
			'formidable-surveys-block-editor-css',
			AppHelper::plugin_url() . '/css/block-editor.css',
			array( 'wp-edit-blocks' ),
			AppHelper::$plug_version
		);
	}
}
