<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 5.3
 */
class FrmViewsSettingsController {

	/**
	 * Called when fitlering setting sections.
	 * Nothing is currently added, but this hook is used to add related settings actions.
	 *
	 * @param array $sections
	 * @return array
	 */
	public static function add_settings_section( $sections ) {
		add_action( 'frm_messages_settings_form', array( __CLASS__, 'message_settings' ) );
		return $sections;
	}

	/**
	 * @param object $frm_settings
	 * @return void
	 */
	public static function message_settings( $frm_settings ) {
		$frmviews_settings = FrmViewsAppHelper::get_settings();
		require FrmViewsAppHelper::plugin_path() . '/classes/views/settings/messages.php';
	}

	/**
	 * @param array $params
	 * @return void
	 */
	public static function update( $params ) {
		FrmViewsAppHelper::get_settings()->update( $params );
	}

	/**
	 * @return void
	 */
	public static function store() {
		FrmViewsAppHelper::get_settings()->store();
	}
}
