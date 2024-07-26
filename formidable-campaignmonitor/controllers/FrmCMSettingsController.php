<?php
/**
 * Formidable Campaign Monitor Settings Handler.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

/**
 * FrmCMSettingsController Class Saves and gets the Global settings.
 */
class FrmCMSettingsController {

	public static function add_settings_section( $sections ) {
		$sections['campaignmonitor'] = array(
			'class'    => 'FrmCMSettingsController',
			'function' => 'route',
			'name'     => 'Campaign Monitor',
			'icon'     => 'frm_icon_font frm_campaignmonitor_icon',
		);

		return $sections;
	}

	public static function match_fields() {
		check_ajax_referer( 'frmcampaignmonitor_ajax', 'security' );

		$form_id = FrmAppHelper::get_param( 'form_id', '', 'post', 'absint' );
		$list_id = FrmAppHelper::get_param( 'list_id', '', 'post', 'sanitize_text_field' );

		if ( empty( $form_id ) || empty( $list_id ) ) {
			wp_die();
		}

		$api = new FrmCMAPI();
		$list_fields = $api->get_list_fields( $list_id );

		$exclude_fields = implode( "','", FrmField::no_save_fields() );
		$form_fields    = FrmField::getAll( 'fi.form_id=' . (int) $form_id . " and fi.type not in ('" . $exclude_fields . "')", 'field_order' );

		if ( isset( $_POST['action_key'] ) ) {
			$action_control = FrmFormActionsController::get_form_actions( 'campaignmonitor' );
			if ( is_callable( array( $action_control, '_set' ) ) ) {
				$action_control->_set( FrmAppHelper::get_post_param( 'action_key', '', 'sanitize_text_field' ) );
			}

			include FrmCMAppController::path() . '/views/action-settings/_match_fields.php';
		}

		wp_die();
	}

	public static function register_actions( $actions ) {
		$actions['campaignmonitor'] = 'FrmCMAction';

		include_once FrmCMAppController::path() . '/models/FrmCMAction.php';

		return $actions;
	}

	public static function display_form() {
		$settings = new FrmCMSettings();
		$frm_version = FrmAppHelper::plugin_version();

		require_once FrmCMAppController::path() . '/views/settings/form.php';
	}

	public static function process_form() {
		$settings = new FrmCMSettings();

		$process_form = FrmAppHelper::get_post_param( 'process_form', '', 'sanitize_text_field' );

		if ( wp_verify_nonce( $process_form, 'process_form_nonce' ) ) {
			$error = self::check_api_key( $settings );
			if ( ! empty( $error ) ) {
				$errors = array( $error );
			}

			$settings->update();
			$settings->store();
		}

		require_once FrmCMAppController::path() . '/views/settings/form.php';
	}

	/**
	 * Check the api keys if they have changed.
	 */
	private static function check_api_key( $settings ) {
		$api_key   = FrmAppHelper::get_param( 'frm_campaignmonitor_api_key', '', 'post', 'sanitize_text_field' );
		$client_id = FrmAppHelper::get_param( 'frm_campaignmonitor_client_api_key', '', 'post', 'sanitize_text_field' );

		if ( $settings->settings->api_key == $api_key && $settings->settings->client_api_key == $client_id ) {
			// The settings haven't changed, so don't do anything.
			return '';
		}

		if ( ! class_exists( 'Frm_Campaign_Monitor' ) ) {
			require_once( FrmCMAppController::path() . '/api/campaign-monitor.php' );
		}

		try {
			$api = new Frm_Campaign_Monitor( $api_key, $client_id );
			$api->get_lists(); // Check the API key and ID by running an API call.
		} catch ( Exception $e ) {
			return __( 'API key and/or Client ID may not be correct', 'frm-campaignmonitor' );
		}

		return '';
	}

	public static function route() {
		$action = FrmAppHelper::get_param( 'action', '', 'get', 'sanitize_text_field' );

		if ( 'process-form' == $action ) {
			return self::process_form();
		} else {
			return self::display_form();
		}
	}
}
