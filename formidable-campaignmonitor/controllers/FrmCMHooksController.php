<?php
/**
 * Hooks for Formidable Campaign Monitor processes.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

/**
 * FrmCMHooksController Class loads all the hooks to keep memory low.
 */
class FrmCMHooksController {

	public static function load_hooks() {
		add_action( 'plugins_loaded', 'FrmCMAppController::load_lang' );
		add_action( 'frm_trigger_campaignmonitor_action', 'FrmCMAppController::trigger_campaignmonitor', 10, 3 );
		add_action( 'frm_registered_form_actions', 'FrmCMSettingsController::register_actions' );

		self::load_admin_hooks();
	}

	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmCMAppController::include_updater', 1 );
		add_action( 'after_plugin_row_formidable-campaignmonitor/formidable-campaignmonitor.php', 'FrmCMAppController::min_version_notice' );

		add_action( 'frm_add_settings_section', 'FrmCMSettingsController::add_settings_section' );
		add_action( 'wp_ajax_frm_campaignmonitor_match_fields', 'FrmCMSettingsController::match_fields' );

		add_action( 'wp_ajax_clear_campaignmonitor_fields_cache', 'FrmCMAppController::clear_cache' );

	}
}
