<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmChatSettingsController {

	/**
	 * @return void
	 */
	public static function load_hooks() {
		if ( is_admin() ) {
			add_filter( 'frm_add_form_settings_section', 'FrmChatSettingsController::add_settings_section', 20 ); // use 20 so it happens after pro updates sections.
			add_action( 'admin_enqueue_scripts', 'FrmChatSettingsController::enqueue_admin_js' );
		}
	}

	/**
	 * Enqueue the JS for the settings page
	 *
	 * @return void
	 */
	public static function enqueue_admin_js() {
		if ( class_exists( 'FrmAppHelper' ) && self::is_form_settings_page() ) {
			$version = FrmChatAppHelper::plugin_version();
			wp_register_script( 'formidable_chat_settings', FrmChatAppHelper::plugin_url() . '/js/settings.js', array(), $version, true );
			wp_enqueue_script( 'formidable_chat_settings' );

			wp_register_style( 'formidable_chat_settings', FrmChatAppHelper::plugin_url() . '/css/settings.css', array(), $version );
			wp_enqueue_style( 'formidable_chat_settings' );
		}
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @return bool
	 */
	private static function is_form_settings_page() {
		$page   = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		return 'formidable' === $page && 'settings' === $action;
	}

	/**
	 * @return string
	 */
	private static function get_settings_views_path() {
		return FrmChatAppHelper::path() . '/views/settings/';
	}

	/**
	 * @param array $sections
	 * @return array
	 */
	public static function add_settings_section( $sections ) {
		$sections['chat'] = array(
			'function' => array( __CLASS__, 'settings_section' ),
			'name'     => __( 'Conversational Forms', 'formidable' ),
			'icon'     => 'frm_icon_font frm_chat_forms_icon',
			'anchor'   => 'chat',
		);
		return $sections;
	}

	/**
	 * @param array $values
	 * @return void
	 */
	public static function settings_section( $values ) {
		require self::get_settings_views_path() . 'settings.php';
	}

	/**
	 * @param int $form_id
	 * @return string
	 */
	public static function get_settings_url( $form_id ) {
		return admin_url( 'admin.php?page=formidable&frm_action=settings&id=' . absint( $form_id ) . '&t=chat_settings' );
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @param array  $args
	 * @return string|null|void
	 */
	private static function toggle( $id, $name, $args ) {
		if ( is_callable( 'FrmProHtmlHelper::toggle' ) ) {
			return FrmProHtmlHelper::toggle( $id, $name, $args );
		}
		require FrmChatAppHelper::path() . '/views/settings/_toggle.php';
	}
}
