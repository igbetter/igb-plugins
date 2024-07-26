<?php
/**
 * Handle the Formidable Campaign Monitor updating.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmCampaignMonitorUpdate Class Handles the plugin updates.
 */
class FrmCMUpdate extends FrmAddon {

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	public $plugin_name = 'Formidable Campaign Monitor';

	/**
	 * Update download ID
	 *
	 * @var int
	 */
	public $download_id = 20891694;

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	public $version = '1.05';

	public function __construct() {
		$this->plugin_file = FrmCMAppController::path() . '/formidable-campaignmonitor.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmCMUpdate();
	}
}
